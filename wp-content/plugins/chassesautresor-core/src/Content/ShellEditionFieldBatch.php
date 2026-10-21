<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content;

/**
 * Apply edition fields in submission order and stop at the first error.
 */
class ShellEditionFieldBatch {
    /**
     * @param array<string, mixed> $fields
     * @param callable(string, mixed): ?string $apply Error message, or null when the field was stored.
     * @return array{saved:list<string>,error:?string,failed:?string}
     */
    public function apply(array $fields, callable $apply): array {
        $saved = [];

        foreach ($fields as $key => $value) {
            $name = (string) $key;
            if ($name === '') {
                continue;
            }

            $error = $apply($name, $value);
            if ($error !== null) {
                return [
                    'saved' => $saved,
                    'error' => $error,
                    'failed' => $name,
                ];
            }

            $saved[] = $name;
        }

        return [
            'saved' => $saved,
            'error' => null,
            'failed' => null,
        ];
    }
}
