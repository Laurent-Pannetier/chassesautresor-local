<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class RiddleMutationServiceTest extends TestCase {
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testPrerequisiteMutationPersistsIdsAndDerivedCondition(): void {
        eval(<<<'PHP'
namespace ChassesAuTresor\Core\Content;
function update_field($field, $value, $postId) {
    $GLOBALS['riddleMutationFields'][$field] = $value;
    return true;
}
function sanitize_text_field($value) { return trim((string) $value); }
function get_post_meta($postId, $field, $single) { return null; }
PHP);

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleFieldPolicyService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleMutationService.php';

        $GLOBALS['riddleMutationFields'] = [];
        $result = (new ChassesAuTresor\Core\Content\RiddleMutationService())->apply(
            42,
            'enigme_acces_pre_requis',
            '10,20',
            static fn () => null,
            1_800_000_000
        );

        $this->assertSame(
            [
                'enigme_acces_pre_requis' => [10, 20],
                'enigme_acces_condition' => 'pre_requis',
            ],
            $GLOBALS['riddleMutationFields']
        );
        $this->assertSame(
            ['error' => null, 'terminal' => false, 'refresh_state' => true],
            $result
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testVisualTextKeepsAllowedHtml(): void {
        eval(<<<'PHP'
namespace ChassesAuTresor\Core\Content;
function update_field($field, $value, $postId) {
    $GLOBALS['riddleMutationFields'][$field] = $value;
    return true;
}
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
function wp_kses_post($value) { return (string) $value; }
function get_post_meta($postId, $field, $single) { return null; }
PHP);

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleFieldPolicyService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleMutationService.php';

        $html = '<p>Bonjour <strong>monde</strong></p>';
        $GLOBALS['riddleMutationFields'] = [];
        $result = (new ChassesAuTresor\Core\Content\RiddleMutationService())->apply(
            7,
            'enigme_visuel_texte',
            $html,
            static fn () => null,
            1_800_000_000
        );

        $this->assertSame($html, $GLOBALS['riddleMutationFields']['enigme_visuel_texte']);
        $this->assertSame(
            ['error' => null, 'terminal' => false, 'refresh_state' => false],
            $result
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testVisualImagesStoreUniqueIds(): void {
        eval(<<<'PHP'
namespace ChassesAuTresor\Core\Content;
function update_field($field, $value, $postId) {
    $GLOBALS['riddleMutationFields'][$field] = $value;
    return true;
}
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
function get_field($field, $postId, $format = true) {
    return $GLOBALS['riddleMutationFields'][$field] ?? null;
}
PHP);

        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleFieldPolicyService.php';
        require_once __DIR__
            . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleMutationService.php';

        $GLOBALS['riddleMutationFields'] = [];
        $service = new ChassesAuTresor\Core\Content\RiddleMutationService();
        $result = $service->apply(7, 'enigme_visuel_image', ['4', '0', '4', '9'], static fn () => null, 1);

        $this->assertSame([4, 9], $GLOBALS['riddleMutationFields']['enigme_visuel_image']);
        $this->assertSame(['error' => null, 'terminal' => false, 'refresh_state' => false], $result);

        $cleared = $service->apply(7, 'enigme_visuel_image', '', static fn () => null, 1);
        $this->assertSame([], $GLOBALS['riddleMutationFields']['enigme_visuel_image']);
        $this->assertSame(['error' => null, 'terminal' => false, 'refresh_state' => false], $cleared);
    }
}
