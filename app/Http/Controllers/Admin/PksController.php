<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PksAddendum;
use App\PksAgreement;
use App\PksServicePoint;
use App\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Manajemen PKS per tenant (superadmin). Akun supervisor tidak punya akses
 * (route admin.pks.* tidak ada di SupervisorScope::ALLOWED_ROUTES).
 * File disimpan privat di storage/app/pks/{tenant_id}/ dan hanya bisa
 * diunduh lewat controller ini atau IspPksController (milik tenant sendiri).
 */
class PksController extends Controller
{
    const FILE_RULE = 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:20480';

    public function index(Request $request)
    {
        $tenants = Tenant::orderBy('app_name')->get(['id', 'app_name', 'domain', 'is_active']);

        $agreements = PksAgreement::with(['tenant', 'addenda', 'servicePoints', 'successors'])
            ->when($request->query('tenant'), fn($q, $t) => $q->where('tenant_id', (int) $t))
            ->when($request->query('q'), function ($q, $term) {
                $q->where(fn($w) => $w->where('nomor_pks', 'like', "%$term%")->orWhere('nib', 'like', "%$term%"));
            })
            ->orderByDesc('berlaku_mulai')
            ->get();

        $status = $request->query('status');
        $filtered = $status ? $agreements->filter(fn($a) => $a->status === $status)->values() : $agreements;

        // Ringkasan dihitung dari semua PKS (sebelum filter status).
        $current = $agreements->where('status', '!=', 'diganti');
        $summary = [
            'aktif' => $current->where('status', 'aktif')->count(),
            'segera_berakhir' => $current->where('status', 'segera_berakhir')->count(),
            'berakhir' => $current->where('status', 'berakhir')->count(),
            'kapasitas_aktif' => $current->whereIn('status', ['aktif', 'segera_berakhir'])->sum(fn($a) => $a->total_capacity),
            'tenant_tanpa_pks' => $tenants->whereNotIn('id', $agreements->pluck('tenant_id')->unique())->count(),
        ];

        return view('admin.pks.index', [
            'agreements' => $filtered,
            'tenants' => $tenants,
            'summary' => $summary,
        ]);
    }

