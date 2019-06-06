<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    function delete() {
        $this->links()->delete();
        parent::delete();
    }
    
    public function org() {
        return $this->belongsTo('App\Organisation', 'organisation_id');
    }

    public function foods() {
        return $this->belongsToMany('App\Food');
    }

    public function links() {
        return $this->morphMany('App\Link', 'linkable');
    }

    public function activeLink() {
        return $this->links()
            ->where([
                ['expires_at', '<>', null],
                ['expires_at', '>=', date("Y-m-d H:i:s")]
            ])
            ->orderBy('expires_at', 'desc')
            ->first();
    }
}
