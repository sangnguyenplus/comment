<?php

namespace Botble\Comment\Repositories\Interfaces;

use Botble\Support\Repositories\Interfaces\RepositoryInterface;

interface CommentRecommendInterface extends RepositoryInterface
{
    public function getRecommendOfArticle(array $reference, $user);
}
