<?php

namespace App\Enums;

class ApiResponseStatus
{
    public function __construct(public bool $status, public int $code, public mixed $data)
    {

    }


    public static function timeSigned(string $time): self
    {
        return new self(true, 200, ['time' => $time]);
    }

    public function getResponse(): array
    {
        return [
            'success' => $this->status,
            'code' => $this->code,
            'data' => $this->data
        ];
    }
}
