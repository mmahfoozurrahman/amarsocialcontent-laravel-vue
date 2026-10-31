<?php

namespace App\Policies;

use App\Models\Chapter;
use App\Models\User;

class ChapterPolicy
{
    public function view(?User $user, Chapter $chapter): bool
    {
        return app(CoursePolicy::class)->view($user, $chapter->course);
    }

    public function update(User $user, Chapter $chapter): bool
    {
        return $user->id === $chapter->course->user_id;
    }

    public function delete(User $user, Chapter $chapter): bool
    {
        return $user->id === $chapter->course->user_id;
    }
}

