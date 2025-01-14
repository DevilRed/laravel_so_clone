<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        if ($request->validated()) {
            $data = $request->validated();
            $data['password'] = bcrypt($data['password']);
            User::create($data);
            return response()->json([
                'message' => 'User created successfully'
            ]);
        }
    }

    /** Login */
    public function auth(AuthUserRequest $request)
    {
        if ($request->validated()) {
            $user = User::whereEmail($request->email)->first();
            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            } else {
                return UserResource::make($user)->additional([
                    'access_token' => $user->createToken('new_user')->plainTextToken
                ]);
            }
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function updateUserInfo(UpdateUserRequest $request)
    {
        if ($request->validated()) {
            if ($request->has('image')) {
                $image = public_path('images/' . $request->user()->image);
                if (File::exists($image)) {
                    File::delete($image);
                }
                $file = $request->file('image');
                $request->user()->image = 'storage/users/images/' . $this->saveImage($file);
            }

            $data = $request->validated();
            $request->user()->update($data);

            return response()->json([
                'message' => 'User updated successfully'
            ]);
            return UserResource::make($request->user())->additional([
                'message' => 'Profile updated successfully'
            ]);
        }
    }

    public function saveImage($file)
    {
        $name = time() . '.' . $file->getClientOriginalName();
        $file->move(public_path('users/images/' . $name), 'public');
        return $name;
    }

    public function updateUserPassword(Request $request)
    {
        $request->validate([
            'current_password' =>
            ['required', 'min:6', 'max:255'],
            'password' => ['required', 'min:6', 'max:255']
        ]);

        if (Hash::check($request->current_password, $request->user()->password)) {
            $request->user()->update([
                'password' => bcrypt($request->password)
            ]);
            return response()->json([
                'message' => 'Password updated successfully'
            ]);
        } else {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 422);
        }
    }
}
