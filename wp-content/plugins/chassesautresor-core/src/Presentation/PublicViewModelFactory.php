<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Presentation;

/** Prepare the stable, deliberately small contract consumed by public fallback templates. */
final class PublicViewModelFactory {
    /** @return array<string,mixed> */
    public function forPost(int $postId): array {
        $postType = (string) get_post_type($postId);
        $model = [
            'id' => $postId,
            'post_type' => $postType,
            'title' => (string) get_the_title($postId),
            'content' => apply_filters('the_content', (string) get_post_field('post_content', $postId)),
            'image' => (string) get_the_post_thumbnail_url($postId, 'large'),
            'permalink' => (string) get_permalink($postId),
            'can_edit' => function_exists('utilisateur_peut_modifier_post')
                && utilisateur_peut_modifier_post($postId),
        ];

        if ($postType === 'chasse') {
            $model['organizer_id'] = (int) get_organisateur_from_chasse($postId);
            $model['riddles'] = $this->relatedPosts(recuperer_enigmes_pour_chasse($postId));
        } elseif ($postType === 'enigme') {
            $model['hunt_id'] = (int) recuperer_id_chasse_associee($postId);
            $model['visible'] = enigme_est_visible_pour(get_current_user_id(), $postId);
            $model['participation'] = '';
            if ($model['visible'] && is_user_logged_in()) {
                $userId = (int) get_current_user_id();
                $answer = (new PortableRiddleAnswerRenderer())->render($postId, $userId);
                $model['participation'] = (new \ChassesAuTresor\Core\Progress\RiddlePlayerPanelRenderer())
                    ->render($postId, $userId, $answer);
            }
        } elseif ($postType === 'organisateur') {
            $query = get_chasses_de_organisateur($postId);
            $model['hunts'] = $this->relatedPosts(is_object($query) && isset($query->posts) ? $query->posts : $query);
        }

        return $model;
    }

    /** @param mixed $posts @return array<int,array{id:int,title:string,url:string}> */
    private function relatedPosts($posts): array {
        $items = [];
        foreach ((array) $posts as $post) {
            $id = is_object($post) && isset($post->ID) ? (int) $post->ID : (int) $post;
            if ($id <= 0) {
                continue;
            }
            $items[] = ['id' => $id, 'title' => (string) get_the_title($id), 'url' => (string) get_permalink($id)];
        }

        return $items;
    }
}
