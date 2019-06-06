<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Allergen;
use App\User;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $currentUser = Auth::user();
        $user = User::find($id);

        if(!$currentUser->can('update', $user)) {
            return redirect('/home')->withErrors('You cannot edit that user.');
        }

        $knownAllergens = Allergen::all();
        $allergies = $user->allergies;

        return view('user.edit', compact('user', 'knownAllergens', 'allergies'));
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
        $currentUser = Auth::user();
        $user = User::find($id);

        if(!$currentUser->can('update', $user)) {
            return redirect('/home')->withErrors('You cannot edit that user.');
        }

        $request->validate([
            'name'=>'required',
            'email'=>'required|email'
        ]);

        $user = User::find($id);
        $user->name = $request->get('name');
        $user->email = $request->get('email');
        
        $user->save();

        $allergiesToSync = array();
        foreach($request->get('selection') as $allergenID => $selection) {
            if($selection == "none") {
                continue;
            }

            $amount = substr($selection, 1, 2);
            if(substr($selection, 0, 1) == "p") {
                $allergiesToSync[$allergenID] = array('allergy_amount' => null, 'preference_amount' => $amount);
            } elseif(substr($selection, 0, 1) == "a") {
                $allergiesToSync[$allergenID] = array('allergy_amount' => $amount, 'preference_amount' => null);
            }
        }

        $user->allergies()->sync($allergiesToSync);

        return redirect('/user/'.$id.'/edit')->withSuccess('Allergies updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
