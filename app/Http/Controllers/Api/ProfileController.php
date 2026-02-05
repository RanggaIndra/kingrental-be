<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{   
    public function show(Request $request)
    {
        return response()->json([
            'message' => 'Data profile berhasil diambil.',
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => 'sometimes|string|max:15',
            'address' => 'sometimes|string',
            'ktp_image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('ktp_image')) {
            if ($user->ktp_image_path && Storage::disk('public')->exists($user->ktp_image_path)) {
                Storage::disk('public')->delete($user->ktp_image_path);
            }

            $path = $request->file('ktp_image')->store('ktp_images', 'public');

            $validated['ktp_image_path'] = $path;
        }

        unset($validated['ktp_image']);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile berhasil diperbaharui.',
            'user' => $user->fresh(),
        ]);
    }
}
