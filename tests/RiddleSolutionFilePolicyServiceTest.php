<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleSolutionFilePolicyService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionFilePolicyService.php';

class RiddleSolutionFilePolicyServiceTest extends TestCase {
    private RiddleSolutionFilePolicyService $service;

    protected function setUp(): void {
        $this->service = new RiddleSolutionFilePolicyService();
    }

    public function testPdfUploadMustRespectSizeAndMimeType(): void {
        $this->assertNull($this->service->getUploadError(1024, 'pdf', 'application/pdf'));
        $this->assertSame(
            'file_too_large',
            $this->service->getUploadError(5 * 1024 * 1024 + 1, 'pdf', 'application/pdf')
        );
        $this->assertSame(
            'invalid_file_type',
            $this->service->getUploadError(1024, 'jpg', 'image/jpeg')
        );
    }

    public function testPublicationTimestampRequiresSupportedCompleteSchedule(): void {
        $this->assertNull($this->service->getPublicationTimestamp('immediate', 1, '12:00', 100));
        $this->assertNull($this->service->getPublicationTimestamp('fin_de_chasse', null, '12:00', 100));
        $this->assertNull($this->service->getPublicationTimestamp('fin_de_chasse', 1, null, 100));
    }

    public function testPastPublicationIsDeferredByAtLeastFiveSeconds(): void {
        $this->assertSame(
            1_704_110_405,
            $this->service->getPublicationTimestamp(
                'fin_de_chasse',
                -1,
                '00:00',
                1_704_110_400
            )
        );
    }
}
