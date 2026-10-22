<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Propose un identifiant WordPress stable, sans reprendre un pseudo déjà pris.
 */
final class SocialAuthUsername
{
    public function propose(string $provider, string $providerUserId, string $displayName, callable $isTaken): string
    {
        $base = $this->slug($displayName);
        if (strlen($base) < 3) {
            $digits = preg_replace('/\D/', '', $providerUserId) ?? '';
            $prefix = in_array($provider, ['google', 'discord', 'email'], true) ? $provider : 'joueur';
            $base = $prefix . ($digits !== '' ? substr($digits, 0, 12) : 'joueur');
        }

        $base = substr($base, 0, 48);
        if (!$isTaken($base)) {
            return $base;
        }

        for ($index = 2; $index <= 40; $index++) {
            $candidate = substr($base, 0, 44) . $index;
            if (!$isTaken($candidate)) {
                return $candidate;
            }
        }

        return substr($base, 0, 40) . substr(hash('sha256', $provider . '|' . $providerUserId), 0, 8);
    }

    private function slug(string $value): string
    {
        $value = trim($value);
        $value = function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
        $value = strtr($value, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ä' => 'a', 'ã' => 'a',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
            'œ' => 'oe', 'æ' => 'ae',
        ]);
        $slug = preg_replace('/[^a-z0-9]/', '', $value);

        return is_string($slug) ? $slug : '';
    }
}
