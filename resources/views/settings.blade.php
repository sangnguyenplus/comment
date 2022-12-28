@extends(BaseHelper::getAdminMasterLayoutTemplate())
@section('content')
    {!! Form::open(['route' => ['comment.storage-settings']]) !!}
    <div class="max-width-1200">
        <div class="flexbox-annotated-section">

            <div class="flexbox-annotated-section-annotation">
                <div class="annotated-section-title pd-all-20">
                    <h2>Comment settings</h2>
                </div>
                <div class="annotated-section-description pd-all-20 p-none-t">
                    <p class="color-note">Configure comment options</p>
                </div>
            </div>

            <div class="flexbox-annotated-section-content">
                <div class="wrapper-content pd-all-20">

                    <div class="form-group mb-2">
                        <input type="hidden" name="comment_enable" value="0">
                            <label>
                                <input type="checkbox" name="comment_enable"
                                       @if (setting('comment_enable')) checked @endif
                                       value="1" id="comment-enable">
                                Enable?
                            </label>
                    </div>

                    <div class="form-group mb-2">
                        <input type="hidden" name="comment_moderation" value="0">
                            <label>
                                <input type="checkbox" name="comment_moderation"
                                       @if (setting('comment_moderation')) checked @endif
                                       value="1" id="comment_moderation">
                                Comments must be moderated?
                            </label>
                    </div>

                </div>
            </div>

        </div>

        <div id="show-comments-setting" style="display: none">

            <div class="flexbox-annotated-section">

                <div class="flexbox-annotated-section-annotation">
                    <div class="annotated-section-title pd-all-20">
                        <h2>Enable comments for</h2>
                    </div>
                    <div class="annotated-section-description pd-all-20 p-none-t">
                        <p class="color-note">Select Modules to Embed Comments</p>
                    </div>
                </div>

                <div class="flexbox-annotated-section-content">
                    <div class="wrapper-content pd-all-20">
                        @php
                            $menuEnables = json_decode(setting('comment_menu_enable', '[]'), true);
                        @endphp

                        <input type="hidden" name="comment_menu_enable[]" value="0" />

                        @foreach(SlugHelper::supportedModels() as $model => $name)
                            <div class="form-group mb-2">
                                <label for="{{ $model }}">
                                    <input type="checkbox"
                                           @if (in_array($model, $menuEnables)) checked @endif
                                           value="{{ $model }}" name="comment_menu_enable[]" id="{{ $model }}">
                                    {{ $name }}
                                </label>
                            </div>
                        @endforeach

                    </div>
                </div>

            </div>

        </div>

        <div class="flexbox-annotated-section" style="border: none">
            <div class="flexbox-annotated-section-annotation">
                &nbsp;
            </div>
            <div class="flexbox-annotated-section-content">
                <button class="btn btn-info" type="submit">{{ trans('core/setting::setting.save_settings') }}</button>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@endsection
