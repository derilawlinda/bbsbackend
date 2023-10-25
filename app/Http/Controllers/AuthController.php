<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;


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

    public function logout(Request $request){
        $accessToken = $request->bearerToken();
        $token = PersonalAccessToken::findToken($accessToken);
        $token->delete();
        return response([
            'message'=>'succesfully logged out!'
        ]);
    }

    public function checkToken(){
        $user = Auth::user();
        $role = $user->role()->get();
        return response([
            'user'=> $user,
            'role'=> $role
        ]);
    }

    public function getUsers(){
        $users = User::where('id','>', 289)
        ->orderBy('id')->get();

        return response($users);
    }


}
