<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Services\FtpService;

class FtpTest extends TestCase
{

    public function testFtpController()
    {
        $ftpController = new FtpService();

        // test connection
        $this->assertTrue($ftpController->connect());

        // test file listing
        $fileList = $ftpController->getFileList();
        $this->assertTrue(is_array($fileList));

        // test file downloading
        $testfile = $ftpController->downloadFile($fileList[0]);
        $this->assertTrue(file_exists($testfile));
        unlink($testfile);
    }

}
