<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Security/site-password.php';

class SitePasswordOAuthBypassTest extends TestCase
{
    private ?string $previousRequestUri = null;

    protected function setUp(): void
    {
        $this->previousRequestUri = isset($_SERVER['REQUEST_URI'])
            ? (string) $_SERVER['REQUEST_URI']
            : null;
    }

    protected function tearDown(): void
    {
        if ($this->previousRequestUri === null) {
            unset($_SERVER['REQUEST_URI']);
        } else {
            $_SERVER['REQUEST_URI'] = $this->previousRequestUri;
        }
    }

    public function testDiscoveryAndOauthRoutesBypassTheSitePassword(): void
    {
        foreach (ca_site_password_public_oauth_paths() as $path) {
            $_SERVER['REQUEST_URI'] = $path . '?unused=1';
            self::assertTrue(
                ca_site_password_is_public_oauth_request('/'),
                $path
            );

            $_SERVER['REQUEST_URI'] = $path . '/';
            self::assertTrue(
                ca_site_password_is_public_oauth_request('/'),
                $path . '/'
            );
        }
    }

    public function testOrdinaryAndAcmePathsStayBehindTheSitePassword(): void
    {
        foreach ([
            '/',
            '/chasse/demo',
            '/.well-known/acme-challenge/token',
            '/.well-known/oauth-protected-resource/extra',
            '/oauth/tokenize',
            '/oauth/authorize-extra',
            '/wp-login.php',
        ] as $path) {
            $_SERVER['REQUEST_URI'] = $path;
            self::assertFalse(
                ca_site_password_is_public_oauth_request('/'),
                $path
            );
        }
    }

    public function testSubdirectoryInstallMatchesOnlyThatPrefix(): void
    {
        $_SERVER['REQUEST_URI'] = '/blog/.well-known/oauth-authorization-server';
        self::assertTrue(ca_site_password_is_public_oauth_request('/blog'));

        $_SERVER['REQUEST_URI'] = '/blog/oauth/token';
        self::assertTrue(ca_site_password_is_public_oauth_request('/blog/'));

        $_SERVER['REQUEST_URI'] = '/blog2/.well-known/oauth-authorization-server';
        self::assertFalse(ca_site_password_is_public_oauth_request('/blog'));

        $_SERVER['REQUEST_URI'] = '/blog/.well-known/acme-challenge/token';
        self::assertFalse(ca_site_password_is_public_oauth_request('/blog'));
    }

    public function testProtectionReturnsBeforeThePasswordWall(): void
    {
        $source = (string) file_get_contents(
            __DIR__ . '/../wp-content/plugins/chassesautresor-core/src/Security/site-password.php'
        );
        $bypass = strpos($source, '|| ca_site_password_is_public_oauth_request()');
        $wall = strpos($source, 'status_header(401)');

        self::assertNotFalse($bypass);
        self::assertNotFalse($wall);
        self::assertLessThan($wall, $bypass);
    }
}
