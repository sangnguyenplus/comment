<?php

namespace Botble\Comment\Http\Controllers;

use Assets;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Events\BeforeEditContentEvent;
use Botble\Base\Events\CreatedContentEvent;
use Botble\Base\Events\DeletedContentEvent;
use Botble\Base\Events\UpdatedContentEvent;
use Botble\Base\Forms\FormBuilder;
use Botble\Base\Http\Controllers\BaseController;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Comment\Forms\CommentForm;
use Botble\Comment\Http\Requests\CommentRequest;
use Botble\Comment\Repositories\Interfaces\CommentInterface;
use Botble\Comment\Supports\CloneUserToMember;
use Botble\Comment\Tables\CommentTable;
use Botble\Setting\Supports\SettingStore;
use Exception;
use Illuminate\Http\Request;

class CommentController extends BaseController
{
    protected CommentInterface $commentRepository;

    public function __construct(CommentInterface $commentRepository)
    {
        $this->commentRepository = $commentRepository;
    }

    public function index(CommentTable $table)
    {
        page_title()->setTitle(trans('plugins/comment::comment.name'));

        return $table->renderTable();
    }

    public function create(FormBuilder $formBuilder)
    {
        page_title()->setTitle(trans('plugins/comment::comment.create'));

        return $formBuilder->create(CommentForm::class)->renderForm();
    }

    public function store(CommentRequest $request, BaseHttpResponse $response)
    {
        $comment = $this->commentRepository->createOrUpdate($request->input());

        event(new CreatedContentEvent(COMMENT_MODULE_SCREEN_NAME, $request, $comment));

        return $response
            ->setPreviousUrl(route('comment.index'))
            ->setNextUrl(route('comment.edit', $comment->id))
            ->setMessage(trans('core/base::notices.create_success_message'));
    }

    public function edit(int $id, FormBuilder $formBuilder, Request $request)
    {
        $comment = $this->commentRepository->findOrFail($id);

        event(new BeforeEditContentEvent($request, $comment));

        page_title()->setTitle(trans('plugins/comment::comment.edit') . ' "' . $comment->name . '"');

        return $formBuilder->create(CommentForm::class, ['model' => $comment])->renderForm();
    }

    public function update(int $id, CommentRequest $request, BaseHttpResponse $response)
    {
        $comment = $this->commentRepository->findOrFail($id);

        $comment->fill($request->input());

        $this->commentRepository->createOrUpdate($comment);

        event(new UpdatedContentEvent(COMMENT_MODULE_SCREEN_NAME, $request, $comment));

        return $response
            ->setPreviousUrl(route('comment.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function approve(int $id, BaseHttpResponse $response)
    {
        $comment = $this->commentRepository->findOrFail($id);

        $comment->status = BaseStatusEnum::PUBLISHED;

        $this->commentRepository->createOrUpdate($comment);

        return $response
            ->setPreviousUrl(route('comment.index'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function destroy(Request $request, int $id, BaseHttpResponse $response)
    {
        try {
            $comment = $this->commentRepository->findOrFail($id);

            $this->commentRepository->delete($comment);

            event(new DeletedContentEvent(COMMENT_MODULE_SCREEN_NAME, $request, $comment));

            return $response->setMessage(trans('core/base::notices.delete_success_message'));
        } catch (Exception $exception) {
            return $response
                ->setError()
                ->setMessage($exception->getMessage());
        }
    }

    public function deletes(Request $request, BaseHttpResponse $response)
    {
        $ids = $request->input('ids');
        if (empty($ids)) {
            return $response
                ->setError()
                ->setMessage(trans('core/base::notices.no_select'));
        }

        foreach ($ids as $id) {
            $comment = $this->commentRepository->findOrFail($id);
            $this->commentRepository->delete($comment);
            event(new DeletedContentEvent(COMMENT_MODULE_SCREEN_NAME, $request, $comment));
        }

        return $response->setMessage(trans('core/base::notices.delete_success_message'));
    }

    public function getSettings()
    {
        page_title()->setTitle(trans('plugins/comment::comment.name'));

        Assets::addScriptsDirectly('vendor/core/plugins/comment/js/comment-setting.js');

        return view('plugins/comment::settings');
    }

    public function storeSettings(Request $request, SettingStore $settingStore, BaseHttpResponse $response)
    {
        foreach ($request->except(['_token']) as $key => $setting) {
            if (is_array($setting)) {
                $setting = json_encode($setting);
            }

            $settingStore->set($key, $setting);
        }

        $settingStore->save();

        return $response
            ->setPreviousUrl(route('comment.setting'))
            ->setMessage(trans('core/base::notices.update_success_message'));
    }

    public function cloneUser(CloneUserToMember $cloneUserToMember, Request $request, BaseHttpResponse $response)
    {
        $clonedUser = $cloneUserToMember->handle($request);

        if (! $clonedUser) {
            return $response->setError();
        }

        auth()->guard(COMMENT_GUARD)->loginUsingId($clonedUser->id);

        return $response->setData(['token' => $clonedUser->id]);
    }
}
