<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    public function scopeLatest($query) {
        $query->orderBy('created_at','desc')->take(1);
    }

    // An Export is complete when 'number_of_products' is not null
    public function scopeCompleted($query) {
        $query->whereNotNull('number_of_products');
    }
}
