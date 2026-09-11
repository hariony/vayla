<?php

namespace App\Data\Office;

use App\Models\AdminAction;
use Spatie\LaravelData\Data;

/**
 * Une ligne du journal. **Le nom de l'administrateur est recopié** sur la ligne
 * (`admin_name`) : un membre retiré ne rend pas ses décisions anonymes.
 */
final class JournalLineData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $kind,
        public readonly string $label,
        public readonly string $famille,
        public readonly string $admin,
        public readonly string $summary,
        public readonly ?string $note,
        public readonly ?JournalSubjectData $subject,
        public readonly string $at,
    ) {}

    public static function fromModel(AdminAction $a): self
    {
        return new self(
            id: $a->id,
            kind: $a->kind->value,
            label: $a->kind->label(),
            famille: $a->kind->famille(),
            admin: (string) $a->admin_name,
            summary: $a->summary,
            note: $a->note,
            subject: $a->subject_type ? new JournalSubjectData($a->subject_type, (int) $a->subject_id) : null,
            at: $a->created_at->toIso8601String(),
        );
    }
}
