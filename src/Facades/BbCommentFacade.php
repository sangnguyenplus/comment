<?php

namespace Botble\Comment\Facades;

use Botble\Comment\Supports\BbComment;
use Illuminate\Support\Facades\Facade;

/**
 * @see \Botble\Comment\Supports\BbComment
 */
class BbCommentFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BbComment::class;
    }
}
