<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

class NotePolicy
{
    public function view(?User $user, Note $note): bool
    {
        return app(SectionPolicy::class)->view($user, $note->section);
    }

    public function update(User $user, Note $note): bool
    {
        return $user->id === $note->section->collection->user_id;
    }

    public function delete(User $user, Note $note): bool
    {
        return $user->id === $note->section->collection->user_id;
    }
}

