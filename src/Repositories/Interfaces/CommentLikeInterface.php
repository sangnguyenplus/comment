<?php

namespace Botble\Comment\Repositories\Interfaces;

use Botble\Comment\Models\Comment;
use Botble\Support\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Contracts\Auth\Authenticatable;

interface CommentLikeInterface extends RepositoryInterface
{
    public function likeThisComment(Comment $comment, Authenticatable $user): bool;
}
