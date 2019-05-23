<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\StoragePermission;

class StoragePermissionsController extends Controller
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
        return view('permissions.storage.create');
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
        $perm = StoragePermission::find($id);
        //echo"<pre>"; print_r($perm->target); echo"</pre>";

        $target = $perm->target;
        if($target instanceOf \App\File) {
            //
        } elseif($target instanceOf \App\Folder) {
            //
        }
        
        $permissible = $perm->permissible;
        if($permissible instanceOf \App\User) {
            //
        } elseif($permissible instanceOf \App\Role) {
            //
        }

        $permList = array('view', 'edit', 'delete',
                          'child_folder_view', 'child_folder_edit', 'child_folder_delete', 'child_folder_new',
                          'child_file_view', 'child_file_edit', 'child_file_delete', 'child_file_new');
        
        return view('perms.storage', compact('perm', 'permList'));
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
