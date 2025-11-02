<?php

namespace App\Http\Controllers;

use App\enum\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

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
            'user' => $findUserByEmail,
            'token' => $this->responseSuccessWithToken($token)
        ]);
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
}
