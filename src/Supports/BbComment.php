<?php

namespace Botble\Comment\Supports;

use BaseHelper;
use Botble\ACL\Models\User;
use Botble\Base\Models\BaseModel;
use Botble\Comment\Models\CommentUser;
use Botble\Member\Models\Member;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;

class BbComment
{
    public function getModel(): Authenticatable|BaseModel
    {
        if (is_plugin_active('member')) {
            return new Member();
        }

        if (auth()->check()) {
            return new User();
        }

        return new CommentUser();
    }

    public function setAuthProvider(): bool
    {
        config([
            'auth.guards.' . COMMENT_GUARD => [
                'driver' => 'session',
                'provider' => COMMENT_GUARD,
            ],
            'auth.providers.' . COMMENT_GUARD => [
                'driver' => 'eloquent',
                'model' => get_class($this->getModel()),
            ],
        ]);

        return true;
    }

    public function getVersion(): string|null
    {
        $content = BaseHelper::getFileData(plugin_path('comment/plugin.json'));

        return Arr::get($content, 'version');
    }

    public function checkCurrentUser(): bool
    {
        if (auth(COMMENT_GUARD)->check()) {
            return true;
        }

        if (is_plugin_active('member')) {
            return auth('member')->check();
        }

        return auth()->check();
    }

    public function getCurrentUser(): Authenticatable|null
    {
        if (auth(COMMENT_GUARD)->check()) {
            return auth(COMMENT_GUARD)->user();
        }

        if (is_plugin_active('member')) {
            return auth('member')->user();
        }

        return auth()->user();
    }
}
