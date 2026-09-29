<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Media;

/**
 * Resolve protected image ownership.
 */
class RiddleImageService
{
    private RiddleImageRepository $repository;

    public function __construct(RiddleImageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function findRiddleId(int $imageId): ?int
    {
        return $imageId > 0 ? $this->repository->findRiddleId($imageId) : null;
    }
}
