<?php

declare(strict_types=1);

namespace TheOneDigi\TourSdk\Common;

use JsonSerializable;

interface RequestPayload extends JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
