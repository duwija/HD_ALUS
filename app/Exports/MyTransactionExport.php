<?php

namespace App\Exports;

use App\Suminvoice;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class MyTransactionExport implements FromView
{
    protected $date_from;
    protected $date_end;

    public function __construct($date_from = null, $date_end = null)
    {
        $this->date_from = $date_from ? Carbon::parse($date_from)->startOfDay() : Carbon::now()->startOfMonth();
        $this->date_end = $date_end ? Carbon::parse($date_end)->endOfDay() : Carbon::now()->endOfDay();
    }

    public function view(): View
    {
        $data = Suminvoice::with(['user', 'customer', 'kasbank'])
        ->where('updated_by', auth()->id())
        ->whereBetween('payment_date', [$this->date_from, $this->date_end])
        ->orderBy('payment_date', 'ASC')
        ->get();

        return view('payment.export-excel', [
            'data' => $data,
            'date_from' => $this->date_from,
            'date_end' => $this->date_end,
        ]);
    }
}
