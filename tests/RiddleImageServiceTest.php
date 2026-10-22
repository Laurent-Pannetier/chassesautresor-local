<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleImageRepository;
use ChassesAuTresor\Core\Media\RiddleImageService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageRepository.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageService.php';

class RiddleImageRepositoryStub extends RiddleImageRepository
{
    public int $imageId = 0;

    public function __construct()
    {
    }

    public function findRiddleId(int $imageId): ?int
    {
        $this->imageId = $imageId;

        return 31;
    }
}

class RiddleImageServiceTest extends TestCase
{
    public function testRiddleLookupIsDelegatedForAValidImage(): void
    {
        $repository = new RiddleImageRepositoryStub();
        $service = new RiddleImageService($repository);

        $this->assertSame(31, $service->findRiddleId(12));
        $this->assertSame(12, $repository->imageId);
    }

    public function testInvalidImageDoesNotReachRepository(): void
    {
        $repository = new RiddleImageRepositoryStub();
        $service = new RiddleImageService($repository);

        $this->assertNull($service->findRiddleId(0));
        $this->assertSame(0, $repository->imageId);
    }
}

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RiddleImageRepositoryLookupTest extends TestCase
{
    public function testAcfLookupSkipsShowTablesAndReusesTheCache(): void
    {
        $GLOBALS['cta_riddle_image_cache'] = [];
        if (!function_exists('wp_cache_get')) {
            eval(<<<'PHP'
function wp_cache_get($key, $group) {
    return $GLOBALS['cta_riddle_image_cache'][$group . ':' . $key] ?? false;
}
function wp_cache_set($key, $value, $group, $ttl = 0) {
    $GLOBALS['cta_riddle_image_cache'][$group . ':' . $key] = $value;
    return true;
}
function wp_cache_delete($key, $group) {
    unset($GLOBALS['cta_riddle_image_cache'][$group . ':' . $key]);
    return true;
}
PHP);
        }

        $wpdb = new class {
            public string $prefix = 'wp_';
            public string $postmeta = 'wp_postmeta';
            public string $last_error = '';
            public int $acfQueries = 0;
            public int $metaQueries = 0;

            public function suppress_errors(bool $suppress = true): bool
            {
                return false;
            }

            public function prepare(string $query, mixed ...$args): string
            {
                return $query;
            }

            public function esc_like(string $text): string
            {
                return $text;
            }

            public function get_var(string $query): ?string
            {
                if (str_contains($query, 'SHOW TABLES')) {
                    $this->acfQueries++;
                    return null;
                }
                if (str_contains($query, 'acf_enigme_visuel_image')) {
                    $this->acfQueries++;
                    $this->last_error = '';
                    return '31';
                }
                $this->metaQueries++;
                return null;
            }
        };

        $repository = new RiddleImageRepository($wpdb);
        $this->assertSame(31, $repository->findRiddleId(12));
        $this->assertSame(31, $repository->findRiddleId(12));
        $this->assertSame(1, $wpdb->acfQueries);
        $this->assertSame(0, $wpdb->metaQueries);
        $this->assertStringNotContainsString('SHOW TABLES', file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/RiddleImageRepository.php'
        ));

        RiddleImageRepository::forgetImage(12);
        $this->assertSame(31, $repository->findRiddleId(12));
        $this->assertSame(2, $wpdb->acfQueries);
    }
}
