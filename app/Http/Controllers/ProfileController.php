<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Content negotiation (F-05): browsers receive the profile view;
     * JSON clients keep the H-04 contract response unchanged.
     */
    public function show(Request $request): JsonResponse|View
    {
        $user = $request->user();

        if ($request->expectsJson()) {
            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'message' => 'تم تحميل الملف الشخصي.',
            ]);
        }

        return view('profile.show', ['user' => $user]);
    }
}
