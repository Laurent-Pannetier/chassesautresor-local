<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content {
    if (!function_exists(__NAMESPACE__ . '\\add_rewrite_rule')) {
        function add_rewrite_rule(string $regex, string $query, string $position): void {
            global $hintRewriteRule;
            $hintRewriteRule = compact('regex', 'query', 'position');
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\add_rewrite_tag')) {
        function add_rewrite_tag(string $tag, string $regex): void {
            global $hintRewriteTag;
            $hintRewriteTag = compact('tag', 'regex');
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\flush_rewrite_rules')) {
        function flush_rewrite_rules(): void {
            global $hintRewriteFlushes;
            ++$hintRewriteFlushes;
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\update_option')) {
        function update_option(string $option, int $value): void {
            global $hintRewriteOption;
            $hintRewriteOption = compact('option', 'value');
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\get_option')) {
        function get_option(string $option) {
            global $hintRewriteOptionValue;
            return $hintRewriteOptionValue;
        }
    }
}

namespace {
    use ChassesAuTresor\Core\Content\RiddleRouteRegistrar;
    use PHPUnit\Framework\TestCase;

    require_once __DIR__
        . '/../wp-content/plugins/chassesautresor-core/src/Content/RiddleRouteRegistrar.php';

    class RiddleRouteRegistrarTest extends TestCase {
        protected function setUp(): void {
            global $hintRewriteRule, $hintRewriteTag, $hintRewriteFlushes;
            global $hintRewriteOption, $hintRewriteOptionValue;
            $hintRewriteRule = null;
            $hintRewriteTag = null;
            $hintRewriteFlushes = 0;
            $hintRewriteOption = null;
            $hintRewriteOptionValue = false;
        }

        public function testRegisterAddsRiddleCreationRoute(): void {
            global $hintRewriteRule, $hintRewriteTag;
            RiddleRouteRegistrar::register();

            $this->assertSame(
                [
                    'regex' => '^creer-enigme/?$',
                    'query' => 'index.php?creer_enigme=1',
                    'position' => 'top',
                ],
                $hintRewriteRule
            );
            $this->assertSame(['tag' => '%creer_enigme%', 'regex' => '1'], $hintRewriteTag);
        }

        public function testFlushAndMaybeFlushPersistCompletion(): void {
            global $hintRewriteFlushes, $hintRewriteOption, $hintRewriteOptionValue;
            RiddleRouteRegistrar::flush();
            $this->assertSame(1, $hintRewriteFlushes);
            $this->assertSame(['option' => 'creer_enigme_rewrite_flushed', 'value' => 1], $hintRewriteOption);

            $hintRewriteFlushes = 0;
            $hintRewriteOptionValue = 1;
            RiddleRouteRegistrar::maybeFlush();
            $this->assertSame(0, $hintRewriteFlushes);
        }
    }
}
