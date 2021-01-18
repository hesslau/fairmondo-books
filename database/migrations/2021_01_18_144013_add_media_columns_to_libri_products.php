<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMediaColumnsToLibriProducts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('libri_products', function($table) {
            $table->string('AntCbildUrl')->nullable();
            $table->string('AntAbildUrl')->nullable();
            $table->string('AntRueckUrl')->nullable();
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
            $table->dropColumn('AntCbildUrl');
            $table->dropColumn('AntAbildUrl');
            $table->dropColumn('AntRueckUrl');
        });
    }
}
