<?php

namespace Botble\Comment\Models;

use Botble\Base\Models\BaseModel;

class CommentLike extends BaseModel
{
    protected $table = 'bb_comment_likes';

    protected $fillable = [
        'user_id',
        'user_type',
        'comment_id',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::created(function (CommentLike $like) {
            static::updateCountLike($like);
        });

        static::deleted(function (CommentLike $like) {
            static::updateCountLike($like);
        });
    }

    protected static function updateCountLike(CommentLike $like): void
    {
        $comment = Comment::where(['id' => $like->comment_id])->first();

        $comment->like_count = CommentLike::where(['comment_id' => $like->comment_id])->count();
        $comment->save();
    }
}
