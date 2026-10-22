<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Auth;

/**
 * Appels HTTPS vers Discord et Google. Le jeton n'est pas journalisé.
 */
final class SocialAuthHttpClient
{
    /**
     * @param array<string, string> $fields
     * @return array{ok:bool,body:array<string,mixed>}
     */
    public function postForm(string $url, array $fields): array
    {
        $response = wp_remote_post($url, [
            'timeout' => 15,
            'redirection' => 0,
            'headers' => ['Accept' => 'application/json'],
            'body' => $fields,
        ]);

        return $this->decode($response);
    }

    /**
     * @return array{ok:bool,body:array<string,mixed>}
     */
    public function getJson(string $url, string $bearer): array
    {
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'redirection' => 0,
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $bearer,
            ],
        ]);

        return $this->decode($response);
    }

    /**
     * @param mixed $response
     * @return array{ok:bool,body:array<string,mixed>}
     */
    private function decode($response): array
    {
        if (is_wp_error($response)) {
            return ['ok' => false, 'body' => []];
        }

        $status = (int) wp_remote_retrieve_response_code($response);
        $raw = (string) wp_remote_retrieve_body($response);
        $decoded = json_decode($raw, true);
        if ($status !== 200 || !is_array($decoded)) {
            return ['ok' => false, 'body' => []];
        }

        return ['ok' => true, 'body' => $decoded];
    }
}
