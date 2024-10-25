<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{



    public function signup(Request $request)
    {
        $fields = $request->validate([
            'username' => 'required|unique:users',
            'mat_no' => 'required|unique:users',
            'password' => 'required'
        ]);

        $user = User::create($fields);

        $token = $user->createToken($request->username);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }

    public function signin(Request $request)
    {
        $request->validate([
            'mat_no' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('mat_no', $request->mat_no)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return [
                'errors' => [
                    'mat_no' => ['The provided credentials are incorrect']
                ]
            ];
        };

        $token = $user->createToken($user->mat_no);

        return [
            'user' => $user,
            'token' => $token->plainTextToken
        ];
    }
    public function signout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'You are logged out.'
        ];
    }
}
