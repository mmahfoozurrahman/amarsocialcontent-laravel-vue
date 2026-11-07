<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function view(?User $user, Section $section): bool
    {
        return app(CollectionPolicy::class)->view($user, $section->collection);
    }

    public function update(User $user, Section $section): bool
    {
        return $user->id === $section->collection->user_id;
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->id === $section->collection->user_id;
    }
}

