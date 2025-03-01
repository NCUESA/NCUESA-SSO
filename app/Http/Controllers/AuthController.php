<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Passport\Token;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    //
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback($provider, Request $request)
    {
        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', '登入失敗，請重試');
        }

        $user = User::updateOrCreate(
            ['email' => $oauthUser->getEmail()],
            [
                'name'        => $oauthUser->getName(),
                'email'       => $oauthUser->getEmail(),
                'provider'    => $provider,
                'provider_id' => $oauthUser->getId(),
            ]
        );

        // **產生短期授權碼 (Authorization Code)**
        $authCode = bin2hex(random_bytes(32));

        // **儲存授權碼對應的使用者 ID (5 分鐘有效)**
        Cache::put("oauth_code:$authCode", $user->id, now()->addMinutes(5));

        // **回傳授權碼給 Web Service**
        return redirect()->away(env('SSO_REDIRECT_URI') . "?code=$authCode");
    }

    public function issueToken(Request $request)
    {
        $authCode = $request->input('code');
        dd($authCode);

        // **檢查授權碼是否有效**（這部分可以保留，如果有額外邏輯處理）
        $userId = Cache::pull("oauth_code:$authCode");

        if (!$userId) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'invalid_user'], 400);
        }

        // **使用 Laravel Passport 發放 Access Token 和 Refresh Token**
        // 這裡可以根據需要生成具體的 Token
        $tokenResult = $user->createToken('SSO Access Token');

        // 取得發放的 Access Token 和 Refresh Token
        $accessToken = $tokenResult->accessToken;
        $refreshToken = $tokenResult->token->getRefreshToken();

        // 返回 Access Token 和 Refresh Token
        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ]);
    }
}
