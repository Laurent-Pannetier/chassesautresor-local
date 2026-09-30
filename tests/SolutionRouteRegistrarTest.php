<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content {
    if (!function_exists(__NAMESPACE__ . '\\add_rewrite_rule')) {
        function add_rewrite_rule(string $regex, string $query, string $position): void
        {
            global $hintRewriteRule;
            $hintRewriteRule = compact('regex', 'query', 'position');
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\add_rewrite_tag')) {
        function add_rewrite_tag(string $tag, string $regex): void
        {
            global $hintRewriteTag;
            $hintRewriteTag = compact('tag', 'regex');
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\flush_rewrite_rules')) {
        function flush_rewrite_rules(): void
        {
            global $hintRewriteFlushes;
            ++$hintRewriteFlushes;
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\update_option')) {
        function update_option(string $option, int $value): void
        {
            global $hintRewriteOption;
            $hintRewriteOption = compact('option', 'value');
        }
    }
    if (!function_exists(__NAMESPACE__ . '\\get_option')) {
        function get_option(string $option)
        {
            global $hintRewriteOptionValue;
            return $hintRewriteOptionValue;
        }
    }
}

namespace {
    use ChassesAuTresor\Core\Content\SolutionRouteRegistrar;
    use PHPUnit\Framework\TestCase;

    require_once __DIR__
        . '/../wp-content/plugins/chassesautresor-core/src/Content/SolutionRouteRegistrar.php';

    class SolutionRouteRegistrarTest extends TestCase
    {
        protected function setUp(): void
        {
            global $hintRewriteRule, $hintRewriteTag, $hintRewriteFlushes;
            global $hintRewriteOption, $hintRewriteOptionValue;
            $hintRewriteRule = null;
            $hintRewriteTag = null;
            $hintRewriteFlushes = 0;
            $hintRewriteOption = null;
            $hintRewriteOptionValue = false;
        }

        public function testRegisterAddsSolutionCreationRoute(): void
        {
            global $hintRewriteRule, $hintRewriteTag;
            SolutionRouteRegistrar::register();

            $this->assertSame(
                [
                    'regex' => '^creer-solution/?$',
                    'query' => 'index.php?creer_solution=1',
                    'position' => 'top',
                ],
                $hintRewriteRule
            );
            $this->assertSame(['tag' => '%creer_solution%', 'regex' => '1'], $hintRewriteTag);
        }

        public function testFlushRegistersRouteAndPersistsCompletion(): void
        {
            global $hintRewriteFlushes, $hintRewriteOption;
            SolutionRouteRegistrar::flush();

            $this->assertSame(1, $hintRewriteFlushes);
            $this->assertSame(
                ['option' => 'creer_solution_rewrite_flushed', 'value' => 1],
                $hintRewriteOption
            );
        }

        public function testMaybeFlushOnlyRunsWhenNeeded(): void
        {
            global $hintRewriteFlushes, $hintRewriteOptionValue;
            SolutionRouteRegistrar::maybeFlush();
            $this->assertSame(1, $hintRewriteFlushes);

            $hintRewriteFlushes = 0;
            $hintRewriteOptionValue = 1;
            SolutionRouteRegistrar::maybeFlush();
            $this->assertSame(0, $hintRewriteFlushes);
        }
    }
}
