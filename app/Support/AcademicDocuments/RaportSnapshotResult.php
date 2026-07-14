<?php

namespace App\Support\AcademicDocuments;

use Illuminate\Validation\ValidationException;
use JsonException;

final class RaportSnapshotResult
{
    /**
     * @param array<string, mixed> $snapshot
     * @param array<int, array{code:string, message:string}> $blockers
     * @param array<int, array{code:string, message:string}> $warnings
     */
    public function __construct(
        private readonly array $snapshot,
        private readonly array $blockers = [],
        private readonly array $warnings = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        return $this->snapshot;
    }

    /**
     * @return array<int, array{code:string, message:string}>
     */
    public function blockers(): array
    {
        return $this->blockers;
    }

    /**
     * @return array<int, array{code:string, message:string}>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasBlockers(): bool
    {
        return $this->blockers !== [];
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    public function canCreateDraft(): bool
    {
        return !$this->hasBlockers();
    }

    /**
     * @throws ValidationException
     */
    public function assertCanCreateDraft(): void
    {
        if (!$this->hasBlockers()) {
            return;
        }

        throw ValidationException::withMessages([
            'raport' => array_map(
                static fn(array $blocker): string =>
                    $blocker['message'],
                $this->blockers
            ),
        ]);
    }

    /**
     * @throws JsonException
     */
    public function snapshotSha256(): string
    {
        return hash(
            'sha256',
            $this->canonicalJson()
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function documentPayload(): array
    {
        return [
            'snapshot_json' => $this->snapshot,
            'snapshot_sha256' => $this->snapshotSha256(),
            'metadata' => [
                'snapshot_blockers' => $this->blockers,
                'snapshot_warnings' => $this->warnings,
                'snapshot_schema_version' =>
                    $this->snapshot['schema_version'] ?? null,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function toArray(): array
    {
        return [
            'ok' => !$this->hasBlockers(),
            'can_create_draft' => $this->canCreateDraft(),
            'snapshot_sha256' => $this->snapshotSha256(),
            'blockers' => $this->blockers,
            'warnings' => $this->warnings,
            'snapshot' => $this->snapshot,
        ];
    }

    /**
     * @throws JsonException
     */
    private function canonicalJson(): string
    {
        return json_encode(
            $this->canonicalize($this->snapshot),
            JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    private function canonicalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn(mixed $item): mixed =>
                    $this->canonicalize($item),
                $value
            );
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] =
                $this->canonicalize($item);
        }

        return $value;
    }
}
