<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = (string) env('AUTH_EMAIL', '');
        $password = (string) env('AUTH_PASSWORD', '');

        if ($email === '' || $password === '') {
            return response()->json([
                'message' => 'Login não configurado no servidor (AUTH_EMAIL / AUTH_PASSWORD).',
            ], 500);
        }

        $emailOk = hash_equals(Str::lower($email), Str::lower($data['email']));
        $passOk = hash_equals($password, $data['password']);

        if (! $emailOk || ! $passOk) {
            return response()->json([
                'message' => 'E-mail ou senha inválidos.',
            ], 401);
        }

        $payload = [
            'email' => Str::lower($email),
            'exp' => now()->addDays(30)->timestamp,
        ];

        return response()->json([
            'token' => Crypt::encryptString(json_encode($payload)),
            'user' => [
                'email' => $email,
                'name' => 'Masseid',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => [
                'email' => $request->attributes->get('auth_email'),
                'name' => 'Masseid',
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json(['message' => 'Logout ok.']);
    }
}
