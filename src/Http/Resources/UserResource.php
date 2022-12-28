<?php

namespace Botble\Comment\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use RvMedia;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_id ? RvMedia::getImageUrl(
                $this->avatar_url,
                'thumb',
                false,
                RvMedia::getDefaultImage()
            ) : $this->avatar_url,
            'created_at' => $this->created_at,
        ];
    }
}
