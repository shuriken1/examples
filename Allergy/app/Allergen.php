<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Allergen extends Model
{
    public function foods() {
        return $this->belongsToMany('App\Food');
    }

    /*public function allergies() {
        return $this->hasMany('App\Allergy');
    }*/

    public function users() {
        return $this->belongsToMany('App\User')->withPivot('allergy_amount', 'preference_amount');
    }
}
