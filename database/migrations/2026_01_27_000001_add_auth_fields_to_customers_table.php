<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthFieldsToCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'portal_password')) {
                $table->string('portal_password', 225)->nullable()->after('ip')->comment('Customer portal login password');
            }

            if (!Schema::hasColumn('customers', 'remember_token')) {
                $table->rememberToken()->after('portal_password');
            }

            if (!Schema::hasColumn('customers', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('customers', 'portal_password')) {
                $columns[] = 'portal_password';
            }

            if (Schema::hasColumn('customers', 'remember_token')) {
                $columns[] = 'remember_token';
            }

            if (Schema::hasColumn('customers', 'last_login_at')) {
                $columns[] = 'last_login_at';
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
}
