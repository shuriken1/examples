<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Folder extends Model
{
    use SoftDeletes;

    public function site() {
        return $this->belongsTo('App\Site');
    }
    
    public function owner() {
        return $this->belongsTo('App\User');
    }

    public function parent() {
        return $this->belongsTo('App\Folder', 'parent_id');
    }

    public function allParents() {
        return $this->parent->with('allParents');
    }

    public function storagePermissions() {
        return $this->morphMany('App\StoragePermission', 'target');
    }

    public function subfolders() {
        return $this->hasMany(Folder::class, 'parent_id', 'id');
    }

    public function allSubfolders() {
        return $this->subfolders()->with('allSubfolders');
    }

    public function files() {
        return $this->hasMany('App\File');
    }

    public function __construct() {
        //$this->viewable = $this->canView();
    }

    public function getViewableAttribute() {
        return $this->canView();
    }

    public function canView() {
        //echo"<pre>"; print_r($this); echo"</pre>"; exit;
        $roles = Auth::user()->roles->where('site_id', Auth::user()->site->id)->pluck('id')->toArray();
        $permList = array('view', 'child_folder_view');
        $resultantPerms = new StoragePermission;
        
        // Build tree hierarchy from the current folder being tested
        $parents = array($this->id);
        $parentFolder = $this->parent;
        while($parentFolder != null) {
            array_push($parents, $parentFolder->id);
            $parentFolder = Folder::find($parentFolder->parent_id);
        }
        $parents = array_reverse($parents);
        
        //echo"<pre>"; print_r($parents); echo"</pre>"; exit;

        foreach($parents as $folderID) {
            $testFolder = Folder::find($folderID);
            
            // Role-specific folder permissions
            $perms = $testFolder->storagePermissions->where('permissible_type', 'App\Role')->whereIn('permissible_id', $roles);
            foreach($perms as $perm) {
                foreach($permList as $el) {
                    if(isset($perm->$el)) {
                        $resultantPerms->$el = $perm->$el;
                    }
                }
            }

            // User-specific folder permissions
            $perms = $testFolder->storagePermissions->where('permissible_type', 'App\User')->where('permissible_id', Auth::id());
            foreach($perms as $perm) {
                foreach($permList as $el) {
                    if(isset($perm->$el)) {
                        $resultantPerms->$el = $perm->$el;
                    }
                }
            }
        }

        //echo"Folder ID: ".$this->id."<br>\n";
        //echo"<pre>"; print_r($resultantPerms->toArray()); echo"</pre><br><br>";

        /*if($resultantPerms->view == 1 OR ($resultantPerms->view == NULL AND $resultantPerms->child_folder_view == 1)) {
            return true;
        } else {
            return false;
        }*/
        if(($resultantPerms->child_folder_view == 1 AND $resultantPerms->view != 0) OR (!isset($resultantPerms->child_folder_view) AND $resultantPerms->view == 1)) {
            return true;
        } else {
            return false;
        }
    }

    public function getRoot() {
        $rootID = $this->id;
        $parentFolder = $this->parent;
        while($parentFolder != null) {
            $rootID = $parentFolder->id;
            $parentFolder = Folder::find($parentFolder->parent_id);
        }
        //return $rootID;
        return Folder::find($rootID);
    }
}
