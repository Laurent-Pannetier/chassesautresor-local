<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\ShellEditionFieldBatch;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/ShellEditionFieldBatch.php';

final class ShellEditionFieldBatchTest extends TestCase {
    public function testAppliesFieldsInOrder(): void {
        $applied = [];

        $result = (new ShellEditionFieldBatch())->apply(
            ['post_title' => 'Nord', 'enigme_style_affichage' => 'carte'],
            static function (string $field, $value) use (&$applied): ?string {
                $applied[] = [$field, $value];

                return null;
            }
        );

        $this->assertSame([
            ['post_title', 'Nord'],
            ['enigme_style_affichage', 'carte'],
        ], $applied);
        $this->assertSame(['post_title', 'enigme_style_affichage'], $result['saved']);
        $this->assertNull($result['error']);
        $this->assertNull($result['failed']);
    }

    public function testStopsAtFirstErrorAndSkipsTheRest(): void {
        $applied = [];

        $result = (new ShellEditionFieldBatch())->apply(
            ['post_title' => 'Nord', 'enigme_mode_validation' => 'aucune', 'enigme_style_affichage' => 'carte'],
            static function (string $field, $value) use (&$applied): ?string {
                $applied[] = $field;
                if ($field === 'enigme_mode_validation') {
                    return '⚠️ valeur_invalide';
                }

                return null;
            }
        );

        $this->assertSame(['post_title', 'enigme_mode_validation'], $applied);
        $this->assertSame(['post_title'], $result['saved']);
        $this->assertSame('⚠️ valeur_invalide', $result['error']);
        $this->assertSame('enigme_mode_validation', $result['failed']);
    }

    public function testIgnoresEmptyFieldNames(): void {
        $applied = [];

        $result = (new ShellEditionFieldBatch())->apply(
            ['' => 'ignore', 'post_title' => 'Nord'],
            static function (string $field, $value) use (&$applied): ?string {
                $applied[] = $field;

                return null;
            }
        );

        $this->assertSame(['post_title'], $applied);
        $this->assertSame(['post_title'], $result['saved']);
    }
}
