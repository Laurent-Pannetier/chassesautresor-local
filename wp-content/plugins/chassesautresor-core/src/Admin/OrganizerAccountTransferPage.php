<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Admin;

/**
 * One-screen admin tool to attach an existing organizer profile to another account.
 */
final class OrganizerAccountTransferPage
{
    private const PAGE = 'chassesautresor-transfer-organizer';
    private const NONCE = 'cat_transfer_organizer';

    private const LINKS = [
        ['type' => 'chasse', 'key' => 'chasse_cache_organisateur', 'from' => 'organizer'],
        ['type' => 'enigme', 'key' => 'enigme_chasse_associee', 'from' => 'chasse'],
        ['type' => 'indice', 'key' => 'indice_chasse_linked', 'from' => 'chasse'],
        ['type' => 'indice', 'key' => 'indice_enigme_linked', 'from' => 'enigme'],
        ['type' => 'solution', 'key' => 'solution_chasse_linked', 'from' => 'chasse'],
        ['type' => 'solution', 'key' => 'solution_enigme_linked', 'from' => 'enigme'],
        ['type' => 'enigme_etape', 'key' => 'etape_enigme_associee', 'from' => 'enigme'],
        ['type' => 'trophee', 'key' => 'trophee_chasse', 'from' => 'chasse'],
    ];

    public static function register(callable $addAction): void
    {
        $addAction('admin_menu', [self::class, 'addPage']);
    }

