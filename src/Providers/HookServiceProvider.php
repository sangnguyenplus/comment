<?php

namespace Botble\Comment\Providers;

use Botble\Base\Models\BaseModel;
use Botble\Blog\Models\Post;
use Botble\Comment\Repositories\Interfaces\CommentInterface;
use Html;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use MetaBox;
use RvMedia;
use SlugHelper;
use Theme;

class HookServiceProvider extends ServiceProvider
{
    protected ?BaseModel $currentReference = null;

    public function boot()
    {
        add_shortcode('comment', 'Comment', 'Comment for this article', [$this, 'renderComment']);
        add_action(BASE_ACTION_PUBLIC_RENDER_SINGLE, [$this, 'storageCurrentReference'], 100, 2);
        add_filter(BASE_FILTER_APPEND_MENU_NAME, [$this, 'getUnreadCount'], 210, 2);

        if (setting('comment_enable')) {
            add_filter(BASE_FILTER_BEFORE_RENDER_FORM, function ($form, $data) {
                if (get_class($data) == Post::class) {
                    $form->add('comment_status', 'onOff', [
                        'label' => trans('plugins/comment::comment.name'),
                        'label_attr' => ['class' => 'control-label'],
                        'default_value' => true,
                    ]);
                }

                return $form;
            }, 127, 2);

            add_action(BASE_ACTION_AFTER_CREATE_CONTENT, function ($type, $request, $object) {
                if (get_class($object) == Post::class) {
                    MetaBox::saveMetaBoxData($object, 'comment_status', $request->input('comment_status'));
                }
            }, 230, 3);

            add_action(BASE_ACTION_AFTER_UPDATE_CONTENT, function ($type, $request, $object) {
                if (get_class($object) == Post::class) {
                    MetaBox::saveMetaBoxData($object, 'comment_status', $request->input('comment_status'));
                }
            }, 231, 3);
        }
    }

    public function renderComment(): ?string
    {
        if (! setting('comment_enable')) {
            return null;
        }

        $this->loadAssets();

        $reference = $this->getReference();

        $loggedUser = auth()->user() ? request()->user()->only(['id', 'email']) : ['id' => 0];

        add_filter(THEME_FRONT_HEADER, function ($html) {
            $this->addSchemas($html);

            return $html . view('plugins/comment::partials.trans');
        }, 15);

        return $reference ? view('plugins/comment::short-codes.comment', compact('reference', 'loggedUser')) : null;
    }

    protected function loadAssets(): void
    {
        Theme::asset()
            ->container('footer')
            ->usePath(false)
            ->add('bb-comment', 'vendor/core/plugins/comment/js/comment.js', ['jquery'], [], comment_plugin_version());

        Theme::asset()
            ->usePath(false)
            ->add(
                'fontawesome-css',
                'vendor/core/plugins/comment/css/vendor/fontawesome-all.min.css',
                [],
                [],
                comment_plugin_version()
            )
            ->add('bb-comment-css', 'vendor/core/plugins/comment/css/comment.css', [], [], comment_plugin_version());
    }

    protected function getReference(bool $isBase64 = true): array|string
    {
        $slug = SlugHelper::getSlug(request()->route('slug'));

        $reference = [
            'reference_type' => $slug->reference_type,
            'reference_id' => $slug->reference_id,
        ];

        return $isBase64 ? base64_encode(json_encode($reference)) : $reference;
    }

    protected function addSchemas(?string &$html): void
    {
        $schemaJson = [
            '@context' => 'http://schema.org',
            '@type' => 'NewsArticle',
        ];

        if ($this->currentReference && get_class($this->currentReference) === Post::class) {
            $post = $this->currentReference;
            $category = $post->categories()->first();

            if ($category) {
                $schemaJson['category'] = $category->name;
            }

            $schemaJson = array_merge($schemaJson, [
                'url' => $post->url,
                'description' => $post->description,
                'name' => $post->name,
                'image' => RvMedia::getImageUrl($post->image),
            ]);

            $html .= '<script type="application/ld+json">' . json_encode($schemaJson) . '</script>';
        }
    }

    public function storageCurrentReference(string $screen, ?Model $object)
    {
        $this->currentReference = $object;
        $menuEnables = json_decode(setting('comment_menu_enable', '[]'), true);

        if (setting('comment_enable') && in_array(get_class($object), $menuEnables)) {
            if (! str_contains($object->content, '[comment')) {
                $object->content .= '[comment][/comment]';
            }
        }
    }

    public function getUnreadCount(int|string|null $index, string $menuId): int|string|null
    {
        if ($menuId == 'cms-plugins-comment') {
            $unread = app(CommentInterface::class)->count([
                ['id', '>', setting('admin-comment_latest_viewed_id', 0)],
            ]);

            if ($unread > 0) {
                return Html::tag('span', (string)$unread, ['class' => 'badge badge-success'])->toHtml();
            }
        }

        return $index;
    }
}
