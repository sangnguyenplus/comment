<?php

namespace Botble\Comment\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;

interface CommentInterface extends RepositoryInterface
{
    public function storageComment(array $input);

    public function getComments(
        array $reference = [],
        int $parentId = 0,
        int $page = 1,
        int $limit = 20,
        string $sort = 'newest'
    );
}
