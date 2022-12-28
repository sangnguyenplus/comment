<?php

namespace Botble\Comment\Supports;

use Illuminate\Foundation\Application;

class CheckMemberCredentials
{
    protected Application $app;

    protected string $provider = COMMENT_GUARD;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function handle()
    {
        $user = auth()->guard(COMMENT_GUARD)->user();

        if ($user) {
            app('auth')->guard(COMMENT_GUARD)->setUser($user);

            return $user;
        }

        return null;
    }
}
