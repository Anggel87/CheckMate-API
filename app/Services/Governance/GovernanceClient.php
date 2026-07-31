<?php

namespace App\Services\Governance;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GovernanceClient
{
    protected function request(): PendingRequest
    {
        return Http::baseUrl((string) config('services.governance.base_url'))
            ->withHeaders([
                'X-Client-Id' => config('services.governance.client_id'),
                'X-Client-Secret' => config('services.governance.client_secret'),
            ])
            ->acceptJson();
    }

    /**
     * @param  array{name: string, email: string, role: string, active?: bool, password?: string}  $data
     * @return array<string, mixed>
     */
    public function createUser(array $data): array
    {
        return $this->request()
            ->post('/internal/users', $data)
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $email, string $password, string $deviceName): array
    {
        return $this->request()
            ->post('/auth/login', [
                'email' => $email,
                'password' => $password,
                'device_name' => $deviceName,
            ])
            ->throw()
            ->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function me(string $token): array
    {
        return $this->request()
            ->withToken($token)
            ->get('/auth/me')
            ->throw()
            ->json();
    }
}
