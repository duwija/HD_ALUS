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
            $table->string('lead_source', 50)->nullable()->after('id_sale')->comment('Source of lead: WA, Phone, Email, Walk-in, Referral, Social Media, Website, Other');
            $table->text('lead_notes')->nullable()->after('lead_source')->comment('Notes from sales team about lead follow-up');
            $table->date('expected_close_date')->nullable()->after('lead_notes')->comment('Expected date to close the deal');
            $table->integer('conversion_probability')->nullable()->after('expected_close_date')->comment('Probability of conversion (0-100%)');
            $table->timestamp('converted_at')->nullable()->after('conversion_probability')->comment('When lead was converted to active customer');
            $table->unsignedBigInteger('converted_by')->nullable()->after('converted_at')->comment('User ID who converted the lead');
            
            // Add indexes for better query performance
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