    public static function addPage(): void
    {
        add_management_page(
            __('Transférer un organisateur', 'chassesautresor-com'),
            __('Transfert organisateur', 'chassesautresor-com'),
            'manage_options',
            self::PAGE,
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $state = self::stateFromRequest();
        $input = $state['input'];
        $plan = $state['plan'];
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Transférer un organisateur', 'chassesautresor-com'); ?></h1>
            <p>
                <?php esc_html_e(
                    'La chasse, les énigmes et les indices restent sur la fiche organisateur. '
                    . 'Cet écran change le compte qui la possède.',
                    'chassesautresor-com'
                ); ?>
            </p>
            <?php self::renderFeedback($state); ?>
            <form method="post" action="<?php echo esc_url(admin_url('tools.php?page=' . self::PAGE)); ?>">
                <?php wp_nonce_field(self::NONCE); ?>
                <input type="hidden" name="cat_transfer_present" value="1">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="cat-transfer-organizer">
                                <?php esc_html_e('ID de la fiche organisateur', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="number"
                                min="1"
                                required
                                class="small-text"
                                id="cat-transfer-organizer"
                                name="organizer_id"
                                value="<?php echo esc_attr(
                                    $input['organizer_id'] > 0 ? (string) $input['organizer_id'] : ''
                                ); ?>"
                            >
                            <p class="description">
                                <?php esc_html_e(
                                    'Le nombre dans l’adresse de modification de la fiche, par exemple post=123.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cat-transfer-source">
                                <?php esc_html_e('Compte actuel', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                required
                                class="regular-text"
                                id="cat-transfer-source"
                                name="source_user"
                                value="<?php echo esc_attr($input['source_user']); ?>"
                            >
                            <p class="description">
                                <?php esc_html_e(
                                    'ID, identifiant ou e-mail. Exemple : organisateur1.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                            <?php self::renderUserLine($state['source_label']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="cat-transfer-target">
                                <?php esc_html_e('Nouveau compte', 'chassesautresor-com'); ?>
                            </label>
                        </th>
                        <td>
                            <input
                                type="text"
                                required
                                class="regular-text"
                                id="cat-transfer-target"
                                name="target_user"
                                value="<?php echo esc_attr($input['target_user']); ?>"
                            >
                            <p class="description">
                                <?php esc_html_e(
                                    'ID, identifiant ou e-mail du compte déjà inscrit.',
                                    'chassesautresor-com'
                                ); ?>
                            </p>
                            <?php self::renderUserLine($state['target_label']); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Médias isolés', 'chassesautresor-com'); ?></th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    name="include_orphans"
                                    value="1"
                                    <?php checked($input['include_orphans']); ?>
                                >
                                <?php esc_html_e(
                                    'Inclure les fichiers envoyés par l’ancien compte '
                                    . 'et pas encore rangés dans un contenu.',
                                    'chassesautresor-com'
                                ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(
                    __('Prévisualiser', 'chassesautresor-com'),
                    'secondary',
                    'cat_preview',
                    false
                ); ?>
                <?php if (is_array($plan) && (empty($plan['blocked']) || !empty($state['confirm_error']))) : ?>
                    <?php self::renderPlan($plan); ?>
                    <?php if (empty($state['applied'])) : ?>
                        <p>
                            <label>
                                <input
                                    type="checkbox"
                                    name="confirm"
                                    value="1"
                                    <?php checked(!empty($input['confirm'])); ?>
                                >
                                <?php esc_html_e(
                                    'Je confirme le transfert de cette fiche vers le nouveau compte.',
                                    'chassesautresor-com'
                                ); ?>
                            </label>
                        </p>
                        <?php submit_button(
                            __('Transférer', 'chassesautresor-com'),
                            'primary',
                            'cat_apply',
                            false
                        ); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * @return array<string, mixed>
     */
    private static function stateFromRequest(): array
    {
        $input = self::inputFromRequest();
        $state = [
            'input' => $input,
            'plan' => null,
            'applied' => false,
            'source_label' => '',
            'target_label' => '',
            'confirm_error' => false,
        ];
        if (!self::isSubmission()) {
            return $state;
        }

        check_admin_referer(self::NONCE);
        $source = self::findUser($input['source_user']);
        $target = self::findUser($input['target_user']);
        $state['source_label'] = self::userLabel($source);
        $state['target_label'] = self::userLabel($target);
        $service = new OrganizerAccountTransferService();
        $plan = $service->plan(self::snapshot(
            $input['organizer_id'],
            $source,
            $target,
            $input['include_orphans'],
            $service
        ));
        if ((string) $input['action'] === 'apply' && empty($plan['blocked']) && empty($input['confirm'])) {
            $plan['blocked'] = true;
            $plan['errors'][] = 'confirm_required';
            $state['confirm_error'] = true;
        }
        if ((string) $input['action'] === 'apply' && empty($plan['blocked'])) {
            self::apply($plan);
            $state['applied'] = true;
        }
        $state['plan'] = $plan;

        return $state;
    }

    /**
     * @return array{
     *     organizer_id:int,
     *     source_user:string,
     *     target_user:string,
     *     include_orphans:bool,
     *     confirm:bool,
     *     action:string
     * }
     */
    private static function inputFromRequest(): array
    {
        $posted = self::isSubmission();

        return [
            'organizer_id' => $posted ? absint(wp_unslash($_POST['organizer_id'] ?? 0)) : 0,
            'source_user' => $posted ? sanitize_text_field(wp_unslash($_POST['source_user'] ?? '')) : '',
            'target_user' => $posted ? sanitize_text_field(wp_unslash($_POST['target_user'] ?? '')) : '',
            'include_orphans' => !$posted || !empty($_POST['include_orphans']),
            'confirm' => $posted && !empty($_POST['confirm']),
            'action' => $posted && isset($_POST['cat_apply']) ? 'apply' : 'preview',
        ];
    }

    private static function isSubmission(): bool
    {
        return isset($_SERVER['REQUEST_METHOD'])
            && strtoupper((string) $_SERVER['REQUEST_METHOD']) === 'POST'
            && isset($_POST['cat_transfer_present']);
    }

    /**
     * @param \WP_User|null $source
     * @param \WP_User|null $target
     * @return array<string, mixed>
     */
    private static function snapshot(
        int $organizerId,
        $source,
        $target,
        bool $includeOrphans,
        OrganizerAccountTransferService $service
    ): array {
        $post = $organizerId > 0 ? get_post($organizerId) : null;
        $posts = $post instanceof \WP_Post ? self::linkedPosts($organizerId) : [];
        $contentIds = array_merge([$organizerId], array_map(static fn (array $item): int => (int) $item['id'], $posts));
        $meta = $post instanceof \WP_Post ? self::metaRows($contentIds) : [];
        $referenced = $service->candidateAttachmentIds($meta);
        $sourceId = $source instanceof \WP_User ? (int) $source->ID : 0;
        $targetId = $target instanceof \WP_User ? (int) $target->ID : 0;
        $sourceOrganizers = $sourceId > 0 ? self::organizerIdsForUser($sourceId, $service) : [];
        $targetOrganizers = $targetId > 0 ? self::organizerIdsForUser($targetId, $service) : [];

        return [
            'organizer_id' => $organizerId,
            'organizer_type' => $post instanceof \WP_Post ? (string) $post->post_type : '',
            'organizer_status' => $post instanceof \WP_Post ? (string) $post->post_status : '',
            'organizer_author' => $post instanceof \WP_Post ? (int) $post->post_author : 0,
            'organizer_title' => $post instanceof \WP_Post ? (string) $post->post_title : '',
            'associated_user_ids' => $post instanceof \WP_Post
                ? self::storedIds(get_post_meta($organizerId, 'utilisateurs_associes', true))
                : [],
            'contact_email' => $post instanceof \WP_Post
                ? (string) get_post_meta($organizerId, 'profil_public_email_contact', true)
                : '',
            'source_user_id' => $sourceId,
            'source_exists' => $source instanceof \WP_User,
            'source_roles' => $source instanceof \WP_User ? array_values((array) $source->roles) : [],
            'source_other_organizer_ids' => array_values(array_diff($sourceOrganizers, [$organizerId])),
            'target_user_id' => $targetId,
            'target_exists' => $target instanceof \WP_User,
            'target_email' => $target instanceof \WP_User ? (string) $target->user_email : '',
            'target_roles' => $target instanceof \WP_User ? array_values((array) $target->roles) : [],
            'target_organizer_ids' => $targetOrganizers,
            'include_orphan_attachments' => $includeOrphans,
            'posts' => $posts,
            'attachments' => self::attachments($sourceId, $contentIds, $referenced),
        ];
    }

    /**
     * @param mixed $value
     * @return mixed[]
     */
    private static function storedIds($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === '' || $value === null || $value === false) {
            return [];
        }

        return [$value];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function linkedPosts(int $organizerId): array
    {
        $idsByType = [
            'organizer' => [$organizerId],
            'chasse' => [],
            'enigme' => [],
            'indice' => [],
            'solution' => [],
            'enigme_etape' => [],
            'trophee' => [],
        ];
        $posts = [];
        foreach (self::LINKS as $link) {
            foreach ($idsByType[$link['from']] as $parentId) {
                $found = self::postsReferencing($link['type'], $link['key'], (int) $parentId);
                foreach ($found as $post) {
                    if (isset($posts[$post['id']])) {
                        continue;
                    }
                    $posts[$post['id']] = $post;
                    $idsByType[$link['type']][] = $post['id'];
                }
            }
        }

        return array_values($posts);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function postsReferencing(string $postType, string $metaKey, int $id): array
    {
        global $wpdb;
        if (!isset($wpdb) || $id <= 0) {
            return [];
        }

        $quoted = '%"' . $wpdb->esc_like((string) $id) . '"%';
        $integer = '%i:' . $wpdb->esc_like((string) $id) . ';%';
        $sql = "SELECT p.ID, p.post_author, p.post_title, p.post_status, p.post_type, pm.meta_value
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
            WHERE p.post_type = %s
              AND p.post_status <> 'auto-draft'
              AND pm.meta_key = %s
              AND (pm.meta_value = %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)";
        $rows = $wpdb->get_results($wpdb->prepare($sql, $postType, $metaKey, (string) $id, $quoted, $integer), ARRAY_A);
        $service = new OrganizerAccountTransferService();
        $posts = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!$service->containsId($row['meta_value'] ?? '', $id)) {
                continue;
            }
            $postId = (int) $row['ID'];
            $owner = $postType === 'chasse' ? get_post_meta($postId, 'organisateur_id', true) : 0;
            $posts[$postId] = [
                'id' => $postId,
                'type' => (string) $row['post_type'],
                'author' => (int) $row['post_author'],
                'title' => (string) $row['post_title'],
                'status' => (string) $row['post_status'],
                'organizer_user_id' => is_numeric($owner) ? (int) $owner : 0,
            ];
        }

        return array_values($posts);
    }

    /**
     * @param int[] $postIds
     * @return array<int, array{key:string,value:string}>
     */
    private static function metaRows(array $postIds): array
    {
        global $wpdb;
        $postIds = array_values(array_filter(array_map('intval', $postIds)));
        if ($postIds === [] || !isset($wpdb)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '%d'));
        $sql = "SELECT meta_key AS `key`, meta_value AS value FROM {$wpdb->postmeta} WHERE post_id IN ($placeholders)";
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$postIds), ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    /**
     * @param int[] $contentIds
     * @param int[] $referencedIds
     * @return array<int, array<string, mixed>>
     */
    private static function attachments(int $sourceId, array $contentIds, array $referencedIds): array
    {
        global $wpdb;
        if (!isset($wpdb) || $sourceId <= 0) {
            return [];
        }

        $contentIds = array_values(array_unique(array_filter(array_map('intval', $contentIds))));
        $referencedIds = array_values(array_unique(array_filter(array_map('intval', $referencedIds))));
        $where = ['post_author = %d'];
        $params = [$sourceId];
        if ($contentIds !== []) {
            $where[] = 'post_parent IN (' . implode(',', array_fill(0, count($contentIds), '%d')) . ')';
            foreach ($contentIds as $contentId) {
                $params[] = $contentId;
            }
        }
        if ($referencedIds !== []) {
            $where[] = 'ID IN (' . implode(',', array_fill(0, count($referencedIds), '%d')) . ')';
            foreach ($referencedIds as $referencedId) {
                $params[] = $referencedId;
            }
        }
        $sql = "SELECT ID, post_author, post_title, post_parent FROM {$wpdb->posts}
            WHERE post_type = 'attachment' AND (" . implode(' OR ', $where) . ')';
        $rows = $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A);
        $referenced = array_fill_keys($referencedIds, true);
        $items = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $id = (int) $row['ID'];
            $items[] = [
                'id' => $id,
                'author' => (int) $row['post_author'],
                'title' => (string) $row['post_title'],
                'parent_id' => (int) $row['post_parent'],
                'referenced' => isset($referenced[$id]),
            ];
        }

        return $items;
    }

    /**
     * @return int[]
     */
    private static function organizerIdsForUser(int $userId, OrganizerAccountTransferService $service): array
    {
        global $wpdb;
        if ($userId <= 0 || !isset($wpdb)) {
            return [];
        }

        $quoted = '%"' . $wpdb->esc_like((string) $userId) . '"%';
        $integer = '%i:' . $wpdb->esc_like((string) $userId) . ';%';
        $sql = "SELECT p.ID, pm.meta_value
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
            WHERE p.post_type = 'organisateur'
              AND p.post_status NOT IN ('auto-draft', 'trash')
              AND pm.meta_key = 'utilisateurs_associes'
              AND (pm.meta_value = %s OR pm.meta_value LIKE %s OR pm.meta_value LIKE %s)";
        $rows = $wpdb->get_results(
            $wpdb->prepare($sql, (string) $userId, $quoted, $integer),
            ARRAY_A
        );
        $ids = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if ($service->containsId($row['meta_value'] ?? '', $userId)) {
                $ids[] = (int) $row['ID'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param array<string, mixed> $plan
     */
    private static function apply(array $plan): void
    {
        $targetId = (int) $plan['target_user_id'];
        $sourceId = (int) $plan['source_user_id'];
        self::grantRoles($targetId, is_array($plan['roles_granted']) ? $plan['roles_granted'] : []);
        foreach ($plan['force_author_ids'] as $postId) {
            self::assignAuthor((int) $postId, $targetId, 0);
        }
        foreach ($plan['reauthor_ids'] as $postId) {
            self::assignAuthor((int) $postId, $targetId, $sourceId);
        }
        foreach ($plan['hunt_user_ids'] as $postId => $userId) {
            self::writeField('organisateur_id', (int) $userId, (int) $postId);
        }
        self::writeField(
            'utilisateurs_associes',
            array_map('strval', $plan['associated_user_ids']),
            (int) $plan['organizer_id']
        );
        self::revokeRoles($sourceId, is_array($plan['roles_revoked']) ? $plan['roles_revoked'] : []);
    }

    private static function assignAuthor(int $postId, int $authorId, int $onlyFrom): void
    {
        global $wpdb;
        if ($postId <= 0 || $authorId <= 0 || !isset($wpdb)) {
            return;
        }
        if ($onlyFrom > 0) {
            $current = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT post_author FROM {$wpdb->posts} WHERE ID = %d",
                $postId
            ));
            if ($current !== $onlyFrom) {
                return;
            }
        }
        $wpdb->update(
            $wpdb->posts,
            ['post_author' => $authorId],
            ['ID' => $postId],
            ['%d'],
            ['%d']
        );
        clean_post_cache($postId);
    }

    /**
     * @param mixed $value
     */
    private static function writeField(string $name, $value, int $postId): void
    {
        if ($postId <= 0) {
            return;
        }
        if (function_exists('update_field')) {
            update_field($name, $value, $postId);

            return;
        }
        update_post_meta($postId, $name, $value);
    }

    /**
     * @param string[] $roles
     */
    private static function grantRoles(int $userId, array $roles): void
    {
        if ($userId <= 0 || $roles === [] || !class_exists('WP_User')) {
            return;
        }
        $user = new \WP_User($userId);
        foreach ($roles as $role) {
            $user->add_role($role);
        }
    }

    /**
     * @param string[] $roles
     */
    private static function revokeRoles(int $userId, array $roles): void
    {
        if ($userId <= 0 || $roles === [] || !class_exists('WP_User')) {
            return;
        }
        $user = new \WP_User($userId);
        foreach ($roles as $role) {
            $user->remove_role($role);
        }
    }

    /**
     * @return \WP_User|null
     */
    private static function findUser(string $raw)
    {
        $raw = trim($raw);
        if ($raw === '' || !function_exists('get_user_by')) {
            return null;
        }
        if (preg_match('/^[1-9][0-9]*$/', $raw) === 1) {
            $user = get_user_by('id', (int) $raw);

            return $user instanceof \WP_User ? $user : null;
        }
        $byEmail = get_user_by('email', $raw);
        if ($byEmail instanceof \WP_User) {
            return $byEmail;
        }
        $byLogin = get_user_by('login', $raw);

        return $byLogin instanceof \WP_User ? $byLogin : null;
    }

    /**
     * @param \WP_User|null $user
     */
    private static function userLabel($user): string
    {
        if (!$user instanceof \WP_User) {
            return '';
        }
        $name = $user->display_name !== '' ? $user->display_name : $user->user_login;

        return sprintf('%s — %s — ID %d', $name, $user->user_email, (int) $user->ID);
    }

    /**
     * @param array<string, mixed> $state
     */
    private static function renderFeedback(array $state): void
    {
        $plan = $state['plan'];
        if (!is_array($plan)) {
            return;
        }
        if (!empty($state['applied'])) {
            echo '<div class="notice notice-success"><p>';
            echo esc_html(sprintf(
                /* translators: %s: organizer title */
                __('Transfert effectué pour « %s ».', 'chassesautresor-com'),
                (string) $plan['organizer_title']
            ));
            echo '</p></div>';
        }
        if (!empty($plan['blocked'])) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html(implode(' ', array_map([self::class, 'errorMessage'], $plan['errors'])));
            echo '</p></div>';
        }
        if ($plan['notices'] !== [] && (empty($plan['blocked']) || !empty($state['confirm_error']))) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html(implode(' ', array_map(
                static fn (string $code): string => self::noticeMessage($code, $plan),
                $plan['notices']
            )));
            echo '</p></div>';
        }
    }

    /**
     * @param array<string, mixed> $plan
     */
    private static function renderPlan(array $plan): void
    {
        echo '<h2>' . esc_html((string) $plan['organizer_title']) . '</h2>';
        echo '<p>' . esc_html(sprintf(
            /* translators: 1: post count, 2: media count */
            __('Contenus dont l’auteur change : %1$d. Médias : %2$d.', 'chassesautresor-com'),
            count($plan['posts']),
            count($plan['attachments'])
        )) . '</p>';
        if ($plan['roles_granted'] !== []) {
            echo '<p>' . esc_html(sprintf(
                /* translators: %s: role list */
                __('Rôle copié sur le nouveau compte : %s.', 'chassesautresor-com'),
                implode(', ', array_map([self::class, 'roleLabel'], $plan['roles_granted']))
            )) . '</p>';
        }
        if ($plan['roles_revoked'] !== []) {
            echo '<p>' . esc_html__('Ce rôle est retiré de l’ancien compte.', 'chassesautresor-com') . '</p>';
        }
        self::renderTable(__('Contenus', 'chassesautresor-com'), $plan['posts'], 'type');
        self::renderTable(__('Médias', 'chassesautresor-com'), $plan['attachments'], 'reason');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private static function renderTable(string $caption, array $rows, string $detailKey): void
    {
        if ($rows === []) {
            return;
        }
        echo '<table class="widefat striped"><caption>' . esc_html($caption) . '</caption><thead><tr>';
        echo '<th>' . esc_html__('ID', 'chassesautresor-com') . '</th>';
        echo '<th>' . esc_html__('Titre', 'chassesautresor-com') . '</th>';
        echo '<th>' . esc_html__('Détail', 'chassesautresor-com') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $detail = $detailKey === 'reason'
                ? self::reasonLabel((string) ($row['reason'] ?? ''))
                : self::typeLabel((string) ($row['type'] ?? ''));
            echo '<tr><td>' . esc_html((string) $row['id']) . '</td>';
            echo '<td>' . esc_html((string) ($row['title'] ?? '')) . '</td>';
            echo '<td>' . esc_html($detail) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    private static function renderUserLine(string $label): void
    {
        if ($label === '') {
            return;
        }
        echo '<p class="description">' . esc_html($label) . '</p>';
    }

    private static function errorMessage(string $code): string
    {
        switch ($code) {
            case 'invalid_organizer':
                return __('Cet ID ne correspond pas à une fiche organisateur.', 'chassesautresor-com');
            case 'missing_source':
                return __('Le compte actuel est introuvable.', 'chassesautresor-com');
            case 'missing_target':
                return __('Le nouveau compte est introuvable.', 'chassesautresor-com');
            case 'same_user':
                return __('Les deux comptes sont identiques.', 'chassesautresor-com');
            case 'source_not_linked':
                return __('Ce compte n’est pas le propriétaire de cette fiche.', 'chassesautresor-com');
            case 'target_already_linked':
                return __(
                    'Le nouveau compte a déjà une fiche organisateur. '
                    . 'Mettez cette fiche vide à la corbeille, puis recommencez.',
                    'chassesautresor-com'
                );
            case 'already_transferred':
                return __('Ce transfert est déjà fait.', 'chassesautresor-com');
            case 'confirm_required':
                return __('Cochez la confirmation pour lancer le transfert.', 'chassesautresor-com');
            default:
                return $code;
        }
    }

    /**
     * @param array<string, mixed> $plan
     */
    private static function noticeMessage(string $code, array $plan): string
    {
        switch ($code) {
            case 'contact_email':
                return sprintf(
                    /* translators: %s: public contact email */
                    __(
                        'L’e-mail public de la fiche reste « %s ». Modifiez-le ensuite sur la fiche si besoin.',
                        'chassesautresor-com'
                    ),
                    (string) $plan['contact_email']
                );
            case 'foreign_authors':
                return sprintf(
                    /* translators: %d: number of posts left unchanged */
                    __(
                        '%d contenu(s) restent sur un autre auteur, souvent un administrateur. '
                        . 'Leur texte ne change pas.',
                        'chassesautresor-com'
                    ),
                    (int) $plan['foreign_author_count']
                );
            case 'shared_attachments':
                return sprintf(
                    /* translators: %d: number of media files left unchanged */
                    __(
                        '%d média(s) déjà utilisé(s) restent sur un autre auteur, souvent l’image par défaut.',
                        'chassesautresor-com'
                    ),
                    (int) $plan['shared_attachment_count']
                );
            case 'orphans_skipped':
                return sprintf(
                    /* translators: %d: number of unused uploads left on the old account */
                    __('%d fichier(s) isolé(s) restent sur l’ancien compte.', 'chassesautresor-com'),
                    (int) $plan['orphan_skipped_count']
                );
            case 'source_keeps_roles':
                return __(
                    'L’ancien compte garde son rôle organisateur, car il possède encore une autre fiche.',
                    'chassesautresor-com'
                );
            case 'no_organizer_role':
                return __(
                    'L’ancien compte n’a pas le rôle organisateur. Le nouveau compte ne le recevra pas non plus.',
                    'chassesautresor-com'
                );
            default:
                return $code;
        }
    }

    private static function typeLabel(string $type): string
    {
        switch ($type) {
            case 'organisateur':
                return __('Organisateur', 'chassesautresor-com');
            case 'chasse':
                return __('Chasse', 'chassesautresor-com');
            case 'enigme':
                return __('Énigme', 'chassesautresor-com');
            case 'indice':
                return __('Indice', 'chassesautresor-com');
            case 'solution':
                return __('Solution', 'chassesautresor-com');
            case 'enigme_etape':
                return __('Étape', 'chassesautresor-com');
            case 'trophee':
                return __('Trophée', 'chassesautresor-com');
            default:
                return $type;
        }
    }

    private static function reasonLabel(string $reason): string
    {
        switch ($reason) {
            case 'referenced':
                return __('Utilisé dans un champ', 'chassesautresor-com');
            case 'parent':
                return __('Rattaché au contenu', 'chassesautresor-com');
            case 'orphan':
                return __('Fichier du compte, sans parent', 'chassesautresor-com');
            default:
                return $reason;
        }
    }

    private static function roleLabel(string $role): string
    {
        switch ($role) {
            case 'organisateur':
                return __('organisateur', 'chassesautresor-com');
            case 'organisateur_creation':
                return __('organisateur en création', 'chassesautresor-com');
            default:
                return $role;
        }
    }
}
