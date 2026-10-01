<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Content\RiddlePrerequisiteAjaxHandler;
use ChassesAuTresor\Core\Media\UserAvatarUploadAjaxHandler;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddlePrerequisiteAjaxHandler.php';
require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Media/UserAvatarUploadAjaxHandler.php';

final class RemainingPriorityAjaxHandlerRegistrationTest extends TestCase {
    public function testRegistersAuthenticatedEndpoints(): void {
        $hooks = [];
        $register = static function ($hook, $callback) use (&$hooks): void {
            $hooks[$hook] = $callback;
        };
        RiddlePrerequisiteAjaxHandler::register($register);
        UserAvatarUploadAjaxHandler::register($register);

        $this->assertSame([
            'wp_ajax_verifier_et_enregistrer_condition_pre_requis' => [
                RiddlePrerequisiteAjaxHandler::class,
                'handle',
            ],
            'wp_ajax_upload_user_avatar' => [UserAvatarUploadAjaxHandler::class, 'handle'],
        ], $hooks);
    }
}
