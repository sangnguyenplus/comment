<?php

namespace Botble\Comment\Repositories\Eloquent;

use BaseHelper;
use Botble\Base\Enums\BaseStatusEnum;
use Botble\Comment\Repositories\Interfaces\CommentInterface;
use Botble\Support\Repositories\Eloquent\RepositoriesAbstract;

class CommentRepository extends RepositoriesAbstract implements CommentInterface
{
    public function storageComment(array $input)
    {
        $condition = [];

        if (! empty($input['comment_id'])) {
            $condition = ['id' => $input['comment_id']];
        }

        $input['comment'] = BaseHelper::clean($input['comment']);

        return $this->createOrUpdate($input, $condition);
    }

    public function getComments(
        array $reference = [],
        int|string $parentId = 0,
        int $page = 1,
        int $limit = 20,
        string $sort = 'newest'
    ): array {
        $condition = [
            'status' => BaseStatusEnum::PUBLISHED,
            'reference_type' => $reference['reference_type'],
            'reference_id' => $reference['reference_id'],
        ];

        $orderBy = (function () use ($sort) {
            switch ($sort) {
                default:
                case 'newest':
                    return ['created_at' => 'desc'];
                case 'oldest':
                    return ['created_at' => 'asc'];
                case 'best':
                    return ['like_count' => 'desc'];
            }
        })();

        $params = [
            'condition' => array_merge($condition, [
                'parent_id' => $parentId,
            ]),
            'order_by' => $orderBy,
            'paginate' => [
                'per_page' => $limit,
                'current_paged' => $page,
            ],
            'with' => ['user'],
        ];

        $count = -1;

        if ($parentId === 0 && $page === 1) {
            $count = $this->count($condition);
        }

        $result = $this->advancedGet($params);

        return [
            $result->getCollection(),
            [
                'total' => $result->total(),
                'per_page' => $result->perPage(),
                'current_page' => $result->currentPage(),
                'last_page' => $result->lastPage(),
                'from' => $result->firstItem(),
                'to' => $result->lastItem(),
                'count_all' => $count,
            ],
        ];
    }
}
