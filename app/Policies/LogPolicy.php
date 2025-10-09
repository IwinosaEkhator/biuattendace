<?php

namespace App\Policies;

use App\Models\Logs;
use App\Models\User;

class LogPolicy
{
    public function delete(User $user, Logs $log)
    {
        // Allow if the user is an admin
        return $user->user_type === 'admin';
    }
}

