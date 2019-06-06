<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use App\Link;
use App\Food;
use App\Menu;

class LinksController extends Controller
{
    /**
     * Index of links can't be viewed.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return redirect('/home');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $linkableType = $request->type;
        $linkableID = $request->id;
        $redirectTo = $request->redirectTo ?: 'back';
        $request->session()->put('redirectTo', $redirectTo);
        
        return view('link.create', compact('linkableType', 'linkableID'));
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

        $request->validate([
            'expiry'=>'nullable|date',
            'linkable_type' => ["required" , "in:food,menu"],
            'linkable_id' => 'required|numeric'
        ]);

        if($request->linkable_type == "food") {
            $linkable = Food::find($request->linkable_id);
        } elseif($request->linkable_type == "menu") {
            $linkable = Menu::find($request->linkable_id);
        }

        $code = substr(md5(mt_rand(10000, 10000000)),0, 6);
        while(strlen($code) != 6 OR Link::where('code', $code)->exists() != null) {
            $code = substr(md5(mt_rand(10000, 10000000)),0, 6);
        }

        $link = new Link([
            'code' => $code,
            'expires_at' => $request->get('expiry'),
        ]);
        $link->user()->associate($user);
        $link->linkable()->associate($linkable);
        $link->save();

        $link->generateQR();

        if($request->session()->has('redirectTo')) {
            list($route, $id) = explode("|", $request->session()->get('redirectTo'));
            $request->session()->forget('redirectTo');
            return redirect()->route($route, ['id' => $id])->withSuccess('Link updated successfully.');
        } else {
            return redirect()->back()->withSuccess('Link updated successfully.');
        }
    }

    /**
     * Display the specified resource.
     * This is the route the QR sends user to after scanning.
     * 
     * @param  string  $code
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        // Is the code valid?
        $link = Link::whereCode($code)->first();
        
        if(!$link) {
            abort(404);
        }

        // Has the link expired?
        $qrPath = $link->id.'.png';
        if(!$link->isActive) {
            abort(410, "Link expired.");
        }

        if($link->linkable_type == "App\Food") {
            return redirect('/food/'.$link->linkable->id."/check");
        } elseif($link->linkable_type == "App\Menu") {
            return redirect('/menu/'.$link->linkable->id);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $link = Link::find($id);
        $redirectTo = $request->redirectTo ?: 'back';
        $request->session()->put('redirectTo', $redirectTo);
        
        return view('link.edit', compact('link'));
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
        $user = Auth::user();

        $request->validate([
            'expiry'=>'nullable|date'
        ]);

        $link = Link::find($id);
        $link->expires_at = $request->get('expiry');
        $link->save();

        if($request->session()->has('redirectTo')) {
            list($route, $id) = explode("|", $request->session()->get('redirectTo'));
            $request->session()->forget('redirectTo');
            return redirect()->route($route, ['id' => $id])->withSuccess('Link updated successfully.');
        } else {
            return redirect()->back()->withSuccess('Link updated successfully.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $link = Link::find($id);
        $link->delete();

        if(Storage::disk('qr')->exists($link->id)) {
            Storage::disk('qr')->delete($link->id);
        }

        return redirect()->back()->withSuccess('Link has been deleted successfully.');
    }

    /**
     * Display QR code for link.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function viewQR($id) {
        $link = Link::find($id);
        $path = $link->id.".png";

        $exists = Storage::disk('qr')->exists($link->id);

        // If link is valid and no QR exists, create one.
        if(!$exists) {
            $link->generateQR();
        }

        // Get QR code.
        $image = Storage::disk('qr')->get($link->id);
        
        $response = Response::make($image, 200);
        $response->header("Content-Disposition", "inline; filename=".$link->code.".jpg");
        $response->header("Content-Type", "image/png");

        return $response;
    }
}
