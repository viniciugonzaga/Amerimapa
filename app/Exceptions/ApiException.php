<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Erro de negócio/HTTP devolvido como { "error": { code, message, details? } },
 * com os mesmos códigos da API Node do Matheus.
 */
class ApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly mixed $details = null,
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $message, mixed $details = null): self
    {
        return new self(400, 'BAD_REQUEST', $message, $details);
    }

    public static function unauthorized(string $message = 'Autenticação necessária.'): self
    {
        return new self(401, 'UNAUTHORIZED', $message);
    }

    public static function forbidden(string $message = 'Você não tem permissão para esta ação.'): self
    {
        return new self(403, 'FORBIDDEN', $message);
    }

    public static function notFound(string $resource, int|string|null $id = null): self
    {
        return new self(404, 'NOT_FOUND', $id === null ? "{$resource} não encontrado(a)." : "{$resource} com id {$id} não encontrado(a).");
    }

    public static function conflict(string $message, string $code = 'CONFLICT'): self
    {
        return new self(409, $code, $message);
    }

    /** Requisição válida que viola uma regra de negócio (RN01..RN22). */
    public static function rule(string $code, string $message, mixed $details = null): self
    {
        return new self(422, $code, $message, $details);
    }

    public static function unavailable(string $code, string $message): self
    {
        return new self(503, $code, $message);
    }

    public function toArray(): array
    {
        $body = ['code' => $this->errorCode, 'message' => $this->getMessage()];
        if ($this->details !== null) {
            $body['details'] = $this->details;
        }

        return $body;
    }
}
