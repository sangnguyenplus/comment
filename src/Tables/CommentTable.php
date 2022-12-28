<?php

namespace Botble\Comment\Tables;

use Auth;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Comment\Repositories\Interfaces\CommentInterface;
use Botble\Setting\Supports\SettingStore;
use Botble\Table\Abstracts\TableAbstract;
use Html;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\DataTables;

class CommentTable extends TableAbstract
{
    protected $hasActions = true;

    protected $hasFilter = true;

    public function __construct(DataTables $table, UrlGenerator $urlGenerator, CommentInterface $commentRepository)
    {
        $this->repository = $commentRepository;
        $this->setOption('id', 'plugins-comment-table');
        parent::__construct($table, $urlGenerator);

        if (! Auth::user()->hasAnyPermission(['comment.edit', 'comment.destroy'])) {
            $this->hasOperations = false;
            $this->hasActions = false;
        }
    }

    public function ajax(): JsonResponse
    {
        $data = $this->table
            ->eloquent($this->query())
            ->editColumn('comment', function ($item) {
                return $item->comment;
            })
            ->editColumn('checkbox', function ($item) {
                return $this->getCheckbox($item->id);
            })
            ->editColumn('created_at', function ($item) {
                return $item->time;
            })
            ->editColumn('reference', function ($item) {
                return $item->reference ? Html::link(
                    $item->reference->url . '#bb-comment',
                    $item->reference->name,
                    ['target' => '_blank']
                ) : '';
            })
            ->editColumn('user', function ($item) {
                return $item->user ? $item->user->name : 'Guest';
            })
            ->editColumn('status', function ($item) {
                return $item->status->toHtml();
            });

        $this->storageLatestViewed();

        return apply_filters(BASE_FILTER_GET_LIST_DATA, $data, $this->repository->getModel())
            ->addColumn('operations', function ($item) {
                $extra = '';
                if (setting('comment_moderation')) {
                    $extra = Html::link(
                        route('comment.approve', $item->id),
                        trans('plugins/comment::comment.approve'),
                        ['class' => 'btn btn-info']
                    )->toHtml();
                }

                return $this->getOperations(false, 'comment.destroy', $item, $extra);
            })
            ->escapeColumns([])
            ->make(true);
    }

    public function query(): Relation|Builder|QueryBuilder
    {
        $query = $this->repository->getModel()
            ->with(['reference'])
            ->select(['*']);

        return $this->applyScopes($query);
    }

    protected function storageLatestViewed(): void
    {
        if ((int)request()->input('start', -1) === 0) {
            $latestId = $this->repository->getModel()->latest()->first();
            if ($latestId && (int)setting('admin-comment_latest_viewed_id', 0) !== $latestId) {
                app(SettingStore::class)->set('admin-comment_latest_viewed_id', $latestId->id)->save();
            }
        }
    }

    public function columns(): array
    {
        return [
            'id' => [
                'title' => trans('core/base::tables.id'),
                'width' => '20px',
            ],
            'comment' => [
                'title' => trans('plugins/comment::comment.name'),
                'class' => 'text-start',
            ],
            'user' => [
                'title' => trans('plugins/comment::comment.user'),
                'class' => 'text-start',
            ],
            'reference' => [
                'title' => trans('plugins/comment::comment.article'),
                'class' => 'text-start',
            ],
            'created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'width' => '100px',
            ],
            'status' => [
                'title' => trans('core/base::tables.status'),
                'width' => '100px',
            ],
        ];
    }

    public function bulkActions(): array
    {
        return $this->addDeleteAction(route('comment.deletes'), 'comment.destroy', parent::bulkActions());
    }

    public function getBulkChanges(): array
    {
        return [
            'comments.name' => [
                'title' => trans('core/base::tables.name'),
                'type' => 'text',
                'validate' => 'required|max:120',
            ],
            'comments.status' => [
                'title' => trans('core/base::tables.status'),
                'type' => 'select',
                'choices' => BaseStatusEnum::labels(),
                'validate' => 'required|in:' . implode(',', BaseStatusEnum::values()),
            ],
            'comments.created_at' => [
                'title' => trans('core/base::tables.created_at'),
                'type' => 'date',
            ],
        ];
    }
}
