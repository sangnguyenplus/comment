<?php

namespace Botble\Comment\Providers;

use Botble\Base\Supports\Helper;
use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\Blog\Models\Post;
use Botble\Comment\Models\Comment;
use Botble\Comment\Models\CommentLike;
use Botble\Comment\Models\CommentRecommend;
use Botble\Comment\Repositories\Caches\CommentCacheDecorator;
use Botble\Comment\Repositories\Caches\CommentLikeCacheDecorator;
use Botble\Comment\Repositories\Caches\CommentRecommendCacheDecorator;
use Botble\Comment\Repositories\Eloquent\CommentLikeRepository;
use Botble\Comment\Repositories\Eloquent\CommentRecommendRepository;
use Botble\Comment\Repositories\Eloquent\CommentRepository;
use Botble\Comment\Repositories\Interfaces\CommentInterface;
use Botble\Comment\Repositories\Interfaces\CommentLikeInterface;
use Botble\Comment\Repositories\Interfaces\CommentRecommendInterface;
use Botble\Member\Models\Member;
use Botble\Page\Models\Page;
use EmailHandler;
use Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class CommentServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function register()
    {
        Helper::autoload(__DIR__ . '/../../helpers');

        $this->app->bind(CommentInterface::class, function () {
            return new CommentCacheDecorator(new CommentRepository(new Comment()));
        });

        $this->app->bind(CommentLikeInterface::class, function () {
            return new CommentLikeCacheDecorator(new CommentLikeRepository(new CommentLike()));
        });

        $this->app->bind(CommentRecommendInterface::class, function () {
            return new CommentRecommendCacheDecorator(new CommentRecommendRepository(new CommentRecommend()));
        });

        config([
            'auth.guards.' . COMMENT_GUARD => [
                'driver' => 'session',
                'provider' => COMMENT_GUARD,
            ],
            'auth.providers.' . COMMENT_GUARD => [
                'driver' => 'eloquent',
                'model' => Member::class,
            ],
        ]);
        $this->configureRateLimiting();
    }

    public function boot()
    {
        $this->setNamespace('plugins/comment')
            ->loadAndPublishConfigurations(['permissions', 'email'])
            ->loadMigrations()
            ->publishAssets()
            ->loadAndPublishTranslations()
            ->loadAndPublishViews()
            ->loadRoutes(['web', 'ajax']);

        $this->app->register(EventServiceProvider::class);

        $this->app->booted(function () {
            $this->app->register(HookServiceProvider::class);

            Post::resolveRelationUsing('comments', function ($model) {
                return $model->morphMany(Comment::class, 'reference');
            });

            Page::resolveRelationUsing('comments', function ($model) {
                return $model->morphMany(Comment::class, 'reference');
            });
        });

        Event::listen(RouteMatched::class, function () {
            dashboard_menu()
                ->registerItem([
                    'id' => 'cms-plugins-comment',
                    'priority' => 5,
                    'parent_id' => null,
                    'name' => 'plugins/comment::comment.name',
                    'icon' => 'fa fa-comment',
                    'url' => route('comment.index'),
                    'permissions' => ['comment.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-comment-comment',
                    'priority' => 1,
                    'parent_id' => 'cms-plugins-comment',
                    'name' => 'plugins/comment::comment.name',
                    'icon' => null,
                    'url' => route('comment.index'),
                    'permissions' => ['comment.index'],
                ])
                ->registerItem([
                    'id' => 'cms-plugins-comment-setting',
                    'priority' => 5,
                    'parent_id' => 'cms-plugins-comment',
                    'name' => trans('plugins/comment::settings.name'),
                    'icon' => null,
                    'url' => route('comment.setting'),
                    'permissions' => ['setting.options'],
                ]);

            EmailHandler::addTemplateSettings(COMMENT_MODULE_SCREEN_NAME, config('plugins.comment.email', []));
        });
    }

    protected function configureRateLimiting()
    {
        RateLimiter::for('comment', function () {
            return Limit::perMinute(20);
        });
    }
}
