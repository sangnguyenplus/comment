<?php

namespace Botble\Comment\Http\Resources;

use Botble\Comment\Models\Comment;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    public function toArray($request): array
    {
        $this->loadMissing('user');

        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'time' => $this->time,
            'like_count' => $this->like_count,
            'liked' => (bool)$this->like_count,
            'rep' => (int)$this->reply_count > 0 ? new RepCollection(
                $this->replies()
                    ->orderBy('created_at', 'DESC')
                    ->paginate(5, ['*'], 'rep_page')
            ) : [],
            'user' => new UserResource($this->user),
        ];
    }
}
