<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsagePermission extends Model
{
    use SoftDeletes;
    
    public function site() {
        return $this->hasOne('App\Site');
    }
    
    public function files() {
        return $this->morphedByMany('App\File', 'permissible', 'permission_id');
    }

    public function users() {
        return $this->morphedByMany('App\User', 'permissible', 'permissible_id');
    }
}
