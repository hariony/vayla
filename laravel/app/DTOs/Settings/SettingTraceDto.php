<?php

namespace App\DTOs\Settings;

/** Quand un réglage a été changé au back-office, et par qui. */
final readonly class SettingTraceDto
{
    public function __construct(
        public ?string $at,
        public ?int $adminId,
    ) {}
}
