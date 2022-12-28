<?php

namespace Botble\Comment\Supports;

use Botble\Member\Repositories\Interfaces\MemberInterface;
use Illuminate\Http\Request;

class CloneUserToMember
{
    protected mixed $memberRepository;

    public function __construct()
    {
        $this->memberRepository = app(MemberInterface::class);
    }

    public function handle(Request $request)
    {
        $user = $request->user();

        if (! $user && config('auth.guards.member')) {
            $user = auth()->guard('member')->user();
        }

        if ($user) {
            $member = $this->memberRepository->getFirstBy(['email' => $user->email]);

            if (! $member) {
                return $this->memberRepository->createOrUpdate([
                    'email' => $user->email,
                    'password' => $user->password,
                    'avatar_id' => $user->avatar_id,
                    'user_type' => get_class($user),
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                ]);
            }

            return $member;
        }

        return false;
    }
}
