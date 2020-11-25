<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIndexOfProductsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('libri_products', function($table) {
            $table->dropPrimary('ProductReference');
            $table->primary('RecordReference');
        });

        Schema::table('fairmondo_products', function($table) {
            $table->dropPrimary('gtin');
            $table->primary('custom_seller_identifier');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('libri_products', function($table) {
            $table->dropPrimary('RecordReference');
            $table->primary('ProductReference');
        });

        Schema::table('fairmondo_products', function($table) {
            $table->dropPrimary('custom_seller_identifier');
            $table->primary('gtin');
        });
    }
}
