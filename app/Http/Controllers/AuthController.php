<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use JWTAuth;

class AuthController extends Controller
{
    public function store()
    {
    	
    	$this->validate(request(), [
    		'name' => 'required',
    		'email' => 'required|email',
    		'password' => 'required|min:6'
		]);

    	$name = request('name');
    	$email = request('email');
    	$password = request('password');

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt($password)
        ]);
        if($user->save()) {
            $user->signin = [
                'href' => 'api/v1/user/signin',
                'method' => 'POST',
                'params' => ['email', 'password']
            ];

            $response = [
                'msg' => 'User created',
                'user' => $user
            ];

            return response()->json($response, 201);
        }


    	$response = [
    		'msg' => 'An error occured',
    	];

    	return response()->json($response, 404);
    }

    public function signin()
    {
    	$this->validate(request(), [
    		'email' => 'required',
    		'password' => 'required'
		]);
    	$email = request('email');
    	$password = request('password');

        $user = [
            'name' => 'Name',
            'email' => $email,
            'password' => $password
        ];

        $response = [
            'msg'=> 'User signed in',
            'user' => $user
        ];

        $credentials = request()->only('email', 'password');

        try {
            if(!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['msg' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['msg' => 'Could not create token'], 500);
        }

    	return response()->json(['token' => $token], 200);
    }
}
