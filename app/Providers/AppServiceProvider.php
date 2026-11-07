<?php

namespace App\Providers;

use App\Models\AggregatedContent;
use App\Models\Collection;
use App\Models\Note;
use App\Models\PersonalNote;
use App\Models\Section;
use App\Models\Tag;
use App\Policies\AggregatedContentPolicy;
use App\Policies\CollectionPolicy;
use App\Policies\NotePolicy;
use App\Policies\PersonalNotePolicy;
use App\Policies\SectionPolicy;
use App\Policies\TagPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(AggregatedContent::class, AggregatedContentPolicy::class);
        Gate::policy(Tag::class, TagPolicy::class);
        Gate::policy(PersonalNote::class, PersonalNotePolicy::class);
        Gate::policy(Collection::class, CollectionPolicy::class);
        Gate::policy(Section::class, SectionPolicy::class);
        Gate::policy(Note::class, NotePolicy::class);
    }
}
