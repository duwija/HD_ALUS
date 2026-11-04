<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Index untuk kolom yang sering dicari tapi belum ber-index (DB tenant).
 * Tanpa index ini, halaman /suminvoice/transaction dan lookup jurnal per invoice
 * (reff = tempcode . 'receive') membaca seluruh tabel.
 * Dibuat online (LOCK=NONE) agar tabel tetap bisa dibaca/ditulis selama proses.
 */
class AddPerformanceIndexes extends Migration
{
    private $indexes = [
        'jurnals'     => ['idx_jurnals_reff' => 'reff'],
        'invoices'    => ['idx_invoices_tempcode' => 'tempcode'],
        'suminvoices' => [
            'idx_suminvoices_tempcode'        => 'tempcode',
            'idx_suminvoices_number'          => 'number',
            'idx_suminvoices_customer_status' => 'id_customer, payment_status',
            'idx_suminvoices_status_paydate'  => 'payment_status, payment_date',
        ],
    ];

    public function up()
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (!$this->hasIndex($table, $name)) {
                    DB::statement("ALTER TABLE `$table` ADD INDEX `$name` ($columns), ALGORITHM=INPLACE, LOCK=NONE");
                }
            }
        }
    }

    public function down()
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if ($this->hasIndex($table, $name)) {
                    DB::statement("ALTER TABLE `$table` DROP INDEX `$name`");
                }
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return count(DB::select("SHOW INDEX FROM `$table` WHERE Key_name = ?", [$name])) > 0;
    }
}
