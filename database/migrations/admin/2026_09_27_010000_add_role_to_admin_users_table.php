<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoleToAdminUsersTable extends Migration
{
    public function up()
    {
        if (!Schema::connection('admin')->hasColumn('admin_users', 'role')) {
            Schema::connection('admin')->table('admin_users', function (Blueprint $table) {
                $table->string('role')->default('super_admin')->after('is_active');
            });
        }
    }

    public function down()
    {
        if (Schema::connection('admin')->hasColumn('admin_users', 'role')) {
            Schema::connection('admin')->table('admin_users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
}
