<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Organisation;
use App\Food;

class OrganisationsController extends Controller
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
     * Display the specified resource's foods.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showFoods($id)
    {
        $org = Organisation::find($id);
        if(!Auth::user()->can('view', $org)) {
            return redirect('/home')->withErrors('You cannot view that organisation.');
        }

        $foods = $org->foods;
        return view('org.foods', compact('org', 'foods'));
    }

    /**
     * Display the specified resource's menus.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showMenus($id)
    {
        //
    }

    /**
     * Display the specified resource's users.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showUsers($id)
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
        //
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
        //
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
