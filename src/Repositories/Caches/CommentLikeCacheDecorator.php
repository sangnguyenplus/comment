<?php

namespace Botble\Comment\Repositories\Caches;

use Botble\Comment\Models\Comment;
use Botble\Comment\Repositories\Interfaces\CommentLikeInterface;
use Botble\Support\Repositories\Caches\CacheAbstractDecorator;
use Illuminate\Contracts\Auth\Authenticatable;

class CommentLikeCacheDecorator extends CacheAbstractDecorator implements CommentLikeInterface
{
    public function likeThisComment(Comment $comment, Authenticatable $user): bool
    {
        return $this->flushCacheAndUpdateData(__FUNCTION__, func_get_args());
    }
}
