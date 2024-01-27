<?php

namespace Botble\Comment\Repositories\Caches;

use Botble\Comment\Repositories\Interfaces\CommentInterface;
use Botble\Support\Repositories\Caches\CacheAbstractDecorator;

class CommentCacheDecorator extends CacheAbstractDecorator implements CommentInterface
{
    public function storageComment(array $input)
    {
        return $this->flushCacheAndUpdateData(__FUNCTION__, func_get_args());
    }

    public function getComments(array $reference = [], int|string $parentId = 0, int $page = 1, int $limit = 20, string $sort = 'newest'): array
    {
        return $this->getDataIfExistCache(__FUNCTION__, func_get_args());
    }
}
