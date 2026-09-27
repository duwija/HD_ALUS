<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenants a supervisor admin user is allowed to access.
 * tenant_id is a plain integer (no FK) because tenants live in the
 * separate 'isp_master' connection/database, not 'admin'.
 */
class CreateAdminUserTenantsTable extends Migration
{
    public function up()
    {
        if (Schema::connection('admin')->hasTable('admin_user_tenants')) {
            return;
        }

        Schema::connection('admin')->create('admin_user_tenants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            $table->unsignedBigInteger('tenant_id');
            $table->timestamps();

            $table->foreign('admin_user_id')->references('id')->on('admin_users')->cascadeOnDelete();
            $table->unique(['admin_user_id', 'tenant_id']);
        });
    }

    public function down()
    {
        Schema::connection('admin')->dropIfExists('admin_user_tenants');
    }
}
