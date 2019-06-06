<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Organisation extends Model
{
    public function users() {
        return $this->hasMany('App\User');
    }

    public function menus() {
        return $this->hasMany('App\Menu');
    }

    public function foods() {
        return $this->morphMany('App\Food', 'owner');
    }
}
