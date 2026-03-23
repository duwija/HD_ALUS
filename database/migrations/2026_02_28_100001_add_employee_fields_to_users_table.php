<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmployeeFieldsToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // join_date, job_title sudah ada di tabel — hanya tambah kolom yang belum ada
            if (!Schema::hasColumn('users', 'supervisor_id')) {
                $table->unsignedBigInteger('supervisor_id')->nullable()->after('phone')
                      ->comment('ID supervisor dari tabel users sendiri');
            }
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id', 50)->nullable()->unique()->after('supervisor_id')
                      ->comment('NIK / nomor karyawan');
            }
            if (!Schema::hasColumn('users', 'is_active_employee')) {
                $table->boolean('is_active_employee')->default(true)->after('employee_id');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = ['supervisor_id','employee_id','is_active_employee'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
