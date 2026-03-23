<?php
// database/migrations/2026_02_18_000002_add_workflow_stage_id_to_customers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('workflow_stage_id')->nullable()->default(1)->after('id_status');
            $table->foreign('workflow_stage_id')->references('id')->on('lead_workflows');
        });
    }
    public function down() {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['workflow_stage_id']);
            $table->dropColumn('workflow_stage_id');
        });
    }
};
