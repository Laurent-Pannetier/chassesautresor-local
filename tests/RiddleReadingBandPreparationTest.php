<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleReadingBandAjaxHandler;
use ChassesAuTresor\Core\Media\RiddleReadingBandPreparation;
use PHPUnit\Framework\TestCase;

final class RiddleReadingBandPreparationTest extends TestCase
{
    public function testPrepareCutsOnlyVisibleRiddleStrips(): void
    {
        $directory = sys_get_temp_dir() . '/_enigmes/prep-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $strip = $directory . '/a.webp';
        $foreign = sys_get_temp_dir() . '/foreign-' . uniqid('', true) . '.webp';
        file_put_contents($strip, 'x');
        file_put_contents($foreign, 'x');
        $ensured = [];

        try {
            $done = (new RiddleReadingBandPreparation())->prepare(
                [4, 4, 9, 5],
                static fn (int $id): bool => $id === 4 || $id === 5,
                static function (int $id) use ($strip, $foreign): string {
                    if ($id === 4) {
                        return $strip;
                    }
                    if ($id === 5) {
                        return $foreign;
                    }

                    return '';
                },
                static function (string $file) use (&$ensured): array {
                    $ensured[] = $file;

                    return [['ready' => true]];
                }
            );

            $this->assertSame([4], $done);
            $this->assertSame([$strip], $ensured);
        } finally {
            @unlink($strip);
            @unlink($foreign);
            @rmdir($directory);
        }
    }

    public function testPrepareLeavesAnUnfinishedStripPending(): void
    {
        $directory = sys_get_temp_dir() . '/_enigmes/prep-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $strip = $directory . '/a.webp';
        file_put_contents($strip, 'x');

        try {
            $done = (new RiddleReadingBandPreparation())->prepare(
                [4],
                static fn (int $id): bool => true,
                static fn (int $id): string => $strip,
                static fn (string $file): array => [
                    ['ready' => true],
                    ['ready' => false],
                ]
            );

            $this->assertSame([], $done);
        } finally {
            @unlink($strip);
            @rmdir($directory);
        }
    }

    public function testPrepareStopsAfterTheImageCap(): void
    {
        $directory = sys_get_temp_dir() . '/_enigmes/prep-' . uniqid('', true);
        mkdir($directory, 0777, true);
        $strip = $directory . '/a.webp';
        file_put_contents($strip, 'x');
        $calls = 0;

        try {
            $done = (new RiddleReadingBandPreparation())->prepare(
                range(1, RiddleReadingBandPreparation::MAX_IMAGES + 1),
                static fn (int $id): bool => true,
                static fn (int $id): string => $strip,
                static function (string $file) use (&$calls): array {
                    $calls++;

                    return [['ready' => true]];
                }
            );

            $this->assertSame(range(1, RiddleReadingBandPreparation::MAX_IMAGES), $done);
            $this->assertSame(RiddleReadingBandPreparation::MAX_IMAGES, $calls);
        } finally {
            @unlink($strip);
            @rmdir($directory);
        }
    }

    public function testRegisterListensForVisitorsAndMembers(): void
    {
        $hooks = [];
        RiddleReadingBandAjaxHandler::register(static function () use (&$hooks): void {
            $hooks[] = func_get_args();
        });

        $this->assertSame('wp_ajax_cta_prepare_riddle_bands', $hooks[0][0]);
        $this->assertSame('wp_ajax_nopriv_cta_prepare_riddle_bands', $hooks[1][0]);
    }
}
