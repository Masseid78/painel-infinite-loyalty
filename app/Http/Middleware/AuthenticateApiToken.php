<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        $token = trim(substr($header, 7));

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return response()->json(['message' => 'Sessão inválida.'], 401);
        }

        if (! is_array($payload) || empty($payload['email']) || empty($payload['exp'])) {
            return response()->json(['message' => 'Sessão inválida.'], 401);
        }

        if ((int) $payload['exp'] < now()->timestamp) {
            return response()->json(['message' => 'Sessão expirada.'], 401);
        }

        $allowed = strtolower((string) env('AUTH_EMAIL', ''));
        if ($allowed === '' || strtolower((string) $payload['email']) !== $allowed) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $request->attributes->set('auth_email', $payload['email']);

        return $next($request);
    }
}
