<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Jobs\BulkUserCreateJob;


class UserController extends Controller
{
    //

    public function list(Request $request)
    {
        $user = $request->user();

        return Cache::remember("user-list-{$user->id}", 60, function () use ($user) {
            if ($user->role->name === 'SuperAdmin') {
                return User::with('role')->get();
            } elseif ($user->role->name === 'Admin') {
                return User::with('role')->whereHas('role', fn($q) => $q->where('name', 'User'))->get();
            } else {
                return User::with('role')->where('id', $user->id)->get();
            }
        });
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->only(['name', 'email']));
        return response()->json(['message' => 'User updated', 'user' => $user]);
    }

    public function delete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User soft-deleted']);
    }
    public function store(Request $request)
    {
        dispatch(new BulkUserCreateJob($request->users));
        return response()->json(['message' => 'User creation in queue']);
    }
}
