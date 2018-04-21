<?php
namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Validator;

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
    $credentials = $request->only('name', 'password');

    $rules = [
      'name' => 'required|string|max:255|unique:players,name',
      'password' => 'required|string|min:1'
    ];

    $validator = Validator::make($credentials, $rules);
    if ($validator->fails()) {
      return response()->json(
        ['success' => false, 'error' => $validator->messages()],
        400
      );
    }

    $name = $request->name;
    $password = $request->password;

    $user = User::create(['name' => $name, 'password' => bcrypt($password)]);

    try {
      // attempt to verify the credentials and create a token for the user
      if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(
          [
            'success' => false,
            'error' => 'Unable to log you after registration. Maybe something was broken during your registration process. Please try again.'
          ],
          401
        );
      }
    } catch (JWTException $e) {
      // something went wrong whilst attempting to encode the token
      return response()->json(
        [
          'success' => false,
          'error' => 'Account created successfully, but failed to login, please try again.'
        ],
        500
      );
    }

    return response()->json(
      [
        'success' => true,
        'message' => 'Thanks for signing up! You can now login.',
        'data' => ['token' => $token, 'user' => auth()->user()]
      ],
      201
    );
  }

  /**
   * API Login, on success return JWT Auth token
   *
   * @param Request $request
   * @return \Illuminate\Http\JsonResponse
   */
  public function login(Request $request)
  {
    $credentials = $request->only('name', 'password');

    $rules = [
      'name' => 'required|string|max:255',
      'password' => 'required|string|min:1'
    ];
    $validator = Validator::make($credentials, $rules);
    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'error' => $validator->messages()
      ]);
    }

    try {
      // attempt to verify the credentials and create a token for the user
      if (!$token = JWTAuth::attempt($credentials)) {
        return response()->json(
          [
            'success' => false,
            'error' => 'We cant find an account with this credentials. Please make sure you entered the right informations.'
          ],
          401
        );
      }
    } catch (JWTException $e) {
      // something went wrong whilst attempting to encode the token
      return response()->json(
        ['success' => false, 'error' => 'Failed to login, please try again.'],
        500
      );
    }

    // all good so return the token
    return response()->json([
      'success' => true,
      'data' => ['token' => $token, 'user' => auth()->user()]
    ]);
  }

  /**
   * Log out
   * Invalidate the token, so user cannot use it anymore
   * They have to relogin to get a new token
   *
   * @param Request $request
   */
  public function logout(Request $request)
  {
    $this->validate($request, ['token' => 'required']);

    try {
      JWTAuth::invalidate($request->input('token'));
      return response()->json([
        'success' => true,
        'message' => "You have successfully logged out."
      ]);
    } catch (JWTException $e) {
      // something went wrong whilst attempting to encode the token
      return response()->json(
        ['success' => false, 'error' => 'Failed to logout, please try again.'],
        500
      );
    }
  }

  public function me(Request $request) {
    $this->validate($request, ['token' => 'required']);
    $token = $request->input('token');

    try {
      $user = JWTAuth::authenticate($token);
    } catch (TokenExpiredException $e) {
      try {
        $token = JWTAuth::refresh($token);
        JWTAuth::setToken($token);
        $user = JWTAuth::authenticate($token);
      } catch(TokenExpiredException $e) {
        return response()->json([
          'success' => false,
          'message' => "need to login again"
        ], 401);
      }
    }

    return response()->json([
      'success' => true,
      'data' => [
        'token' => $token,
        'user' => $user
      ]
    ]);
  }
}
