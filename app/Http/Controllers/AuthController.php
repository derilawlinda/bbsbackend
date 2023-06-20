<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    //
    public function register(RegisterRequest $request){
        $this->authorize('create-delete-users');
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        $usercreate = User::create($validated);
        return response($usercreate);

    }

    public function login(LoginRequest $request){
        if(!Auth::attempt($request->only('email','password'))){
            return response([
                'errors'=>'Invalid credential'
            ],Response::HTTP_UNAUTHORIZED);
        }
        $user = Auth::user();
        $token = $user->createToken('token')->plainTextToken;
        return response([
            'jwt'=>$token,
            'type' => 'bearer'
        ]);
    }

    public function logout(){
        auth()->user()->currentAccessToken()->delete();
        return response([
            'message'=>'succesfully logged out!'
        ]);
    }

    public function checkToken(){
        $user = Auth::user();
        return response([
            'user'=> $user
        ]);
    }


}
