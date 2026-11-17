<?php

declare(strict_types=1);

final class Max_QR_Api_Exception extends Exception
{
    public readonly string $error_code;

    public readonly mixed $details;

    public readonly ?float $retry_after;

    public readonly ?string $request_id;

    public function __construct(
        string $message,
        public readonly int $status = 0,
        string $error_code = 'api_error',
        mixed $details = null,
        ?float $retry_after = null,
        ?string $request_id = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status, $previous);
        $this->error_code = $error_code;
        $this->details = $details;
        $this->retry_after = $retry_after;
        $this->request_id = $request_id;
    }

    public function is_rate_limited(): bool
    {
        return $this->error_code === 'rate_limited' || $this->status === 429;
    }
}
