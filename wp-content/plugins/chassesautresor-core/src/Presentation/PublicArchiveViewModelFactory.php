<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

/** Normalize the current WordPress archive query for portable listing templates. */
final class PublicArchiveViewModelFactory {
    /** @return array{title:string,items:array<int,array<string,mixed>>,pagination:string} */
    public function current(): array {
        global $wp_query;

        $items = [];
        foreach ((array) ($wp_query->posts ?? []) as $post) {
            $postId = is_object($post) && isset($post->ID) ? (int) $post->ID : (int) $post;
            if ($postId <= 0) {
                continue;
            }
            $items[] = [
                'id' => $postId,
                'title' => (string) get_the_title($postId),
                'url' => (string) get_permalink($postId),
                'image' => (string) get_the_post_thumbnail_url($postId, 'medium_large'),
                'excerpt' => (string) get_the_excerpt($postId),
            ];
        }

        return [
            'title' => (string) post_type_archive_title('', false),
            'items' => $items,
            'pagination' => (string) get_the_posts_pagination([
                'mid_size' => 1,
                'prev_text' => __('Précédent', 'chassesautresor-com'),
                'next_text' => __('Suivant', 'chassesautresor-com'),
            ]),
        ];
    }
}
