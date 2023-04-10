<?php

namespace Botble\Comment\Http\Controllers\AJAX;

use App\Http\Controllers\Controller;
use Botble\Comment\Facades\BbComment;
use Botble\ACL\Traits\RegistersUsers;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    public function register(Request $request, BaseHttpResponse $response)
    {
        $this->validator($request->input())->validate();

        event(new Registered($user = $this->create($request->input())));

        $this->guard()->login($user);

        $this->registered($request, $user, $response);

        return $response->setNextUrl(route('public.index'));
    }

    protected function validator(array $data)
    {
        return Validator::make($data, array_merge(
            [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'required|email|max:255|unique:' . BbComment::getModel()->getTable(),
                'password' => 'required|min:6|confirmed',
            ],
            setting('enable_captcha') && is_plugin_active('captcha') ? ['g-recaptcha-response' => 'required|captcha'] : []
        ));
    }

    protected function create(array $data)
    {
        return BbComment::getModel()->forceCreate([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    protected function guard()
    {
        return auth(COMMENT_GUARD);
    }

    public function registered(Request $request, $user, $response)
    {
        $token = $user->id;

        return $response
            ->setData(compact('token'));
    }
}
