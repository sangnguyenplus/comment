<?php

namespace Botble\Comment\Http\Controllers\AJAX;

use App\Http\Controllers\Controller;
use Botble\ACL\Traits\RegistersUsers;
use Botble\Base\Http\Responses\BaseHttpResponse;
use Botble\Member\Repositories\Interfaces\MemberInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected string $redirectTo = '/';

    protected MemberInterface $memberRepository;

    public function __construct(MemberInterface $memberRepository)
    {
        $this->memberRepository = $memberRepository;
    }

    public function register(Request $request, BaseHttpResponse $response)
    {
        $this->validator($request->input())->validate();

        event(new Registered($member = $this->create($request->input())));

        $this->guard()->login($member);

        return $this->registered($request, $member, $response)
            ?: $response->setNextUrl($this->redirectPath());
    }

    protected function validator(array $data)
    {
        return Validator::make($data, array_merge(
            [
                'first_name' => 'required|max:255',
                'last_name' => 'required|max:255',
                'email' => 'required|email|max:255|unique:members',
                'password' => 'required|min:6|confirmed',
            ],
            setting('enable_captcha') && is_plugin_active('captcha') ? ['g-recaptcha-response' => 'required|captcha'] : []
        ));
    }

    protected function create(array $data)
    {
        return $this->memberRepository->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
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
