<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddleSolutionAttachmentService;
use PHPUnit\Framework\TestCase;

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Relationships/RelationshipService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleSolutionAttachmentService.php';

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name(string $filename): string {
        return str_replace(' ', '-', strtolower($filename));
    }
}

class RiddleSolutionAttachmentServiceTest extends TestCase {
    public function testAttachmentDataUsesPdfMimeAndSanitizedTitle(): void {
        $service = new RiddleSolutionAttachmentService();

        $this->assertSame(
            [
                'post_mime_type' => 'application/pdf',
                'post_title' => 'ma-solution.pdf',
                'post_content' => '',
                'post_status' => 'inherit',
            ],
            $service->getAttachmentData('Ma Solution.pdf', 'application/pdf')
        );
    }
}
