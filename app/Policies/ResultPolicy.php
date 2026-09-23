<?php

namespace App\Policies;

use App\Models\Result;
use App\Models\User;

class ResultPolicy
{
    public function view(User $user, Result $result): bool
    {
        return $result->assessmentSession()
            ->where('user_id', $user->id)
            ->exists();
    }
}
