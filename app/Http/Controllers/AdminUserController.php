<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = User::query()
            ->with('campus') // if you have campus relationship
            ->orderByDesc('id');

        if ($search = $request->query('q')) {
            $q->where(function ($w) use ($search) {
                $w->where('username', 'like', "%{$search}%")
                    ->orWhere('mat_no', 'like', "%{$search}%");
            });
        }

        $users = $q->paginate((int) $request->query('per_page', 20));
        return response()->json($users);
    }

    public function store(Request $request)
    {
        // Validate clearly; return 422 on bad input (RN will show this)
        $data = $request->validate([
            'username'  => ['required', 'string', 'max:100', Rule::unique('users', 'username')],
            'mat_no'    => ['required', 'string', 'max:100', Rule::unique('users', 'mat_no')],
            'password'  => ['required', 'string', 'min:6'],
            'user_type' => ['required', Rule::in(['user', 'admin'])],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ]);

        // Normalize
        $data['mat_no'] = strtoupper(trim($data['mat_no']));

        try {
            return DB::transaction(function () use ($data) {
                $user = new User();
                $user->username  = $data['username'];
                $user->mat_no    = $data['mat_no'];
                $user->password  = Hash::make($data['password']);
                $user->user_type = $data['user_type'];
                $user->campus_id = $data['campus_id'] ?? null;
                $user->save();

                return response()->json($user, 201);
            });
        } catch (\Throwable $e) {
            // Log full error and return a readable message
            Log::error('Admin create user failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Server error while creating user.',
            ], 500);
        }
    }

    public function show(User $user)
    {
        return $user->load('campus');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username'  => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'mat_no'    => ['required', 'string', 'max:100', Rule::unique('users', 'mat_no')->ignore($user->id)],
            'password'  => ['nullable', 'string', 'min:6'],
            'user_type' => ['required', Rule::in(['user', 'admin'])],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
        ]);

        $user->username  = $data['username'];
        $user->mat_no    = strtoupper(trim($data['mat_no']));
        $user->user_type = $data['user_type'];
        $user->campus_id = $data['campus_id'] ?? null;
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return response()->json($user->load('campus'));
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'user_type' => ['required', 'in:user,admin'],
        ]);

        // Prevent changing your own role away from admin
        if ($request->user()->is($user) && $validated['user_type'] !== 'admin') {
            return response()->json(['message' => 'You cannot change your own role.'], 422);
        }

        // Persist safely
        $user->forceFill(['user_type' => $validated['user_type']])->save();

        return response()->json([
            'message' => 'Role updated',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {
        // guard deleting self if needed
        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'You cannot delete yourself.'], 422);
        }
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
