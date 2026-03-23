<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppCustomerNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('app_customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('type')->default('info')->comment('new_invoice, reminder_invoice, ticket_update, info');
            $table->string('open_url')->nullable()->comment('URL path untuk dibuka di app');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_read']);
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_customer_notifications');
    }
}
