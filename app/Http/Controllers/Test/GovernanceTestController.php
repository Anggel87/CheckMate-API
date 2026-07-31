<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use App\Services\Governance\GovernanceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GovernanceTestController extends Controller
{
    public function __construct(protected GovernanceClient $governance) {}

    public function createUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'in:profesor,tutor_academico,alumno,administrator,career_director'],
            'active' => ['sometimes', 'boolean'],
            'password' => ['sometimes', 'string'],
        ]);

        return response()->json($this->governance->createUser($data));
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string'],
        ]);

        return response()->json($this->governance->login(
            $data['email'],
            $data['password'],
            $data['device_name'] ?? 'insomnia-test',
        ));
    }

    public function popup(Request $request): RedirectResponse
    {
        $redirectUri = $request->query('redirect_uri', route('test.governance.callback'));

        $url = config('services.governance.popup_url').'?'.http_build_query([
            'client_id' => config('services.governance.client_id'),
            'redirect_uri' => $redirectUri,
        ]);

        return redirect()->away($url);
    }

    public function callback(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Callback recibido desde gobernanza.',
            'query' => $request->query(),
        ]);
    }
}
