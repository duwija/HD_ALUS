<?php

namespace Database\Seeders;

use App\PaymentGateway;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Seed default payment gateway providers for the current database connection.
     */
    public function run(): void
    {
        if (!Schema::hasTable('payment_gateways')) {
            $this->command?->warn('PaymentGatewaySeeder skipped: table `payment_gateways` not found.');
            return;
        }

        $defaults = PaymentGateway::defaultProviders();

        foreach ($defaults as $def) {
            $exists = DB::table('payment_gateways')
                ->where('provider', $def['provider'])
                ->first();

            $payload = [
                'label'      => $def['label'],
                'icon'       => $def['icon'],
                'sort_order' => $def['sort_order'],
                'settings'   => json_encode($def['settings'] ?? []),
                'updated_at' => now(),
            ];

            if ($exists) {
                DB::table('payment_gateways')
                    ->where('provider', $def['provider'])
                    ->update($payload);
            } else {
                DB::table('payment_gateways')->insert(array_merge($payload, [
                    'domain'     => null,
                    'provider'   => $def['provider'],
                    'enabled'    => $def['enabled'] ?? 1,
                    'fee_type'   => 'none',
                    'fee_amount' => 0,
                    'fee_label'  => 'Biaya Transaksi',
                    'created_at' => now(),
                ]));
            }
        }

        $this->command?->info('Payment gateway defaults seeded successfully.');
    }
}
