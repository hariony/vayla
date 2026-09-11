<?php

namespace App\DTOs\Content;

use App\Enums\CategoryIcon;

/** Une catégorie du rail, telle que l'équipe la saisit. Sa clé ne se saisit pas : elle naît du libellé. */
final readonly class CategoryDto
{
    public function __construct(
        public string $label,
        public CategoryIcon $icon,
        public bool $sponsored,
    ) {}
}
