<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Allergen;

class Food extends Model
{
    protected $fillable = [
        'barcode',
        'public',
        'name',
        'description'
    ];

    protected $attributes = [
        'public' => false,
    ];

    function delete() {
        // $this->links->delete() doesn't work because the link needs to be loaded first.
        foreach($this->links as $link) {
            $link->delete();
        }
        parent::delete();
    }
    
    public function owner() {
        return $this->morphTo();
    }

    public function ingredients() {
        return $this->belongsToMany('App\Food', 'ingredients', 'food_id', 'ingredient_id');
    }
    public function ingredientOf() {
        return $this->belongsToMany('App\Food', 'ingredients', 'ingredient_id', 'food_id');
    }

    public function menus() {
        return $this->belongsToMany('App\Menu');
    }
    
    public function allergens() {
        return $this->belongsToMany('App\Allergen')->withPivot('food_amount');
    }

    public function links() {
        return $this->morphMany('App\Link', 'linkable');
    }

    /**
     * Collates all allergens from all ingredients. Does not include direct. Can have duplicates.
     *
     * @return Illuminate\Support\Collection
     */
    public function ingredientAllergens() {
        $ingredientAllergens = collect();

        $food = $this;
        while($food->ingredients->count() > 0) {
            //echo"Food is now ".$food->id.".<br>";
            //echo"Ingredients found.<br>";
            //dd($food->ingredients);
            foreach($food->ingredients as $ingredient) {
                //echo"Parsing ingredient ".$ingredient->id.".<br>";
                $ingredientAllergens = $ingredientAllergens->merge($ingredient->allergens);
                //echo"Setting food to ".$ingredient->id.".<br>";
                $food = $ingredient;
            }
        }

        return $ingredientAllergens;
    }

    /**
     * Combines allergens from direct and ingredients according to amounts.
     *
     * @return array
     */
    public function mergedAllergens() {
        $knownAllergens = Allergen::all();
        $directAllergens = $this->allergens;
        $ingredientAllergens = $this->ingredientAllergens();
        
        $result = array();

        foreach($knownAllergens as $allergen) {
            $result[$allergen->id] = null;
            
            foreach($directAllergens->where('id', $allergen->id) as $presentAllergen) {
                $foodAmount = $presentAllergen->pivot->food_amount;
                if($result[$allergen->id] == null AND $foodAmount == 0) {
                    $result[$allergen->id] = 0;
                } elseif($result[$allergen->id] < $foodAmount AND $foodAmount == 1) {
                    $result[$allergen->id] = 1;
                } elseif($result[$allergen->id] < $foodAmount AND $foodAmount == 2) {
                    $result[$allergen->id] = 2;
                } else {
                    // DEBUG Some kind of error
                }
            }

            foreach($ingredientAllergens->where('id', $allergen->id) as $presentAllergen) {
                $foodAmount = $presentAllergen->pivot->food_amount;
                if($result[$allergen->id] == null AND $foodAmount == 0) {
                    $result[$allergen->id] = 0;
                } elseif($result[$allergen->id] < $foodAmount AND $foodAmount == 1) {
                    $result[$allergen->id] = 1;
                } elseif($result[$allergen->id] < $foodAmount AND $foodAmount == 2) {
                    $result[$allergen->id] = 2;
                } else {
                    // DEBUG Some kind of error
                }
            }
        }

        return $result;
    }

    /**
     * Combines allergens from given allergen collections returning array of allergens with highest amounts.
     *
     * @parameter Illuminate\Support\Collection $givenAllergens
     * @return array
     */
    public static function mergeAllergens() {
        $knownAllergens = Allergen::all();
        $allergenSets = func_get_args();
        //dd($allergenSets);
        $result = collect();

        foreach($knownAllergens as $allergen) {
            //$result[$allergen->id] = null;
            $result->push(collect([
                'id' => $allergen->id,
                'name' => $allergen->name,
                'amount' => null])
            );
        }
        //dd($result);
        foreach($allergenSets as $givenAllergens) {
            foreach($knownAllergens as $allergen) {
                foreach($givenAllergens->where('id', $allergen->id) as $presentAllergen) {
                    $foodAmount = $presentAllergen->pivot->food_amount;

                    //dd($result->firstWhere('id', $allergen->id)['id']);

                    if($result->firstWhere('id', $allergen->id)->get('amount') == null AND $foodAmount == 0) {
                        $result->firstWhere('id', $allergen->id)['amount'] = 0;
                    } elseif($result->firstWhere('id', $allergen->id)->get('amount') < $foodAmount AND $foodAmount == 1) {
                        $result->firstWhere('id', $allergen->id)['amount'] = 1;
                    } elseif($result->firstWhere('id', $allergen->id)->get('amount') < $foodAmount AND $foodAmount == 2) {
                        $result->firstWhere('id', $allergen->id)['amount'] = 2;
                    } else {
                        // DEBUG Some kind of error
                    }
                }
            }
        }

        foreach($result as $key =>$allergen) {
            $result[$key] = $allergen->toArray();
        }
        //dd($result);
        return $result;
    }
}
