<?php

namespace App\Models;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\FtpController,
    App\Http\Controllers\Controller,
    App\Http\Controllers\ZipController;

class Update extends Model
{

    const FILE_SUFFIX = '.XML';
    const COMPRESSED_FILE_SUFFIX = '.zip';

    protected $table = 'libri_updates';
}
