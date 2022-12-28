<?php

namespace Botble\Comment\Http\Controllers\AJAX;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Comment\Events\NewCommentEvent;
use Botble\Comment\Http\Resources\CommentResource;
use Botble\Comment\Http\Resources\UserResource;
use Botble\Comment\Models\Comment;
use Botble\Comment\Repositories\Interfaces\CommentInterface;
use Botble\Comment\Repositories\Interfaces\CommentLikeInterface;
use Botble\Comment\Repositories\Interfaces\CommentRecommendInterface;
use Botble\Comment\Supports\CheckMemberCredentials;
use Botble\Member\Repositories\Interfaces\MemberInterface;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use RvMedia;

class CommentFrontController extends BaseController
{
    protected BaseHttpResponse $response;

    protected CommentInterface $commentRepository;

    protected CheckMemberCredentials $memberCredentials;

    public function __construct(
        BaseHttpResponse $response,
        CommentInterface $commentRepository,
        CheckMemberCredentials $memberCredentials
    ) {
        $this->response = $response;
        $this->commentRepository = $commentRepository;
        $this->memberCredentials = $memberCredentials;
    }

    public function postComment(Request $request): BaseHttpResponse
    {
        $validate = $this->validator($request->input());
        if ($validate->fails()) {
            return $this->response
                ->setMessage($validate->getMessageBag())
                ->setError();
        }

        if (! ($reference = $this->reference($request))) {
            return $this->response
                ->setError()
                ->setMessage(__('Invalid reference'));
        }

        $user = $request->user();

        $request->merge(array_merge(
            [
                'ip_address' => $request->ip(),
                'user_id' => $user->getAuthIdentifier(),
                'status' => setting('comment_moderation') ? BaseStatusEnum::PENDING : BaseStatusEnum::PUBLISHED,
            ],
            $reference
        ));
        $comment = $this->commentRepository->storageComment($request->only([
            'ip_address',
            'user_id',
            'reference_id',
            'reference_type',
            'reference',
            'comment',
            'parent_id',
            'status',
        ]));

        event(new NewCommentEvent($comment, $user));

        if (setting('comment_moderation')) {
            return $this->response->setError()
                ->setMessage(__('Comments must be moderated'));
        }

        return $this->response->setData(new CommentResource($comment));
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'reference' => 'required',
            'comment' => 'required|min:5',
        ]);
    }

    protected function reference(Request $request)
    {
        try {
            $reference = json_decode(base64_decode($request->input('reference')), true);

            if (isset($reference['author']) && ! empty($reference['author'])) {
                Comment::$author = app($reference['author']['type'])->where(['id' => $reference['author']['id']])
                    ->first();
            }

            return $reference;
        } catch (Exception) {
            return null;
        }
    }

    public function getComments(Request $request, CommentRecommendInterface $commentRecommendRepo)
    {
        if (! ($reference = $this->reference($request))) {
            return $this->response
                ->setError()
                ->setMessage(__('Invalid reference'));
        }
        $user = $this->memberCredentials->handle();
        $parentId = $request->input('up', 0);
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 5);
        $sort = $request->input('sort', 'newest');

        [$comments, $attrs] = $this->commentRepository->getComments($reference, $parentId, $page, $limit, $sort);

        return $this->response
            ->setData([
                'comments' => CommentResource::collection($comments),
                'attrs' => $attrs,
                'user' => empty($user) ? null : new UserResource($user),
                'recommend' => $commentRecommendRepo->getRecommendOfArticle($reference, $user),
            ]);
    }

    public function userInfo(Request $request): BaseHttpResponse
    {
        return $this->response
            ->setData(new UserResource($request->user()));
    }

    public function deleteComment(Request $request)
    {
        $userId = $request->user()->getAuthIdentifier();
        $id = $request->input('id');

        if (! $id) {
            return $this->response
                ->setError()
                ->setMessage(__('Comment ID is required'));
        }

        $comment = $this->commentRepository->getFirstBy(compact('id'));

        if (! $comment || $comment->user_id !== $userId) {
            return $this->response
                ->setError()
                ->setMessage(__('You don\'t have permission with this comment'));
        }

        $this->commentRepository->delete($comment);

        return $this->response
            ->setMessage(__('Delete comment successfully'));
    }

    public function likeComment(Request $request, CommentLikeInterface $commentLikeRepo)
    {
        $id = $request->input('id');
        $user = $request->user();

        $comment = $this->commentRepository->getFirstBy(compact('id'));

        $liked = $commentLikeRepo->likeThisComment($comment, $user);

        return $this->response
            ->setData(compact('liked'))
            ->setMessage($liked ? __('Like successfully') : __('Unlike successfully'));
    }

    public function changeAvatar(Request $request, MemberInterface $commentUserRepo, BaseHttpResponse $response)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return $response
                ->setError()
                ->setCode(422)
                ->setMessage(__('Data invalid!') . ' ' . implode(' ', $validator->errors()->all()) . '.');
        }

        try {
            $file = RvMedia::handleUpload($request->file('photo'), 0, 'members');
            if (Arr::get($file, 'error') !== true) {
                $commentUserRepo->createOrUpdate(
                    ['avatar_id' => $file['data']->id],
                    ['id' => $request->user()->getKey()]
                );
            }

            return $response
                ->setMessage(__('Update avatar successfully!'));
        } catch (Exception $ex) {
            return $response
                ->setError()
                ->setMessage($ex->getMessage());
        }
    }

    public function recommend(Request $request, CommentRecommendInterface $commentRecommendRepo): BaseHttpResponse
    {
        $reference = $this->reference($request);
        $user = $request->user();

        if ($reference) {
            $params = array_merge(
                Arr::only($reference, ['reference_type', 'reference_id']),
                ['user_id' => $user->id]
            );
            $recommend = $commentRecommendRepo->getFirstBy($params);

            if (! $recommend) {
                $commentRecommendRepo->createOrUpdate($params);
            } else {
                $recommend->delete();
            }

            return $this->response
                ->setData($recommend);
        }

        return $this->response
            ->setError();
    }
}
