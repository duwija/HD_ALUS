<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\LeadWorkflow;
use App\CustomerStep;
use Illuminate\Support\Facades\DB;

class LeadWorkflowController extends Controller
{
    /**
     * Tampilkan halaman pengaturan workflow
     */
    public function index()
    {
        $stages = LeadWorkflow::orderBy('order')->get();
        return view('settings.lead-workflow', compact('stages'));
    }

    /**
     * Simpan stage baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $maxOrder = LeadWorkflow::max('order') ?? 0;

        LeadWorkflow::create([
            'name'        => $request->name,
            'description' => $request->description,
            'color'       => 'secondary',
            'order'       => $maxOrder + 1,
        ]);

        return redirect()->route('lead-workflow.index')
            ->with('success', 'Stage "' . $request->name . '" berhasil ditambahkan.');
    }

    /**
     * Update stage yang ada
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $stage = LeadWorkflow::findOrFail($id);
        $stage->update([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('lead-workflow.index')
            ->with('success', 'Stage "' . $stage->name . '" berhasil diupdate.');
    }

    /**
     * Hapus stage
     */
    public function destroy($id)
    {
        $stage = LeadWorkflow::findOrFail($id);

        // Cek apakah ada customer di stage ini
        $customerCount = \App\Customer::where('workflow_stage_id', $id)->count();
        if ($customerCount > 0) {
            return redirect()->route('lead-workflow.index')
                ->with('error', "Tidak bisa hapus stage \"{$stage->name}\" — masih ada {$customerCount} customer di stage ini.");
        }

        $stageName = $stage->name;
        $stage->delete();

        // Re-order semua stage yang tersisa
        $this->reorderAll();

        return redirect()->route('lead-workflow.index')
            ->with('success', "Stage \"{$stageName}\" berhasil dihapus.");
    }

