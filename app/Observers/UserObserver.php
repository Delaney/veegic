<?php

namespace App\Observers;
use App\Models\User;
use App\Models\Subscription;

class UserObserver
{
    /**
     * Handle the user "created" event.
     *
     * @param  \App\Models\User $user
     * @return void
     */
    public function created(User $user)
    {
        $subscription = Subscription::create([
            'user_id'   => $user->id
        ]);
    }
}
