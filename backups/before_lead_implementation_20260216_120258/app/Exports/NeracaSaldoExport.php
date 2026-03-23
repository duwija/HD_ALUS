<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class NeracaSaldoExport implements FromView
{
    public function __construct(
        public array $grouped,
        public array $grand,
        public string $tanggalAwal,
        public string $tanggalAkhir
    ) {}

    public function view(): View
    {
        return view('jurnal.neraca_saldo_export', [
            'grouped'      => $this->grouped,
            'grand'        => $this->grand,
            'tanggalAwal'  => $this->tanggalAwal,
            'tanggalAkhir' => $this->tanggalAkhir,
        ]);
    }
}
