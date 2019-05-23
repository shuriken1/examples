<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'surname', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function site() {
        return $this->belongsTo('App\Site');
    }

    public function roles() {
        return $this->belongsToMany('App\Role');
    }
    
    public function filesOwned() {
        return $this->hasMany('App\File');
    }

    public function filesTaggedIn() {
        return $this->belongsToMany('App\File');
    }

    public function usagePermissions() {
        return $this->morphToMany('App\UsagePermission', 'permissible');
    }

    public function storagePermissions() {
        return $this->morphMany('App\StoragePermission', 'permissible');
    }

    public function storagePermissionsAsTarget() {
        return $this->morphMany('App\StoragePermission', 'target');
    }

    public function faces() {
        return $this->hasMany('App\Face');
    }
}
