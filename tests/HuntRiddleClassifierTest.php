<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\HuntRiddleClassifier;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Progress/HuntRiddleClassifier.php';

class HuntRiddleClassifierFixture extends HuntRiddleClassifier
{
    protected function getValidationMode(int $riddleId): string
    {
        return $riddleId === 12 ? 'aucune' : 'automatique';
    }
}

class HuntRiddleClassifierTest extends TestCase
{
    public function testClassifySeparatesValidationAndEngagementRiddles(): void
    {
        $classifier = new HuntRiddleClassifierFixture();

        $this->assertSame(
            ['validatable' => [10, 11], 'engagement_only' => [12]],
            $classifier->classify([10, '11', 12, 0])
        );
    }
}
