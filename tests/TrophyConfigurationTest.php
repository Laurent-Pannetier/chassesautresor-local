<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Trophies\CodeEventTrophyTrigger;
use ChassesAuTresor\Core\Trophies\TrophyEvent;
use ChassesAuTresor\Core\Trophies\TrophyFieldGroupRegistrar;
use ChassesAuTresor\Core\Trophies\TrophyPostTypeRegistrar;
use ChassesAuTresor\Core\Trophies\TrophyTriggerRegistry;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}

final class TrophyConfigurationTest extends TestCase
{
    public function testPostTypeIsPrivateAndAdministratorManaged(): void
    {
        $args = TrophyPostTypeRegistrar::postTypeArgs();

        self::assertFalse($args['public']);
        self::assertFalse($args['publicly_queryable']);
        self::assertTrue($args['show_ui']);
        self::assertSame('manage_options', $args['capabilities']['edit_posts']);
        self::assertContains('thumbnail', $args['supports']);
        self::assertContains('excerpt', $args['supports']);
    }

    public function testFieldGroupOffersOneTriggerAndRecipientScope(): void
    {
        $group = TrophyFieldGroupRegistrar::fieldGroup();
        $fields = [];
        foreach ($group['fields'] as $field) {
            $fields[$field['name']] = $field;
        }

        self::assertArrayHasKey('trophee_trigger_type', $fields);
        self::assertArrayHasKey('trophee_scope', $fields);
        self::assertArrayHasKey('trophee_notification_channel', $fields);
        self::assertSame(['user', 'team', 'both'], array_keys($fields['trophee_scope']['choices']));
    }

    public function testCodeEventProviderOnlyMatchesItsConfiguredKey(): void
    {
        $provider = (new TrophyTriggerRegistry())->get(CodeEventTrophyTrigger::TYPE);
        self::assertNotNull($provider);
        $configuration = [CodeEventTrophyTrigger::FIELD => 'account.created'];

        self::assertTrue($provider->matches(
            $configuration,
            new TrophyEvent('code_event', 4, 'account.created')
        ));
        self::assertFalse($provider->matches(
            $configuration,
            new TrophyEvent('code_event', 4, 'other.event')
        ));
    }
}
