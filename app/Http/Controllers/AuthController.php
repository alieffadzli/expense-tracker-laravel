<?php

namespace App\Http\Controllers;

use App\enum\UserRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as OAuth2User;

class AuthController extends Controller
{
    public function signUp (Request $request)
    {
        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:225',
            'email' => 'required|email|unique:users',
            'email_verified_at' => 'nullable|date',
            'avatar' => 'nullable|string',
            'password' => 'required|string|min:6|max:18|confirmed',
            'role' => ['nullable', new Enum(UserRole::class)],
        ]);

        if ($validate->fails()) 
            return ResponseController::failsResponse('Validation error', $validate->errors(), null, 422);

        $data = $validate->validated();
        $hashedPassword = Hash::make($data['password']);
        $newUser = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $hashedPassword,
            'role' => $data['role']
        ]);
        
        try {
            $token = JWTAuth::fromUser($newUser);
            return ResponseController::successResponse('Create user successful', [
                'user' => $newUser, 
                'token' => $this->responseSuccessWithToken($token)
            ]);
        } catch (JWTException $err) {
            return ResponseController::failsResponse('Create user successful', $err->getMessage(), null, 401);
        }
    }

    public function signIn (Request $request)
    {   
        $validate = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validate->fails()) 
            return ResponseController::failsResponse('Validation error', $validate->errors(), null, 422);

        $data = $validate->validated();
        try {
            $token = JWTAuth::attempt($data);
            if (!$token) return ResponseController::failsResponse('Invalid credentials', '', null, 401);
        } catch (JWTException $err) {
            return ResponseController::failsResponse('Could not create token', $err->getMessage(), null, 500);
        }

        $findUserByEmail = User::query()->where('email', $data['email'])->first();
        return ResponseController::successResponse('Sign in sucessful', [
            'user' => [
                'id' => $findUserByEmail->id,
                'name' => $findUserByEmail->name,
                'email' => $data['email'],
                'email_verified_at' => $findUserByEmail->email_verified_at
            ],
            'access_token' => $token
        ])->cookie('refresh_token',    // nama cookie
                    $token,             // isi JWT
                    60 * 24,            // durasi (menit)
                    '/',                // path
                    'localhost',        // domain (WAJIB di-set biar cookie bisa di-share antar port)
                    false,              // secure = false (karena masih http localhost)
                    true,               // httpOnly
                    false,              // raw
                    'None'              // SameSite=None → wajib untuk cross-origins // path, domain, secure, httpOnly, raw, sameSite);
        );
    }

    public function me ()
    {
        if (!$data = JWTAuth::parseToken()->authenticate()) 
            return ResponseController::failsResponse('Failed get user', 'Session expired', null, 401);
        return ResponseController::successResponse('Success get user', $data, 200);
    }

    public static function getCurrentUser ()
    {
        $currentUser = JWTAuth::parseToken()->authenticate();
        if (!$currentUser) return null; 
        return $currentUser;
    }

    public function refresh ()
    {
        $newToken = JWTAuth::refresh(JWTAuth::getToken());
        try {
            return ResponseController::successResponse('Access_token is refreshed', $newToken, 201);
        } catch (JWTException $err) {
            return ResponseController::failsResponse('Failed to refresh token', $err->getMessage(), null, 401);
        }
    }

    public function responseSuccessWithToken (String $access_token)
    {   
        return [
            'access_token' => $access_token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ];
    }

    public function googleRedirect () {
        return Socialite::driver('google')->scopes(['openid', 'profile', 'email'])
                                          ->stateless()
                                          ->redirect();
    }

    public function googleCallback () {
        try {
            /** @var OAuth2User $google_user */
            $google_user = Socialite::driver('google')->stateless()->user();
        } catch (InvalidStateException $exception) {
            abort(400, $exception->getMessage());
        }

        $user = User::updateOrCreate([
            'name' => $google_user->name,
            'email' => $google_user->email,
            'avatar' => $google_user->avatar,
            'role' => 'employe',
            'email_verified_at' => Carbon::now(),
            'social_auth' => 'google',
            'google_id' => $google_user->id,
        ]);

        $token = JWTAuth::fromUser($user);
        
        return response()->json([
            'token' => $token,
            'google_user' => $google_user->user
        ]);
    }
}
