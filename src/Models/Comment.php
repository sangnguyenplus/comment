<?php

namespace Botble\Comment\Models;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Models\BaseModel;
use Botble\Member\Models\Member;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends BaseModel
{
    public static ?Model $author = null;

    protected $table = 'bb_comments';

    protected $fillable = [
        'comment',
        'reference_id',
        'reference_type',
        'ip_address',
        'user_id',
        'status',
        'parent_id',
        'reply_count',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'status' => BaseStatusEnum::class,
    ];

    protected $appends = [
        'time',
        'rep',
        'isAuthor',
        'liked',
    ];

    protected $with = [
        'user',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function (Comment $comment) {
            if ((int)$comment->parent_id !== 0) {
                $parent = Comment::where(['id' => $comment->parent_id])->first();
                $parent->reply_count = Comment::where(['parent_id' => $parent->id])->count();
                $parent->save();
            }
        });

        static::deleted(function (Comment $comment) {
            Comment::where(['parent_id' => $comment->id])->delete();
        });
    }

    public function user(): HasOne
    {
        return $this->hasOne(Member::class, 'id', 'user_id')->withDefault();
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function getTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getIsAuthorAttribute(): bool
    {
        if (self::$author && $this->user) {
            return $this->user->email === self::$author->email && $this->user->user_type === get_class(self::$author);
        }

        return false;
    }

    public function getRepAttribute(): LengthAwarePaginator|array
    {
        return (int)$this->reply_count > 0 ? $this->replies()
            ->orderBy('created_at', 'DESC')
            ->paginate(5, ['*'], 'rep_page') : [];
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id', 'id');
    }

    public function getLikedAttribute(): bool
    {
        if ((int)$this->like_count > 0 && auth()->guard(COMMENT_GUARD)->check()) {
            return $this->likes()->where(['user_id' => auth()->guard(COMMENT_GUARD)->id()])->exists();
        }

        return false;
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }
}
