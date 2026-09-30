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
}
