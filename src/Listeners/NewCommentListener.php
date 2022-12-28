<?php

namespace Botble\Comment\Listeners;

use Botble\Comment\Events\NewCommentEvent;
use Botble\Comment\Repositories\Interfaces\CommentInterface;
use EmailHandler;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewCommentListener implements ShouldQueue
{
    public function handle(NewCommentEvent $event)
    {
        $parentId = request()->input('parent_id');
        $article = $event->comment->reference()->first();

        $mailer = EmailHandler::setModule(COMMENT_MODULE_SCREEN_NAME)
            ->setVariableValues([
                'user_name' => $event->member->name,
                'post_name' => $article->name,
                'post_link' => $article->url,
                'comment_content' => $event->comment->comment,
            ]);

        // on reply
        if ($parentId > 0) {
            $comment = app(CommentInterface::class)->getFirstBy(['id' => $parentId]);
            if ($article &&
                $comment &&
                $comment->user->id !== $event->comment->user->id
            ) {
                $mailer->sendUsingTemplate('new-reply', $comment->user->email);
            }
        } else {
            // on comment
            $adminEmails = $this->getAdminEmail();
            if ($article && setting('admin_email') && ! in_array($event->comment->user->email, $adminEmails)) {
                $mailer->sendUsingTemplate('new-comment', setting('admin_email'));
            }
        }
    }

    protected function getAdminEmail(): array
    {
        if (function_exists('get_admin_email')) {
            return get_admin_email()->toArray();
        }

        return [setting('admin_email')];
    }
}
