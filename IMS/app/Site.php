<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use SoftDeletes;
    
    public function users() {
        //return $this->hasManyThrough('App\User', 'App\Role'); // Probablyn this once multiple sites can be accomodated.
        return $this->hasMany('App\User');
    }

    public function roles() {
        return $this->hasMany('App\Role');
    }
    
    public function folders() {
        return $this->hasMany('App\Folder');
    }

    public function files() {
        return $this->hasManyThrough('App\File', 'App\Folder');
    }
}
