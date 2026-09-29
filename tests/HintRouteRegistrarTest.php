<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Content {
    function add_rewrite_rule(string $regex, string $query, string $position): void
    {
        global $hintRewriteRule;
        $hintRewriteRule = compact('regex', 'query', 'position');
    }

    function add_rewrite_tag(string $tag, string $regex): void
    {
        global $hintRewriteTag;
        $hintRewriteTag = compact('tag', 'regex');
    }

    function flush_rewrite_rules(): void
    {
        global $hintRewriteFlushes;
        $hintRewriteFlushes++;
    }

    function update_option(string $option, int $value): void
    {
        global $hintRewriteOption;
        $hintRewriteOption = compact('option', 'value');
    }

    function get_option(string $option)
    {
        global $hintRewriteOptionValue;

        return $hintRewriteOptionValue;
    }
}

namespace {
    use ChassesAuTresor\Core\Content\HintRouteRegistrar;
    use PHPUnit\Framework\TestCase;

    require_once __DIR__
        . '/../wp-content/plugins/chassesautresor-core/src/Content/HintRouteRegistrar.php';

    class HintRouteRegistrarTest extends TestCase
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

        public function testRegisterAddsHintCreationRoute(): void
        {
            global $hintRewriteRule, $hintRewriteTag;

            HintRouteRegistrar::register();

            $this->assertSame(
                [
                    'regex' => '^creer-indice/?$',
                    'query' => 'index.php?creer_indice=1',
                    'position' => 'top',
                ],
                $hintRewriteRule
            );
            $this->assertSame(['tag' => '%creer_indice%', 'regex' => '1'], $hintRewriteTag);
        }

        public function testFlushRegistersRouteAndPersistsCompletion(): void
        {
            global $hintRewriteFlushes, $hintRewriteOption;

            HintRouteRegistrar::flush();

            $this->assertSame(1, $hintRewriteFlushes);
            $this->assertSame(
                ['option' => 'creer_indice_rewrite_flushed', 'value' => 1],
                $hintRewriteOption
            );
        }

        public function testMaybeFlushOnlyRunsOnce(): void
        {
            global $hintRewriteFlushes, $hintRewriteOptionValue;

            HintRouteRegistrar::maybeFlush();
            $this->assertSame(1, $hintRewriteFlushes);

            $hintRewriteFlushes = 0;
            $hintRewriteOptionValue = 1;
            HintRouteRegistrar::maybeFlush();
            $this->assertSame(0, $hintRewriteFlushes);
        }
    }
}
