<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateLicensePlansTable extends Migration
{
    public function up()
    {
        if (Schema::connection('isp_master')->hasTable('license_plans')) {
            return;
        }

        Schema::connection('isp_master')->create('license_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        // Starter, Basic, Professional, Enterprise
            $table->integer('max_customers');              // -1 = unlimited
            $table->unsignedBigInteger('price_monthly');   // Harga per bulan (Rp)
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Seed default plans
        DB::connection('isp_master')->table('license_plans')->insert([
            ['name' => 'Trial',        'max_customers' => 10,   'price_monthly' => 0,       'description' => 'Gratis hingga 10 pelanggan',        'sort_order' => 0, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Starter',      'max_customers' => 50,   'price_monthly' => 299000,  'description' => 'Hingga 50 pelanggan aktif',          'sort_order' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Basic',        'max_customers' => 200,  'price_monthly' => 599000,  'description' => 'Hingga 200 pelanggan aktif',         'sort_order' => 2, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Professional', 'max_customers' => 500,  'price_monthly' => 999000,  'description' => 'Hingga 500 pelanggan aktif',         'sort_order' => 3, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Enterprise',   'max_customers' => -1,   'price_monthly' => 1999000, 'description' => 'Pelanggan tidak terbatas (unlimited)', 'sort_order' => 4, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Add license columns to tenants table
        Schema::connection('isp_master')->table('tenants', function (Blueprint $table) {
            $table->foreignId('license_plan_id')->nullable()->after('is_active')
                  ->constrained('license_plans')->nullOnDelete();
            $table->enum('license_status', ['trial', 'active', 'expired', 'suspended'])
                  ->default('trial')->after('license_plan_id');
            $table->date('license_expires_at')->nullable()->after('license_status');
        });
    }

    public function down()
    {
        Schema::connection('isp_master')->table('tenants', function (Blueprint $table) {
            $table->dropForeign(['license_plan_id']);
            $table->dropColumn(['license_plan_id', 'license_status', 'license_expires_at']);
        });

        Schema::connection('isp_master')->dropIfExists('license_plans');
    }
}
