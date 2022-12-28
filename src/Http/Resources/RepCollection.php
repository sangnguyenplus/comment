<?php

namespace Botble\Comment\Http\Resources;

use Botble\Comment\Models\Comment;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RepCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        $this->collection->transform(function (Comment $comment) {
            return (new CommentResource($comment));
        });

        return [
            'data' => $this->collection,
            'pagination' => [
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
            ],

        ];
    }
}
