<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique();
            $table->string('app_name');
            $table->string('signature');
            $table->string('rescode', 10)->unique();
            
            // Database Configuration
            $table->string('db_host')->default('127.0.0.1');
            $table->string('db_port')->default('3306');
            $table->string('db_database');
            $table->string('db_username');
            $table->string('db_password'); // Will be encrypted
            
            // Mail Configuration
            $table->string('mail_from')->nullable();
            
            // Integration Tokens (encrypted)
            $table->text('whatsapp_token')->nullable();
            $table->text('xendit_key')->nullable();
            
            // Features (JSON)
            $table->json('features')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Metadata
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('domain');
            $table->index('rescode');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tenants');
    }
}
