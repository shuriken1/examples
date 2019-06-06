<?php

namespace App\Policies;

use App\User;
use App\Food;
use Illuminate\Auth\Access\HandlesAuthorization;

class FoodsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the food.
     *
     * @param  \App\User  $user
     * @param  \App\Food  $food
     * @return mixed
     */
    public function view(User $user, Food $food)
    {
        return true;
    }

    /**
     * Determine whether the user can create foods.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function createPrivate(User $user)
    {
        // Any members of orgs can create foods.
        return !is_null($user->org);
    }

    /**
     * Determine whether the user can create foods.
     *
     * @param  \App\User  $user
     * @return mixed
     */
    public function createPublic(User $user)
    {
        // // If user is part of an org then they can.
        // if($user->org) {
        //     return true;
        // } else {
        //     return false;
        // }
        
        // Public foods disabled.
        return false;
    }

    /**
     * Determine whether the user can update the food.
     *
     * @param  \App\User  $user
     * @param  \App\Food  $food
     * @return mixed
     */
    public function update(User $user, Food $food)
    {
        /* DEBUG Seems like this should work but $food->owner doesn't equal $user for some reason
        dd($food->owner);
        if($food->owner == $user OR $user->org == $food->owner) {
            return true;
        } else {
            return false;
        }*/
        if($food->owner_type == 'App\User') {
            if($food->owner_id == $user->id) {
                return true;
            } else {
                return false;
            }
        } elseif($food->owner_type == 'App\Organisation') {
            if(!$user->org) {
                return false;
            }
            if($food->owner_id == $user->org->id) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Determine whether the user can delete the food.
     *
     * @param  \App\User  $user
     * @param  \App\Food  $food
     * @return mixed
     */
    public function delete(User $user, Food $food)
    {
        if($food->owner_type == 'App\User') {
            if($food->owner_id == $user->id) {
                return true;
            } else {
                return false;
            }
        } elseif($food->owner_type == 'App\Organisation') {
            if(!$user->org) {
                return false;
            }
            if($food->owner_id == $user->org->id) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Determine whether the user can restore the food.
     *
     * @param  \App\User  $user
     * @param  \App\Food  $food
     * @return mixed
     */
    public function restore(User $user, Food $food)
    {
        //
    }

    /**
     * Determine whether the user can permanently delete the food.
     *
     * @param  \App\User  $user
     * @param  \App\Food  $food
     * @return mixed
     */
    public function forceDelete(User $user, Food $food)
    {
        //
    }
}
