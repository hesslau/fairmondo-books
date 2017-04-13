<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\ExportController;
use FairmondoProductTest;

class ExportControllerTest extends TestCase
{
    public function testExport()
    {
        // test export
        $exportFile = ExportController::makeDelta(null, true);

        // asser that export file has been written
        $this->assertTrue(file_exists($exportFile));

        // delete file
        unlink($exportFile);
    }
}
