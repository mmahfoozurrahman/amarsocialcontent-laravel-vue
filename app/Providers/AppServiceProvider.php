<?php

namespace App\Providers;

use App\Models\AggregatedContent;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\PersonalNote;
use App\Models\Tag;
use App\Policies\AggregatedContentPolicy;
use App\Policies\ChapterPolicy;
use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use App\Policies\PersonalNotePolicy;
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
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
    }
}
