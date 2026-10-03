<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\RiddleStepImageRepository;
use ChassesAuTresor\Core\Media\RiddleStepImageService;
use PHPUnit\Framework\TestCase;

final class RiddleStepImageRepositoryStub extends RiddleStepImageRepository {
    public ?int $stepId = null;

    public function __construct() {
    }

    public function findStepId(int $imageId): ?int {
        return $imageId > 0 ? $this->stepId : null;
    }
}

final class RiddleStepImageServiceTest extends TestCase {
    public function testResolvesStepAndRiddleOwnership(): void {
        $repository = new RiddleStepImageRepositoryStub();
        $repository->stepId = 12;
        $service = new RiddleStepImageService($repository);

        self::assertSame(
            ['step_id' => 12, 'riddle_id' => 9],
            $service->findContext(45, static fn (string $field, int $postId): int => 9)
        );
        self::assertNull($service->findContext(0));
    }

    public function testPlayerCanOnlyViewImagesFromVisibleSteps(): void {
        $service = new RiddleStepImageService(new RiddleStepImageRepositoryStub());
        $context = ['step_id' => 12, 'riddle_id' => 9];

        self::assertTrue($service->canViewContext($context, true, [11, 12], false));
        self::assertFalse($service->canViewContext($context, true, [11], false));
        self::assertFalse($service->canViewContext($context, false, [11, 12], false));
        self::assertTrue($service->canViewContext($context, false, [], true));
    }
}
