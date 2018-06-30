<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Export extends Model
{
    protected $dates = [
        'created_at',
        'updated_at',
        'finished_at'
    ];

    public function scopeLatest($query) {
        $query->orderBy('created_at','desc')->take(1);
    }

    // An Export is complete when 'number_of_products' is not null
    public function scopeCompleted($query) {
        $query->whereNotNull('number_of_products');
    }

    public function hasFinished() {
        return ($this->finished_at != NULL);
    }

    public function isEmpty() {
        return ($this->number_of_products === 0);
    }

    public function hasFailed() {
        return ($this->hasFinished() && $this->number_of_products === NULL);
    }

    public function inProgress() {
        return !$this->hasFinished();
    }

    public function getDuration() {
        return $this->created_at->diffForHumans($this->finished_at, true);
    }
}
