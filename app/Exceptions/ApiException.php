<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public function __construct(
        string $message,
        protected int $httpStatus,
        protected ?string $errorCode = null,
        protected ?array $errors = null,
    ) {
        parent::__construct($message);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function notFound(string $message, ?string $errorCode = null, ?array $errors = null): self
    {
        return new self($message, 404, $errorCode, $errors);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function forbidden(string $message, ?string $errorCode = null, ?array $errors = null): self
    {
        return new self($message, 403, $errorCode, $errors);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function conflict(string $message, ?string $errorCode = null, ?array $errors = null): self
    {
        return new self($message, 409, $errorCode, $errors);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function unauthorized(string $message, ?string $errorCode = null, ?array $errors = null): self
    {
        return new self($message, 401, $errorCode, $errors);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function unprocessable(string $message, ?string $errorCode = null, ?array $errors = null): self
    {
        return new self($message, 422, $errorCode, $errors);
    }

    public static function payloadTooLarge(string $message, ?string $errorCode = null): self
    {
        return new self($message, 413, $errorCode);
    }

    public static function unsupportedMediaType(string $message, ?string $errorCode = null): self
    {
        return new self($message, 415, $errorCode);
    }

    public function render(Request $request): JsonResponse
    {
        $payload = [
            'success' => false,
            'status_code' => $this->httpStatus,
            'message' => $this->getMessage(),
            'data' => null,
            'errors' => $this->errors,
            'meta' => [
                'request_id' => $request->headers->get('X-Request-ID') ?? uniqid('req_'),
                'api_version' => 'v1',
                'timestamp' => now()->toIso8601String(),
            ],
        ];

        if ($this->errorCode !== null) {
            $payload['error_code'] = $this->errorCode;
        }

        return response()->json($payload, $this->httpStatus);
    }
}
