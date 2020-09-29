<?php

namespace App\Http\Controllers\User;

use App\Http\Resources\UserResource;
use App\Model\Company;
use App\Traits\ApiResponser;
use App\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    use ApiResponser;

    public function __construct()
    {

        $this->middleware('auth:user', ['except' => ['login', 'register', 'checkToken']]);
    }


    public function login(Request $request){


        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            return $this->respondWithToken($token, $request->email);

        }

        return $this->errorResponse('Unauthorized.. Wrong Credentials', 401);

    }


    public function register(Request $request){

        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users',
            'phone' => 'required',
            'password' => 'required|string',
            'company_name' => 'required|string',
            'country' => 'required|string',
            'industry' => 'required|string',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
        ]);

        $user->save();

        $company = new Company;

        $company->name = $request->company_name;
        $company->slug = str_slug($request->company_name) . '-' . Str::random(6);
        $company->user_id = $user->id;
        $company->country = $request->country;
        $company->industry = $request->industry;

        $company->save();

        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {

            return $this->respondWithToken($token, $request->email);

        }

        return response()->json(['error' => 'An  Error Occurred'], 401);

    }

    public function me()
    {
        $user_res = new UserResource($this->guard()->user());

        return $this->showOne($user_res);


    }


    public function getUser(User $user){

        $user_res = new UserResource($user);

        return $this->showOne($user_res);

    }


    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }



    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    public function checkToken(Request $request){

        $token= str_replace('Bearer ', "" , $request->header('Authorization'));

        try {
            JWTAuth::setToken($token); //<-- set token and check
            if (! $claim = JWTAuth::getPayload()) {
                return response()->json(array('message'=>'user_not_found'), 404);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(array('message'=>'token_expired'), 404);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(array('message'=>'token_invalid'), 404);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(array('message'=>'token_absent'), 404);
        }

        return response()->json(array('message'=>'Token is Valid'), 200);


    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token, $email)
    {
        $user = User::where('email', $email)->first();

        $user_res = new UserResource($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user_res
        ]);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard('user');
    }


}
