<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnSuccessToExportModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('exports', function($table) {
            $table->datetime('finished_at')->nullable();
        });

        App\Models\Export::all()->each(function($export) {
            $export->finished_at = $export->updated_at;
            $export->save();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('exports', function($table) {
            $table->dropColumn('finished_at');
        });
    }
}
