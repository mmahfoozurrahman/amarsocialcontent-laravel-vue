<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function view(?User $user, Lesson $lesson): bool
    {
        return app(ChapterPolicy::class)->view($user, $lesson->chapter);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->chapter->course->user_id;
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->id === $lesson->chapter->course->user_id;
    }
}

