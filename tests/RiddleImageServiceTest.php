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
