<?php

namespace App\Policies;

use App\Models\AggregatedContent;
use App\Models\User;

class AggregatedContentPolicy
{
    /**
     * Determine whether the user can view the content.
     */
    public function view(?User $user, AggregatedContent $content): bool
    {
        if ($content->visibility === 'public' && $content->status === 'completed') {
            return true;
        }

        return $user && $user->id === $content->user_id;
    }

    /**
     * Determine whether the user can update the content.
     */
    public function update(User $user, AggregatedContent $content): bool
    {
        return $user->id === $content->user_id;
    }

    /**
     * Determine whether the user can delete the content.
     */
    public function delete(User $user, AggregatedContent $content): bool
    {
        return $user->id === $content->user_id;
    }

    /**
     * Determine whether the user can retry failed processing.
     */
    public function retry(User $user, AggregatedContent $content): bool
    {
        return $user->id === $content->user_id && $content->canRetry();
    }
}

