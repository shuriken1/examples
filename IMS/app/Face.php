<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Face extends Model
{
    public function user() {
        return $this->belongsTo('App\User');
    }

    public function file() {
        return $this->belongsTo('App\File');
    }

    public function verifier() {
        return $this->belongsTo('App\User');
    }
}
