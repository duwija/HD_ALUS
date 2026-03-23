<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * PaymentGatewaySeeder
 *
 * Seed tabel payment_gateways di DB tenant yang sedang aktif.
 * Setiap tenant punya DB sendiri, jadi jalankan di context tenant yang benar.
 *
 * Cara jalankan:
 *   php artisan db:seed --class=PaymentGatewaySeeder
 *
 * Aman dijalankan berkali-kali (upsert / skip jika sudah ada).
 *
 * Untuk menambah provider baru ke tenant lama:
 *   Tambah entry di PaymentGateway::defaultProviders() lalu jalankan seeder ini.
 */
class PaymentGatewaySeeder extends Seeder
{
    public function run()
    {
        $defaults = \App\PaymentGateway::defaultProviders();

        foreach ($defaults as $def) {
            $exists = DB::table('payment_gateways')->where('provider', $def['provider'])->exists();

            if ($exists) {
                // Update hanya label, icon, sort_order, settings — JANGAN overwrite enabled/fee
                // yang sudah dikustomisasi tenant
                DB::table('payment_gateways')
                    ->where('provider', $def['provider'])
                    ->update([
                        'label'      => $def['label'],
                        'icon'       => $def['icon'],
                        'sort_order' => $def['sort_order'],
                        'settings'   => json_encode($def['settings']),
                        'updated_at' => now(),
                    ]);
            } else {
                // Insert baru — pakai enabled dari definition (default bisa 0 untuk provider baru)
                DB::table('payment_gateways')->insert([
                    'provider'   => $def['provider'],
                    'label'      => $def['label'],
                    'icon'       => $def['icon'],
                    'enabled'    => $def['enabled'] ?? 1,
                    'fee_type'   => 'none',
                    'fee_amount' => 0,
                    'fee_label'  => 'Biaya Transaksi',
                    'sort_order' => $def['sort_order'],
                    'settings'   => json_encode($def['settings']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info('  + Provider baru ditambah: ' . $def['provider']);
            }
        }

        $this->command->info('PaymentGatewaySeeder: ' . count($defaults) . ' provider di-seed.');
    }
}
