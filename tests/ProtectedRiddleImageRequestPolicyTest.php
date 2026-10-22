<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Media\ProtectedRiddleImageRequestPolicy;
use ChassesAuTresor\Core\Media\ProtectedRiddleImageSignedUrlService;
use PHPUnit\Framework\TestCase;

if (!function_exists('wp_salt')) {
    function wp_salt(string $scheme = 'auth'): string
    {
        unset($scheme);

        return 'test-image-signing-secret';
    }
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedRiddleImageSignedUrlService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Media/ProtectedRiddleImageRequestPolicy.php';

final class ProtectedRiddleImageRequestPolicyTest extends TestCase
{
    public function testASignatureMintedForTheOrganizerStillReachesThePlayer(): void
    {
        $signer = new ProtectedRiddleImageSignedUrlService(3600);
        $now = 1_700_000_100;
        $signed = $signer->sign(15, 'thumbnail', 4, $now);
        $valid = $signer->verify(
            15,
            'thumbnail',
            $signed['uid'],
            $signed['exp'],
            $signed['sig'],
            $now + 5
        );
        $policy = new ProtectedRiddleImageRequestPolicy();

        self::assertTrue($valid);
        self::assertTrue($policy->acceptsSignature($valid, $signed['uid'], 9));
        self::assertFalse($signer->verify(
            15,
            'thumbnail',
            9,
            $signed['exp'],
            $signed['sig'],
            $now + 5
        ));
    }

    public function testABrokenOrAnonymousSignatureIsRefused(): void
    {
        $policy = new ProtectedRiddleImageRequestPolicy();

        self::assertFalse($policy->acceptsSignature(false, 4, 9));
        self::assertFalse($policy->acceptsSignature(true, -1, 9));
        self::assertFalse($policy->acceptsSignature(true, 4, -1));
        self::assertTrue($policy->acceptsSignature(true, 4, 0));
    }
}
