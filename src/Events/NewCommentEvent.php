<?php

namespace Botble\Comment\Events;

use Botble\Comment\Models\Comment;
use Botble\Member\Models\Member;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewCommentEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Comment $comment;

    public Member $member;

    public function __construct(Comment $comment, Member $member)
    {
        $this->comment = $comment;
        $this->member = $member;
    }
}
