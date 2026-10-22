<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * N'accepte comme retour qu'une adresse du même site, hors écran de connexion.
 */
final class SocialAuthRedirect
{
    public function accept(string $candidate, string $home): string
    {
        $home = $this->absoluteHome($home);
        $candidate = trim($candidate);
        if ($candidate === '' || $this->isUnsafeScheme($candidate)) {
            return $home;
        }
        if (str_starts_with($candidate, '//') || str_starts_with($candidate, '/\\')) {
            return $home;
        }
        if (str_starts_with($candidate, '/')) {
            $local = $this->origin($home) . $candidate;

            return $this->isAuthScreen($local) || $this->isProfileLanding($local) ? $home : $local;
        }

        $parts = parse_url($candidate);
        $homeParts = parse_url($home);
        if (!is_array($parts) || !is_array($homeParts)) {
            return $home;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $homeHost = strtolower((string) ($homeParts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || $host !== $homeHost) {
            return $home;
        }
        if ($this->isAuthScreen($candidate)) {
            return $home;
        }
        if ($this->isProfileLanding($candidate)) {
            return $home;
        }

        return $candidate;
    }

    private function absoluteHome(string $home): string
    {
        $home = trim($home);
        if ($home === '') {
            return '/';
        }

        return $home;
    }

    private function origin(string $home): string
    {
        $parts = parse_url($home);
        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $scheme = (string) ($parts['scheme'] ?? 'https');
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return $scheme . '://' . $parts['host'] . $port;
    }

    private function isUnsafeScheme(string $candidate): bool
    {
        return (bool) preg_match('#^(javascript|data|vbscript):#i', $candidate);
    }

    private function isAuthScreen(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $path = (string) ($parts['path'] ?? '');
        $bare = rtrim($path, '/');
        if ($bare === '/connexion' || str_ends_with($bare, '/connexion')) {
            return true;
        }
        if (str_ends_with($path, '/wp-login.php') || $path === 'wp-login.php') {
            return true;
        }

        $query = (string) ($parts['query'] ?? '');

        return str_contains($query, 'cat_connexion=') || str_contains($query, 'cat_oauth=');
    }

    private function isProfileLanding(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }

        $path = strtolower(rtrim((string) ($parts['path'] ?? ''), '/'));
        if ($path === '' || $path === '/') {
            return false;
        }
        if ($path === '/wp-admin/profile.php') {
            return true;
        }

        $roots = ['/mon-compte', '/my-account'];
        foreach ($roots as $root) {
            if ($path === $root || $path === $root . '/edit-account' || $path === $root . '/modifier-compte') {
                return true;
            }
        }

        return false;
    }
}
