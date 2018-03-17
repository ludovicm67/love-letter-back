<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User;

use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Validator;
use Illuminate\Support\Facades\Password;


class AuthController extends Controller
{
    /**
     * API Register
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $credentials = $request->only('name', 'email', 'password');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ];

        $validator = Validator::make($credentials, $rules);
        if ($validator->fails()) {
            return response()->json([
              'success' => false,
              'error' => $validator->messages()
            ]);
        }

        $name = $request->name;
        $email = $request->email;
        $password = $request->password;

        $user = User::create([
          'name' => $name,
          'email' => $email,
          'password' => bcrypt($password)
        ]);

        return response()->json([
          'success'=> true,
          'message'=> 'Thanks for signing up! You can now login.'
        ]);
    }

}
