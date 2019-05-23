<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StoragePermission extends Model
{
    use SoftDeletes;
    
    public function target() {
        return $this->morphTo();
    }

    public function permissible() {
        return $this->morphTo();
    }
}