    /**
     * Reorder via drag-drop (AJAX)
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order' => 'required|array',
            'order.*' => 'exists:lead_workflows,id',
        ]);

        foreach ($request->order as $index => $id) {
            LeadWorkflow::where('id', $id)->update(['order' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update workflow stage customer (AJAX dari show page)
     */
    public function updateCustomer(Request $request, $customerId)
    {
        $request->validate([
            'workflow_stage_id' => 'required|exists:lead_workflows,id',
        ]);

        $customer = \App\Customer::findOrFail($customerId);
        $oldStageId = $customer->workflow_stage_id;

        \App\Customer::where('id', $customerId)->update(['workflow_stage_id' => $request->workflow_stage_id]);

        // Log perubahan
        $oldStageName = $oldStageId ? (\App\LeadWorkflow::find($oldStageId)->name ?? $oldStageId) : '-';
        $newStageName = \App\LeadWorkflow::find($request->workflow_stage_id)->name ?? $request->workflow_stage_id;
        \App\LeadUpdate::create([
            'id_customer'   => $customer->id,
            'updated_by'    => auth()->user()->name ?? 'System',
            'field_changed' => 'workflow_stage_id',
            'old_value'     => $oldStageName,
            'new_value'     => $newStageName,
            'notes'         => 'Update workflow stage via progress bar',
        ]);

        $newStage = LeadWorkflow::find($request->workflow_stage_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'stage'    => $newStage->name,
                'color'    => $newStage->color,
                'order'    => $newStage->order,
            ]);
        }

        return redirect()->back()->with('success', 'Workflow stage diupdate ke "' . $newStage->name . '".');
    }

    /**
     * Helper: reset urutan setelah delete
     */
    private function reorderAll()
    {
        $stages = LeadWorkflow::orderBy('order')->get();
        foreach ($stages as $i => $stage) {
            $stage->update(['order' => $i + 1]);
        }
    }

    // =========================================================
    // PER-CUSTOMER STEPS (mirip ticket_steps di tiket)
    // =========================================================

    /**
     * Start workflow: copy template lead_workflows ke customer_steps
     */
    public function startSteps($customerId)
    {
        $customer = \App\Customer::findOrFail($customerId);

        // Hapus steps lama jika ada
        CustomerStep::where('customer_id', $customerId)->delete();

        // Copy dari template lead_workflows
        $templates = LeadWorkflow::orderBy('order')->get();
        foreach ($templates as $i => $tpl) {
            CustomerStep::create([
                'customer_id' => $customerId,
                'name'        => $tpl->name,
                'position'    => $i + 1,
            ]);
        }

        // Set step pertama sebagai aktif
        $first = CustomerStep::where('customer_id', $customerId)->orderBy('position')->first();
        \App\Customer::where('id', $customerId)->update(['current_step_id' => $first ? $first->id : null]);

        return response()->json(['success' => true, 'message' => 'Workflow dimulai dari template default.']);
    }

    /**
     * Tambah step baru untuk customer (AJAX)
     */
    public function addStep(Request $request, $customerId)
    {
        $request->validate(['name' => 'required|string|max:255']);

        $customer = \App\Customer::findOrFail($customerId);
        $maxPos = CustomerStep::where('customer_id', $customerId)->max('position') ?? 0;

        $step = CustomerStep::create([
            'customer_id' => $customerId,
            'name'        => $request->name,
            'position'    => $maxPos + 1,
        ]);

        return response()->json(['success' => true, 'step' => $step]);
    }

    /**
     * Hapus step customer (AJAX)
     */
    public function deleteStep(Request $request, $customerId)
    {
        $request->validate(['step_id' => 'required|exists:customer_steps,id']);

        $customer = \App\Customer::findOrFail($customerId);
        $step = CustomerStep::where('id', $request->step_id)
                            ->where('customer_id', $customerId)
                            ->firstOrFail();

        // Jika step yang dihapus adalah step aktif, reset ke sebelumnya
        if ($customer->current_step_id == $step->id) {
            $prev = CustomerStep::where('customer_id', $customerId)
                                ->where('position', '<', $step->position)
                                ->orderBy('position', 'desc')
                                ->first();
            \App\Customer::where('id', $customerId)->update(['current_step_id' => $prev ? $prev->id : null]);
        }

        $step->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Pindah ke step tertentu (set current_step_id)
     */
    public function moveStep(Request $request, $customerId)
    {
        $request->validate(['step_id' => 'required|exists:customer_steps,id']);

        $customer = \App\Customer::findOrFail($customerId);
        $step = CustomerStep::where('id', $request->step_id)
                            ->where('customer_id', $customerId)
                            ->firstOrFail();

        // Ambil nama step sebelumnya sebelum diupdate
        $oldStep = $customer->current_step_id
            ? CustomerStep::find($customer->current_step_id)
            : null;
        $oldStepName = $oldStep ? $oldStep->name : '-';

        \App\Customer::where('id', $customerId)->update(['current_step_id' => $step->id]);

        // Tandai waktu pemilihan step
        CustomerStep::where('id', $step->id)->update(['selected_at' => now()]);

        // Log ke lead_updates
        \App\LeadUpdate::create([
            'id_customer'   => $customer->id,
            'updated_by'    => auth()->user()->name ?? 'System',
            'field_changed' => 'workflow_step',
            'old_value'     => $oldStepName,
            'new_value'     => $step->name,
            'notes'         => null,
        ]);

        return response()->json(['success' => true, 'step' => $step->name]);
    }

    /**
     * Reorder steps via drag-drop (AJAX)
     */
    public function reorderSteps(Request $request, $customerId)
    {
        $request->validate([
            'order'          => 'required|array',
            'order.*.id'     => 'required|exists:customer_steps,id',
            'order.*.position' => 'required|integer',
        ]);

        foreach ($request->order as $item) {
            CustomerStep::where('id', $item['id'])
                        ->where('customer_id', $customerId)
                        ->update(['position' => $item['position']]);
        }

        return response()->json(['success' => true]);
    }
}
