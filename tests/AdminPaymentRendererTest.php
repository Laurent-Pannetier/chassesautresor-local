<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Admin\AdminPaymentRenderer;
use ChassesAuTresor\Core\Relationships\OrganizerRepository;
use PHPUnit\Framework\TestCase;

final class AdminPaymentRendererTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRendersLocalizedTableHeadersWithoutThemeCallback(): void
    {
        function esc_html__(string $message, string $domain): string
        {
            return $message;
        }

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Relationships/OrganizerRepository.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Admin/AdminPaymentRenderer.php';

        $html = (new AdminPaymentRenderer(new OrganizerRepository(new stdClass())))->table([]);

        self::assertStringContainsString('Organisateur', $html);
        self::assertStringContainsString('Montant / Points', $html);
        self::assertStringContainsString('IBAN / BIC', $html);
    }
}
