<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Download extends Model
{
    protected $primaryKey = 'remote_filepath';
    protected $fillable = array('remote_filepath');
}
