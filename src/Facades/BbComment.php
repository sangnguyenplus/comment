<?php

namespace Botble\Comment\Facades;

use Botble\Comment\Supports\BbComment as BbCommentSupport;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Contracts\Auth\Authenticatable|\Botble\Base\Models\BaseModel getModel()
 * @method static bool setAuthProvider()
 * @method static string|null getVersion()
 * @method static bool checkCurrentUser()
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null getCurrentUser()
 *
 * @see \Botble\Comment\Supports\BbComment
 */
class BbComment extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BbCommentSupport::class;
    }
}