    public function create(Request $request)
    {
        $previous = $request->query('previous') ? PksAgreement::with('servicePoints')->findOrFail($request->query('previous')) : null;
        $tenantId = $previous->tenant_id ?? (int) $request->query('tenant');

        // NIB biasanya sama untuk satu tenant: isi otomatis dari PKS terakhirnya.
        $lastNib = $tenantId ? PksAgreement::where('tenant_id', $tenantId)->whereNotNull('nib')->orderByDesc('id')->value('nib') : null;

        return view('admin.pks.form', [
            'agreement' => new PksAgreement([
                'tenant_id' => $tenantId ?: null,
                'previous_id' => $previous->id ?? null,
                'jenis' => $previous ? 'perpanjangan' : 'baru',
                'nib' => $previous->nib ?? $lastNib,
                'berlaku_mulai' => $previous && $previous->effective_end ? $previous->effective_end->copy()->addDay() : null,
            ]),
            'previous' => $previous,
            'tenants' => Tenant::orderBy('app_name')->get(['id', 'app_name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAgreement($request, true);
        $previous = !empty($data['previous_id']) ? PksAgreement::findOrFail($data['previous_id']) : null;
        if ($previous && $previous->tenant_id !== (int) $data['tenant_id']) {
            return back()->withInput()->withErrors(['previous_id' => 'PKS lama harus milik tenant yang sama.']);
        }

        $agreement = new PksAgreement($data);
        $agreement->jenis = $previous ? 'perpanjangan' : 'baru';
        $agreement->created_by = $this->adminName();
        $this->attachFile($agreement, $request->file('file'), $agreement->tenant_id);
        $agreement->save();

        // Perpanjangan: salin titik layanan/POP dari PKS lama bila diminta.
        if ($previous && $request->boolean('copy_points')) {
            foreach ($previous->servicePoints as $point) {
                $agreement->servicePoints()->create($point->only(['jenis', 'nama', 'kapasitas_mbps', 'lokasi', 'koordinat', 'keterangan']));
            }
        }

        return redirect()->route('admin.pks.show', $agreement->id)->with('success', 'PKS berhasil disimpan.');
    }

    public function show($id)
    {
        $agreement = PksAgreement::with(['tenant', 'addenda', 'servicePoints', 'previous', 'successors'])->findOrFail($id);

        // Riwayat PKS tenant ini (untuk navigasi PKS lama/baru).
        $history = PksAgreement::with(['addenda', 'successors'])
            ->where('tenant_id', $agreement->tenant_id)
            ->orderByDesc('berlaku_mulai')
            ->get();

        return view('admin.pks.show', compact('agreement', 'history'));
    }

    public function edit($id)
    {
        $agreement = PksAgreement::findOrFail($id);

        return view('admin.pks.form', [
            'agreement' => $agreement,
            'previous' => $agreement->previous,
            'tenants' => Tenant::orderBy('app_name')->get(['id', 'app_name']),
        ]);
    }

    public function update(Request $request, $id)
    {
        $agreement = PksAgreement::findOrFail($id);
        $data = $this->validateAgreement($request, false);
        unset($data['tenant_id'], $data['previous_id']); // tenant & rantai perpanjangan tidak diubah lewat edit

        $agreement->fill($data);
        $agreement->updated_by = $this->adminName();
        if ($request->hasFile('file')) {
            $this->attachFile($agreement, $request->file('file'), $agreement->tenant_id);
        }
        $agreement->save();

        return redirect()->route('admin.pks.show', $agreement->id)->with('success', 'PKS berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $agreement = PksAgreement::findOrFail($id);
        $tenantId = $agreement->tenant_id;
        $agreement->delete(); // soft delete — file tetap disimpan

        return redirect()->route('admin.pks.index', ['tenant' => $tenantId])->with('success', 'PKS ' . $agreement->nomor_pks . ' dihapus.');
    }

    public function storeAddendum(Request $request, $id)
    {
        $agreement = PksAgreement::findOrFail($id);
        $data = $request->validate([
            'nomor' => 'required|string|max:150',
            'tanggal' => 'nullable|date',
            'berlaku_sampai_baru' => 'nullable|date|after_or_equal:' . $agreement->berlaku_mulai->format('Y-m-d'),
            'keterangan' => 'nullable|string|max:5000',
            'file' => 'nullable|' . self::FILE_RULE,
        ]);

        $addendum = new PksAddendum($data);
        $addendum->pks_id = $agreement->id;
        $addendum->created_by = $this->adminName();
        $this->attachFile($addendum, $request->file('file'), $agreement->tenant_id);
        $addendum->save();

        return redirect()->route('admin.pks.show', $agreement->id)->with('success', 'Adendum ' . $addendum->nomor . ' ditambahkan.');
    }

    public function destroyAddendum($id, $addendumId)
    {
        $addendum = PksAddendum::where('pks_id', $id)->findOrFail($addendumId);
        $addendum->delete();

        return redirect()->route('admin.pks.show', $id)->with('success', 'Adendum ' . $addendum->nomor . ' dihapus.');
    }

    public function storePoint(Request $request, $id)
    {
        $agreement = PksAgreement::findOrFail($id);
        $agreement->servicePoints()->create($this->validatePoint($request));

        return redirect()->route('admin.pks.show', $agreement->id)->with('success', 'Titik layanan/POP ditambahkan.');
    }

    public function updatePoint(Request $request, $id, $pointId)
    {
        $point = PksServicePoint::where('pks_id', $id)->findOrFail($pointId);
        $point->update($this->validatePoint($request));

        return redirect()->route('admin.pks.show', $id)->with('success', 'Titik layanan/POP ' . $point->nama . ' diperbarui.');
    }

    public function destroyPoint($id, $pointId)
    {
        $point = PksServicePoint::where('pks_id', $id)->findOrFail($pointId);
        $point->delete();

        return redirect()->route('admin.pks.show', $id)->with('success', 'Titik layanan/POP ' . $point->nama . ' dihapus.');
    }

    /** Unduh dokumen: $type = pks | adendum. */
    public function download($type, $fileId)
    {
        $doc = $type === 'adendum' ? PksAddendum::findOrFail($fileId) : PksAgreement::findOrFail($fileId);

        return self::fileResponse($doc);
    }

    // ------------------------------------------------------------------ helpers

    /** Dipakai juga oleh IspPksController (tampilan tenant). */
    public static function fileResponse($doc)
    {
        if (!$doc->file_path || !Storage::disk('local')->exists($doc->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        // Tampilkan inline (PDF/gambar terbuka di browser), nama file asli.
        return Storage::disk('local')->response($doc->file_path, $doc->file_name, [], 'inline');
    }

    private function validateAgreement(Request $request, bool $creating): array
    {
        return $request->validate([
            'tenant_id' => ($creating ? 'required' : 'nullable') . '|integer|exists:isp_master.tenants,id',
            'previous_id' => 'nullable|integer',
            'nomor_pks' => 'required|string|max:150',
            'nib' => 'nullable|string|max:50',
            'tanggal_pks' => 'nullable|date',
            'berlaku_mulai' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_mulai',
            'keterangan' => 'nullable|string|max:5000',
            'file' => ($creating ? 'required' : 'nullable') . '|' . self::FILE_RULE,
        ], [
            'file.required' => 'File PKS wajib diunggah.',
            'file.mimes' => 'File harus PDF, JPG, PNG, DOC, atau DOCX.',
            'file.max' => 'Ukuran file maksimal 20 MB.',
            'berlaku_sampai.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai berlaku.',
        ]);
    }

    private function validatePoint(Request $request): array
    {
        $data = $request->validate([
            'jenis' => 'required|in:' . implode(',', array_keys(PksServicePoint::JENIS)),
            'nama' => 'required|string|max:150',
            'kapasitas_mbps' => 'required|integer|min:0|max:10000000',
            'lokasi' => 'nullable|string|max:255',
            'koordinat' => ['nullable', 'string', 'max:60', function ($attr, $value, $fail) {
                if (!(new PksServicePoint(['koordinat' => $value]))->lat_lng) {
                    $fail('Koordinat harus berformat "lat,lng", contoh -8.4095,115.1889.');
                }
            }],
            'keterangan' => 'nullable|string|max:2000',
        ]);

        // Simpan dalam bentuk baku "lat,lng" (tanpa spasi).
        $latLng = (new PksServicePoint(['koordinat' => $data['koordinat'] ?? null]))->lat_lng;
        $data['koordinat'] = $latLng ? $latLng[0] . ',' . $latLng[1] : null;

        return $data;
    }

    private function attachFile($model, ?UploadedFile $file, $tenantId): void
    {
        if (!$file) {
            return;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $model->file_path = $file->storeAs('pks/' . (int) $tenantId, Str::uuid() . '.' . $ext, 'local');
        $model->file_name = Str::limit($file->getClientOriginalName(), 250, '');
        $model->file_size = $file->getSize();
    }

    private function adminName(): ?string
    {
        $admin = auth('admin')->user();

        return $admin->name ?? $admin->email ?? null;
    }
}
