<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Gate;
use App\Food;
use App\User;
use App\Allergen;
use App\Link;

class FoodsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $foods = Food::all();
        return view('food.index', compact('foods'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if(!Auth::user()->can('createPrivate', 'App\Food')) {
            return redirect('/home')->withErrors('You cannot create new foods.');
        }

        $allergens = Allergen::all();
        return view('food.create')->with('allergens', $allergens);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if(!Auth::user()->can('createPrivate', 'App\Food')) {
            return redirect('/home')->withErrors('You cannot create new foods.');
        }

        $request->validate([
            'name'=>'required',
            'barcode'=>'nullable|numeric'
        ]);

        // Barcode and Public disabled.
        $food = new Food([
            'barcode' => null,
            'public' => 0,
            'name' => $request->get('name'),
            'description'=> $request->get('description')
        ]);
        
        $food->owner()->associate($user->org);

        $food->save();

        foreach($request->get('allergens') as $id => $amount) {
            if($amount == "unknown") {
                continue;
            }

            $food->allergens()->attach($id, ['food_amount' => $amount]);
        }

        return redirect('org/'.$user->org->id.'/foods')->withSuccess('\''.$food->name.'\' has been created succesfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $food = Food::find($id);
        $knownAllergens = Allergen::all();
        $directAllergens = $food->allergens;
        $ingredientAllergens = $food->ingredientAllergens();
        $mergedAllergens = Food::mergeAllergens($directAllergens, $ingredientAllergens);

        return view('food.show', compact('food', 'knownAllergens', 'mergedAllergens'));
    }

    /**
     * Display the specified resource to an admin.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function manage($id)
    {
        // DEBUG Logic to make sure user has permission

        $food = Food::find($id);
        $knownAllergens = Allergen::all();
        $directAllergens = $food->allergens;
        $ingredientAllergens = $food->ingredientAllergens();
        $ingredientAllergensMerged = Food::mergeAllergens($ingredientAllergens);
        $mergedAllergens = Food::mergeAllergens($directAllergens, $ingredientAllergens);

        return view('food.manage', compact('food', 'knownAllergens', 'directAllergens', 'ingredientAllergensMerged', 'mergedAllergens'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $food = Food::find($id);

        if(!Auth::user()->can('update', $food)) {
            return redirect('/home')->withErrors('You cannot edit that food.');
        }

        $knownAllergens = Allergen::all();
        $allergens = $food->allergens;
        
        return view('food.edit', compact('food', 'knownAllergens', 'allergens'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $food = Food::find($id);

        if(!Auth::user()->can('update', $food)) {
            return redirect('/home')->withErrors('You cannot edit that food.');
        }
        
        $request->validate([
            'name'=>'required',
            'barcode'=>'nullable|numeric'
        ]);

        $food->barcode = $request->get('barcode');
        $food->name = $request->get('name');
        $food->description = $request->get('description');
        
        $food->save();

        $allergensToSync = array();
        foreach($request->get('allergens') as $allergenID => $amount) {
            if($amount == "unknown") {
                continue;
            }

            $allergensToSync[$allergenID] = array('food_amount' => $amount);
        }

        $food->allergens()->sync($allergensToSync);

        return redirect()->back()->withSuccess('Food item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $food = Food::find($id);

        if(!Auth::user()->can('delete', $food)) {
            return redirect('/home')->withErrors('You cannot delete that food.');
        }

        $food->delete();

        return redirect()->back()->withSuccess('Food item has been deleted successfully.');
    }

    /**
     * Compare food allergens against user allergies and report.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function check(Request $request, $id)
    {
        $food = Food::find($id);
        $knownAllergens = Allergen::all();
        $directAllergens = $food->allergens;
        $ingredientAllergens = $food->ingredientAllergens();
        $mergedAllergens = Food::mergeAllergens($directAllergens, $ingredientAllergens);

        if(Auth::check()) {
            $user = Auth::user();
        } else {
            return redirect('/food/'.$food->id);
        }

        $warningLevel = 0;
        $allergyBlocks = collect();
        $allergyWarnings = collect();
        $prefWarnings = collect();
        $unknownAllergyWarnings = collect();
        $unknownPrefWarnings = collect();

        foreach($mergedAllergens as $allergen) {
            $allergy = $user->allergies->firstWhere('id', $allergen['id']);
            if(!$allergy) {
                continue;
            }

            $userAllergyAmount = $allergy->pivot->allergy_amount;
            $userPrefAmount = $allergy->pivot->preference_amount;
            $foodAmount = $allergen['amount'];

            if(is_null($allergen['amount'])) {
                if(!is_null($userAllergyAmount)) {
                    $unknownAllergyWarnings->push($allergen);
                    $warningLevel = max($warningLevel, 2);
                } else {
                    $unknownPrefWarnings->push($allergen);
                    $warningLevel = max($warningLevel, 1);
                }
                continue;
            }

            //If allergy amount is set and there is more or equal in the food.
            if(!is_null($userAllergyAmount) AND $foodAmount >= $userAllergyAmount) {
                //echo"Allergy block set by ".$allergen->name."<br>\n";
                $allergyBlocks->push($allergen);
                $warningLevel = 3;
            }
            
            // If food contains any amount of allergen and allergy ammount > food amount.
            if($foodAmount > 0 AND $userAllergyAmount > $foodAmount) {
                //echo"Allergy warning set by ".$allergy->name."<br>\n";
                $allergyWarnings->push($allergen);
                $warningLevel = max($warningLevel, 2);
            }

            // If a preference is set and there is more in the food.
            if(!is_null($userPrefAmount) AND $foodAmount > $userPrefAmount) {
                //echo"Preference warning set by ".$allergy->name."<br>\n";
                $prefWarnings->push($allergen);
                $warningLevel = max($warningLevel, 1);
            }
        }

        return view('food.check', compact('food', 'knownAllergens', 'mergedAllergens', 'warningLevel', 'allergyBlocks', 'allergyWarnings', 'prefWarnings', 'unknownAllergyWarnings', 'unknownPrefWarnings'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function links($id)
    {
        $food = Food::find($id);

        if(!Auth::user()->can('update', $food)) {
            return redirect()->back()->withErrors('You cannot view that food\'s links.');
        }

        $links = $food->links->sortByDesc('expires_at')->sortByDesc('isActive');
        
        return view('food.links', compact('food', 'links'));
    }
}
