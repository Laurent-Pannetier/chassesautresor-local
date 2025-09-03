<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DevenirOrganisateurTemplateTest extends TestCase
{
    public function test_template_contains_message_output(): void
    {
        $template = file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/templates/page-devenir-organisateur.php'
        );
        $this->assertStringContainsString('myaccount_get_important_messages', $template);
    }
}
