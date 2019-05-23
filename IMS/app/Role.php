<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public function site() {
        return $this->belongsTo('App\Site');
    }

    public function users() {
        return $this->belongsToMany('App\User');
    }

    public function storagePermissions() {
        return $this->morphMany('App\StoragePermission', 'permissible');
    }
}
