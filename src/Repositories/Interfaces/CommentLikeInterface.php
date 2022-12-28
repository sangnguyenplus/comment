<?php

namespace Botble\Comment\Repositories\Interfaces;

use Botble\Comment\Models\Comment;
use Botble\Member\Models\Member;
use Botble\Support\Repositories\Interfaces\RepositoryInterface;

interface CommentLikeInterface extends RepositoryInterface
{
    /**
     * @param Comment $comment
     * @param Member $user
     * @return mixed
     */
    public function likeThisComment(Comment $comment, Member $user);
}
