<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLeadFieldsToCustomers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            // Lead Management Fields
            $table->string('lead_source', 50)->nullable()->after('id_merchant')->comment('WA, Phone, Email, Walk-in, Referral, Social Media, Website');
            $table->text('lead_notes')->nullable()->after('lead_source')->comment('Catatan sales untuk follow-up');
            $table->date('expected_close_date')->nullable()->after('lead_notes')->comment('Target tanggal closing');
            $table->integer('conversion_probability')->nullable()->default(0)->after('expected_close_date')->comment('Persentase kemungkinan convert (0-100%)');
            $table->timestamp('converted_at')->nullable()->after('conversion_probability')->comment('Tanggal convert dari Potensial ke Active');
            $table->bigInteger('converted_by')->unsigned()->nullable()->after('converted_at')->comment('User ID yang convert');
            
            // Index untuk performance
            $table->index('lead_source');
            $table->index('expected_close_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['lead_source']);
            $table->dropIndex(['expected_close_date']);
            
            $table->dropColumn([
                'lead_source',
                'lead_notes',
                'expected_close_date',
                'conversion_probability',
                'converted_at',
                'converted_by'
            ]);
        });
    }
}
