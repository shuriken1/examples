<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class File extends Model
{
    use SoftDeletes;
    
    public function folder() {
        return $this->belongsTo('App\Folder');
    }

    public function allParents() {
        return $this->folder->with('allParents');
    }

    public function owner() {
        return $this->belongsTo('App\User');
    }

    public function usagePermissions() {
        return $this->morphToMany('App\UsagePermission', 'permissible', 'permissibles', 'permissible_id', 'permission_id')->withPivot('state');
    }

    public function storagePermissions() {
        return $this->morphMany('App\StoragePermission', 'target'); // ??
    }

    public function usersTagged() { // ??
        return $this->hasManyThrough('App\User', 'App\Face');
    }

    public function faces() {
        return $this->hasMany('App\Face');
    }

    // Not currently used
    /*public function calculateStoragePermissions() {
        $roles = Auth::user()->roles->where('site_id', Auth::user()->site->id)->pluck('id')->toArray();
        $resultantPerms = new StoragePermission;
        $permList = array('view', 'edit', 'delete',
                          'child_folder_view', 'child_folder_edit', 'child_folder_delete', 'child_folder_new',
                          'child_file_view', 'child_file_edit', 'child_file_delete', 'child_file_new');
        
        // Build tree hierarchy
        $parents = array();
        $folder = $this->folder;
        while($folder != null) {
            array_push($parents, $folder->id);
            $folder = Folder::find($folder->parent_id);
        }
        $parents = array_reverse($parents);
        
        foreach($parents as $folderID) {
            $folder = Folder::find($folderID);
            
            // Role-specific folder permissions
            $perms = $folder->storagePermissions->where('permissible_type', 'App\Role')->whereIn('permissible_id', $roles);
            foreach($perms as $perm) {
                foreach($permList as $el) {
                    if(isset($perm->$el)) {
                        $resultantPerms->$el = $perm->$el;
                    }
                }
            }

            // User-specific folder permissions
            $perms = $folder->storagePermissions->where('permissible_type', 'App\User')->where('permissible_id', Auth::id());
            foreach($perms as $perm) {
                foreach($permList as $el) {
                    if(isset($perm->$el)) {
                        $resultantPerms->$el = $perm->$el;
                    }
                }
            }
        }

        // Role-specific file permissions
        $perms = $folder->storagePermissions->where('permissible_type', 'App\Role')->whereIn('permissible_id', $roles);
        foreach($perms as $perm) {
            foreach($permList as $el) {
                if(isset($perm->$el)) {
                    $resultantPerms->$el = $perm->$el;
                }
            }
        }
        
        // User-specific file permissions
        $perms = $this->storagePermissions->where('permissible_type', 'App\User')->where('permissible_id', Auth::id());
        foreach($perms as $perm) {
            foreach($permList as $el) {
                if(isset($perm->$el)) {
                    $resultantPerms->$el = $perm->$el;
                }
            }
        }

        // Tagged user permissions
        $taggedUsers = $this->usersTagged;
        foreach($taggedUsers as $taggedUser) {
            foreach($taggedUser->storagePermissionsAsTarget as $perm) {
                foreach($permList as $el) {
                    if(isset($perm->$el)) {
                        $resultantPerms->$el = $perm->$el;
                    }
                }
            }
        }
        
        return $resultantPerms;
    }*/

    public function getViewableAttribute() {
        return $this->canView();
    }

    public function canView() {
        $roles = Auth::user()->roles->where('site_id', Auth::user()->site->id)->pluck('id')->toArray();
        $resultantPerms = new StoragePermission;
        $folderPermList = array('child_file_view');
        $filePermList = array('view');

        // Build tree hierarchy
        $parents = array();
        $folder = $this->folder;
        while($folder != null) {
            array_push($parents, $folder->id);
            $folder = Folder::find($folder->parent_id);
        }
        $parents = array_reverse($parents);
        
        foreach($parents as $folderID) {
            $folder = Folder::find($folderID);
            
            // Role-specific folder permissions
            $perms = $folder->storagePermissions->where('permissible_type', 'App\Role')->whereIn('permissible_id', $roles);
            foreach($perms as $perm) {
                if(isset($perm->child_file_view)) {
                    $resultantPerms->child_file_view = $perm->child_file_view;
                }
            }

            // User-specific folder permissions
            $perms = $folder->storagePermissions->where('permissible_type', 'App\User')->where('permissible_id', Auth::id());
            foreach($perms as $perm) {
                if(isset($perm->child_file_view)) {
                    $resultantPerms->child_file_view = $perm->child_file_view;
                }
            }
        }

        // Role-specific file permissions
        $perms = $folder->storagePermissions->where('permissible_type', 'App\Role')->whereIn('permissible_id', $roles);
        foreach($perms as $perm) {
            if(isset($perm->view)) {
                $resultantPerms->view = $perm->view;
            }
        }
        
        // User-specific file permissions
        $perms = $this->storagePermissions->where('permissible_type', 'App\User')->where('permissible_id', Auth::id());
        foreach($perms as $perm) {
            if(isset($perm->view)) {
                $resultantPerms->view = $perm->view;
            }
        }

        // Tagged user permissions
        /*$taggedUsers = $this->usersTagged;
        foreach($taggedUsers as $taggedUser) {
            foreach($taggedUser->storagePermissionsAsTarget as $perm) {
                if(isset($perm->view)) {
                    $resultantPerms->view = $perm->view;
                }
            }
        }*/
        $taggedFaces = $this->faces;
        foreach($taggedFaces as $face) {
            $taggedUser = $face->user;
            var_dump($taggedUser);
            if($taggedUser == null) {
                continue;
            }
            foreach($taggedUser->storagePermissionsAsTarget as $perm) {
                if(isset($perm->view)) {
                    $resultantPerms->view = $perm->view;
                }
            }
        }
        $faces = $this->faces;
        foreach($faces as $face) {
            $taggedUser = $face->user;
            if($taggedUser == null) {
                continue;
            }
            foreach($taggedUser->storagePermissionsAsTarget as $perm) {
                $permsRows->push($perm);
                if(isset($perm->view)) {
                    $resultantPerms->view = $perm->view;
                }
            }
        }
        
        if($resultantPerms->view == 1 OR ($resultantPerms->view == NULL AND $resultantPerms->child_file_view == 1)) {
            return true;
        } else {
            return false;
        }
    }

    public function getStoragePermissions() {
        $roles = Auth::user()->roles->where('site_id', Auth::user()->site->id)->pluck('id')->toArray();
        $resultantPerms = new StoragePermission;
        $folderPermList = array('child_file_view');
        $filePermList = array('view');

        // Build tree hierarchy
        $parents = array();
        $permsRows = collect();
        $folder = $this->folder;
        while($folder != null) {
            array_push($parents, $folder->id);
            $folder = Folder::find($folder->parent_id);
        }
        $parents = array_reverse($parents);
        
        foreach($parents as $folderID) {
            $folder = Folder::find($folderID);
            
            // Role-specific folder permissions
            $perms = $folder->storagePermissions->where('permissible_type', 'App\Role')->whereIn('permissible_id', $roles);
            foreach($perms as $perm) {
                $permsRows->push($perm);
                if(isset($perm->child_file_view)) {
                    $resultantPerms->child_file_view = $perm->child_file_view;
                }
            }

            // User-specific folder permissions
            $perms = $folder->storagePermissions->where('permissible_type', 'App\User')->where('permissible_id', Auth::id());
            foreach($perms as $perm) {
                $permsRows->push($perm);
                if(isset($perm->child_file_view)) {
                    $resultantPerms->child_file_view = $perm->child_file_view;
                }
            }
        }

        // Role-specific file permissions
        $perms = $folder->storagePermissions->where('permissible_type', 'App\Role')->whereIn('permissible_id', $roles);
        foreach($perms as $perm) {
            $permsRows->push($perm);
            if(isset($perm->view)) {
                $resultantPerms->view = $perm->view;
            }
        }
        
        // User-specific file permissions
        $perms = $this->storagePermissions->where('permissible_type', 'App\User')->where('permissible_id', Auth::id());
        foreach($perms as $perm) {
            $permsRows->push($perm);
            if(isset($perm->view)) {
                $resultantPerms->view = $perm->view;
            }
        }

        // Tagged user permissions
        /*$taggedUsers = $this->usersTagged;
        foreach($taggedUsers as $taggedUser) {
            foreach($taggedUser->storagePermissionsAsTarget as $perm) {
                $permsRows->push($perm);
                if(isset($perm->view)) {
                    $resultantPerms->view = $perm->view;
                }
            }
        }*/
        $faces = $this->faces;
        foreach($faces as $face) {
            $taggedUser = $face->user;
            if($taggedUser == null) {
                continue;
            }
            foreach($taggedUser->storagePermissionsAsTarget as $perm) {
                $permsRows->push($perm);
                if(isset($perm->view)) {
                    $resultantPerms->view = $perm->view;
                }
            }
        }
        
        return $permsRows;
    }
}
