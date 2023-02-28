<?php

namespace Botble\Comment\Repositories\Eloquent;

use Botble\Comment\Models\Comment;
use Botble\Comment\Repositories\Interfaces\CommentLikeInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;
use Illuminate\Contracts\Auth\Authenticatable;

class CommentLikeRepository extends RepositoriesAbstract implements CommentLikeInterface
{
    public function likeThisComment(Comment $comment, Authenticatable $user): bool
    {
        $params = ['comment_id' => $comment->getKey(), 'user_id' => $user->getAuthIdentifier()];

        $like = $this->getFirstBy($params);

        if ($like) { // unlike
            $like->delete();

            return false;
        }

        $this->createOrUpdate($params);

        return true;
    }
}
