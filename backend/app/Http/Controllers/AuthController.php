<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'refresh', 'register']]);
    }


    public function login()
    {
        try {
            $credentials = request(['email', 'password']);
            // Xác thực và tạo access token
            if (!$token = auth('api')->attempt($credentials)) { // thực hiện xác thực với thông tin đăng nhập được cung cấp. Nếu xác thực thành công thì sẽ trả về một token JWT.            
                return response()->json(['error' => 'Người dùng không tồn tại'], 401);
            }
            // Tạo refresh token
            // playload refresh token
            $data = [
                'user_id' => auth('api')->user()->id,
                'exp' => time() + config('jwt.refresh_ttl')
            ];
            // create refresh token
            $refreshToken = JWTAuth::getJWTProvider()->encode($data);
            return $this->respondWithToken($token, $refreshToken);
        } catch (JWTException $e) {
            return response()->json($e);
        }
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            "name" => "required",
            "email" => "required|unique:users,email",
            "password" => "required"
        ], [
            "name.required" => "Không được để trống tên đăng nhập",
            "email.required" => "Không được để trống email",
            "email.unique" => "Email đã tồn tại trên hệ thống",
            "password" => "Vui lòng nhập password"
        ]);
        $user = User::create([
            "name" => $request->name,
            "email" => $request->email,
            "password" => Hash::make($request->password)
        ]);
        return response()->json($user);
    }


    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }


    protected function profile()
    {
        return response()->json(auth('api')->user());
        // $user = auth('api')->user();
        // $rolesOfUser = $user->roles;
        // return response()->json($rolesOfUser);

    }


    public function refresh()
    {
        $refreshToken = request('refresh_token');
        try {
            $decode = JWTAuth::getJWTProvider()->decode($refreshToken);
            $user = User::find($decode['user_id']);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            // Đưa access token hiện tại vào blacklist
            // ....
            // Tạo mới access token
            $accessTokenNew = auth('api')->login($user);
            return response()->json(['access_token' => $accessTokenNew]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Refresh token invalid'], 500);
        }
    }


    protected function respondWithToken($accessToken, $refreshToken)
    {
        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ]);
    }
}
