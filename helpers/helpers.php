<?php

if (! function_exists('has_member')) {
    function has_member(): bool
    {
        if (config()->has('has_member')) {
            return config('has_member');
        }

        $hasPluginMember = is_plugin_active('member');

        config(['has_member' => $hasPluginMember]);

        return $hasPluginMember;
    }
}

if (! function_exists('comment_plugin_version')) {
    function comment_plugin_version()
    {
        $content = BaseHelper::getFileData(plugin_path('comment/plugin.json'));

        return Arr::get($content, 'version');
    }
}
