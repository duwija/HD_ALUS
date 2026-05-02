<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCustomerTagDefinitionsAndMapTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_tag_definitions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('customer_tag_map', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('customer_tag_id');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['customer_id', 'customer_tag_id']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('customer_tag_id')->references('id')->on('customer_tag_definitions')->onDelete('cascade');
        });

        // Migrate existing customer tags from old pivot (customer_tags -> tags)
        if (Schema::hasTable('customer_tags') && Schema::hasTable('tags')) {
            $rows = DB::table('customer_tags as ct')
                ->join('tags as t', 't.id', '=', 'ct.tag_id')
                ->select('ct.customer_id', 't.name')
                ->distinct()
                ->get();

            if ($rows->isNotEmpty()) {
                // Create definitions first
                $names = $rows->pluck('name')->filter()->unique()->values();
                foreach ($names as $name) {
                    DB::table('customer_tag_definitions')->updateOrInsert(
                        ['name' => $name],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }

                $defs = DB::table('customer_tag_definitions')->pluck('id', 'name');
                $mapRows = [];
                foreach ($rows as $row) {
                    if (!isset($defs[$row->name])) {
                        continue;
                    }
                    $mapRows[] = [
                        'customer_id' => $row->customer_id,
                        'customer_tag_id' => $defs[$row->name],
                        'created_at' => now(),
                    ];
                }

                if (!empty($mapRows)) {
                    DB::table('customer_tag_map')->insertOrIgnore($mapRows);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_tag_map');
        Schema::dropIfExists('customer_tag_definitions');
    }
}
