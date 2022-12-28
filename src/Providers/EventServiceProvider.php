<?php

namespace Botble\Comment\Providers;

use Botble\Comment\Events\NewCommentEvent;
use Botble\Comment\Listeners\NewCommentListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        NewCommentEvent::class => [
            NewCommentListener::class,
        ],
    ];
}
