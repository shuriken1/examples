<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Folder;
use App\User;

class PagesController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function index(Request $request) {
        //$currentSiteId = $request->session()->get('currentSiteId', '1'); // DEBUG this needs to check somethign real!
        $root_folder_id = Folder::where('site_id', Auth::user()->site->id)->where('parent_id', NULL)->value('id');
        return redirect()->action('FoldersController@show', ['id' => $root_folder_id]);
    }
}
