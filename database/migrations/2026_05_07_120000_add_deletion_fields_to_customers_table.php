<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'deletion_type')) {
                $table->string('deletion_type', 50)->nullable()->after('lost_by');
            }

            if (!Schema::hasColumn('customers', 'deletion_reason')) {
                $table->text('deletion_reason')->nullable()->after('deletion_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'deletion_reason')) {
                $table->dropColumn('deletion_reason');
            }

            if (Schema::hasColumn('customers', 'deletion_type')) {
                $table->dropColumn('deletion_type');
            }
        });
    }
};
