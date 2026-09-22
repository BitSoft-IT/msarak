<?php

namespace App\Policies;

use App\Models\AssessmentSession;
use App\Models\User;

class AssessmentSessionPolicy
{
    /**
     * Determine whether the user can view the assessment session.
     */
    public function view(User $user, AssessmentSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    /**
     * Determine whether the user can update answers in the assessment session.
     */
    public function update(User $user, AssessmentSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}
