<?php

declare(strict_types=1);

/**
 * Transitional compatibility loader for the core WP-CLI command.
 */

if (!class_exists('Cat_CLI_Command', false)) {
    class_alias(ChassesAuTresor\Core\Cli\CatCliCommand::class, 'Cat_CLI_Command');
}
