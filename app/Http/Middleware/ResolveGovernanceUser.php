<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\User;
use App\Services\Governance\GovernanceClient;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ResolveGovernanceUser
{
    public function __construct(protected GovernanceClient $governance) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw ApiException::unauthorized('Tu sesión ha expirado. Inicia sesión nuevamente.', 'AUTH05');
        }

        $governanceUserId = $this->resolveGovernanceUserId($token);

        if ($governanceUserId === null) {
            throw ApiException::unauthorized('Tu sesión ha expirado. Inicia sesión nuevamente.', 'AUTH05');
        }

        $user = User::where('governance_user_id', $governanceUserId)->first();

        if ($user === null) {
            throw ApiException::forbidden('Tu cuenta no está vinculada a un perfil local todavía.');
        }

        if (! $user->active) {
            throw ApiException::forbidden('Tu cuenta está desactivada. Contacta al administrador.', 'AUTH03');
        }

        Auth::setUser($user);

        return $next($request);
    }

    /**
     * Resolves and caches the governance user id for this token.
     *
     * A single browser page load fires several API requests in parallel with the same
     * token. Without a lock, every one of them would miss a cold cache at the same time
     * and independently call out to Gobernanza — this makes the first request after the
     * cache expires (or the first request after login) pay that round trip once, and lets
     * the rest wait for and reuse that same result instead of repeating it.
     */
    private function resolveGovernanceUserId(string $token): ?int
    {
        $ttl = (int) config('services.governance.auth_cache_ttl', 120);
        $key = 'governance:auth:'.hash('sha256', $token);

        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $resolve = fn () => Cache::remember($key, $ttl, function () use ($token) {
            try {
                $response = $this->governance->me($token);
            } catch (RequestException|ConnectionException) {
                return null;
            }

            return $response['data']['user']['id'] ?? null;
        });

        try {
            return Cache::lock('lock:'.$key, 10)->block(5, $resolve);
        } catch (LockTimeoutException) {
            return $resolve();
        }
    }
}
