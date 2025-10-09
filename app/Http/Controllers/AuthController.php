<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function signup(Request $request)
    {
        $fields = $request->validate([
            'username'    => 'required|string|unique:users,username',
            'mat_no'      => 'required|string|unique:users,mat_no',
            'password'    => 'required|string|min:6',
            'campus_code' => 'required|string|exists:campuses,code',
        ]);


        $campusCode = strtoupper(trim($fields['campus_code']));
        $matNo      = strtoupper(trim($fields['mat_no']));

        $campusId = Campus::where('code', $campusCode)->value('id');

        $user = User::create([
            'username'  => trim($fields['username']),
            'mat_no'    => $matNo,
            'password'  => Hash::make($fields['password']),
            'campus_id' => $campusId,
        ]);

        $token = $user->createToken($user->username);

        return ['user' => $user->load('campus'), 'token' => $token->plainTextToken];
    }

    public function signin(Request $request)
    {
        $request->validate([
            'mat_no'   => 'required|string',
            'password' => 'required|string',
        ]);

        $matNo = strtoupper(trim($request->mat_no));
        $user  = User::where('mat_no', $matNo)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // 401 is also reasonable here
            return response()->json(['errors' => ['mat_no' => ['The provided credentials are incorrect']]], 422);
        }

        $token = $user->createToken($user->mat_no);
        return ['user' => $user->load('campus'), 'token' => $token->plainTextToken];
    }

    public function signout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();
        return [
            'message' => 'You are logged out.'
        ];
    }
}
