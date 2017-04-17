<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExtendDownloadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('downloads', function($table) {
            $table->boolean('success')->default(false);
            $table->integer('attempts')->default(0);
        });

        $previousDownloads = App\Models\Download::all();
        foreach ($previousDownloads as $download) {
            $download->success = true;
            $download->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('downloads', function($table) {
            $table->dropColumn('success');
            $table->dropColumn('attempts');
        });
    }
}
