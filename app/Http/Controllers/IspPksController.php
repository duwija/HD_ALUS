<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\PksController;
use App\PksAddendum;
use App\PksAgreement;

// Menu ISP > PKS: daftar PKS milik tenant yang sedang login (read-only).
// Data dikelola superadmin di /admin/pks (database master).
class IspPksController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('checkPrivilege:admin,accounting');
    }

    public function index()
    {
        $agreements = PksAgreement::with(['addenda', 'servicePoints', 'successors', 'previous'])
            ->where('tenant_id', $this->tenantId())
            ->orderByDesc('berlaku_mulai')
            ->get();

        return view('isp.pks', ['agreements' => $agreements]);
    }

    /** Unduh dokumen PKS/adendum — hanya milik tenant ini. */
    public function download($type, $fileId)
    {
        $doc = $type === 'adendum'
            ? PksAddendum::whereHas('agreement', fn($q) => $q->where('tenant_id', $this->tenantId()))->findOrFail($fileId)
            : PksAgreement::where('tenant_id', $this->tenantId())->findOrFail($fileId);

        return PksController::fileResponse($doc);
    }

    private function tenantId(): int
    {
        $id = (int) tenant_id();
        abort_if($id <= 0, 404);

        return $id;
    }
}
