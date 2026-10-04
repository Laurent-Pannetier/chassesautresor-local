<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Ensures previously unprotected AJAX endpoints now require nonces end-to-end.
 */
final class AjaxCsrfSecurityTest extends TestCase
{
    public function testAccountMessageDismissalRequiresNonce(): void
    {
        $handler = $this->core('Messages/AccountMessageDismissalAjaxHandler.php');
        $theme = $this->theme('functions.php');
        $script = $this->theme('assets/js/validation-chasse.js');

        $this->assertStringContainsString("check_ajax_referer('cta_dismiss_message', 'nonce');", $handler);
        $this->assertStringContainsString("wp_create_nonce('cta_dismiss_message')", $theme);
        $this->assertStringContainsString("params.set('nonce', ctaMyAccount.dismissNonce);", $script);
    }

    public function testAccountSectionRequiresNonce(): void
    {
        $handler = $this->core('Messages/AccountSectionAjaxHandler.php');
        $theme = $this->theme('functions.php');
        $script = $this->theme('assets/js/myaccount.js');

        $this->assertStringContainsString("check_ajax_referer('cta_load_admin_section', 'nonce');", $handler);
        $this->assertStringContainsString("wp_create_nonce('cta_load_admin_section')", $theme);
        $this->assertStringContainsString("searchParams.set('nonce', ctaMyAccount.sectionNonce);", $script);
    }

    public function testAvatarUploadRequiresNonce(): void
    {
        $handler = $this->core('Media/UserAvatarUploadAjaxHandler.php');
        $theme = $this->theme('inc/user-functions.php');
        $script = $this->theme('assets/js/avatar-upload.js');

        $this->assertStringContainsString("check_ajax_referer('upload_user_avatar', 'nonce');", $handler);
        $this->assertStringContainsString("wp_create_nonce('upload_user_avatar')", $theme);
        $this->assertStringContainsString('formData.append("nonce", (window.avatarUpload && avatarUpload.nonce) || "");', $script);
    }

    public function testUserAttemptsRequiresNonce(): void
    {
        $handler = $this->core('Progress/UserAttemptsAjaxHandler.php');
        $theme = $this->theme('inc/user-functions.php');
        $script = $this->theme('assets/js/tentatives-pager.js');

        $this->assertStringContainsString("check_ajax_referer('ca_fetch_tentatives', 'nonce');", $handler);
        $this->assertStringContainsString("wp_create_nonce('ca_fetch_tentatives')", $theme);
        $this->assertStringContainsString("data.set('nonce', config.nonce);", $script);
    }

    public function testRiddleAttemptListAndPrerequisiteReuseEnigmeNonce(): void
    {
        $list = $this->core('Progress/RiddleAttemptListAjaxHandler.php');
        $prereq = $this->core('Content/RiddlePrerequisiteAjaxHandler.php');
        $script = $this->theme('assets/js/enigme-edit.js');

        $this->assertStringContainsString("check_ajax_referer('modifier_champ_enigme', 'nonce');", $list);
        $this->assertStringContainsString("check_ajax_referer('modifier_champ_enigme', 'nonce');", $prereq);
        $this->assertStringContainsString(
            "nonce: (window.CHP_ENIGME_DEFAUT && CHP_ENIGME_DEFAUT.nonce) || ''",
            $script
        );
    }

    public function testConversionModalRequiresNonce(): void
    {
        $handler = $this->core('Points/ConversionModalAjaxHandler.php');
        $theme = $this->theme('inc/organisateur-functions.php');
        $script = $this->theme('assets/js/conversion.js');

        $this->assertStringContainsString("check_ajax_referer('conversion-history-nonce', 'nonce');", $handler);
        $this->assertStringContainsString("wp_create_nonce('conversion-history-nonce')", $theme);
        $this->assertStringContainsString('action: "conversion_modal_content"', $script);
        $this->assertStringContainsString('ConversionModalAjax.nonce', $script);
    }

    public function testAdminToolsRequireSharedNonce(): void
    {
        $handler = $this->core('Admin/AdminAjaxHandler.php');
        $theme = $this->theme('inc/admin-functions.php');
        $payments = $this->theme('assets/js/paiements-admin.js');
        $history = $this->theme('assets/js/paiements-historique.js');
        $search = $this->theme('assets/js/autocomplete-utilisateurs.js');
        $acf = $this->theme('assets/js/developpement-card.js');

        $this->assertSame(
            4,
            substr_count($handler, "check_ajax_referer('cta_admin_tools', 'nonce');")
        );
        $this->assertStringContainsString("wp_create_nonce('cta_admin_tools')", $theme);
        $this->assertStringContainsString('ctaAdminTools.nonce', $payments);
        $this->assertStringContainsString('ctaAdminTools.nonce', $history);
        $this->assertStringContainsString('ajax_object.nonce', $search);
        $this->assertStringContainsString('ajax_object.nonce', $acf);
    }

    private function core(string $relative): string
    {
        return (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/' . $relative
        );
    }

    private function theme(string $relative): string
    {
        return (string) file_get_contents(
            __DIR__ . '/../wp-content/themes/chassesautresor/' . $relative
        );
    }
}
