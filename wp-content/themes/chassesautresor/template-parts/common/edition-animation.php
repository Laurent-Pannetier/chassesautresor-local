<?php
/**
 * Template Part: Edition Animation tab for chasses, enigmes and organizers.
 *
 * Expected arguments:
 * - objet_type (string): 'chasse', 'enigme' or 'organisateur'.
 * - objet_id   (int): Object ID.
 * - liens      (array): Public links (optional).
 * - peut_modifier (bool): Whether current user can modify links (optional).
 * - statut_metier, mode_fin, bloc_fin_chasse, date_decouverte_formatee, gagnants
 *   (optional for chasse stop card).
 * - afficher_qr_code (bool), url (string), url_qr_code (string) for QR code card.
 *
 * Hooks:
 * - `chassesautresor/edition_animation_indices_query_args` to filter indices query args.
 * - `chassesautresor/edition_animation_solutions_query_args` to filter solutions query args.
 * - `chassesautresor/edition_animation_indice_prefill` to filter prefill data for new indices.
 * - `chassesautresor/edition_animation_solution_prefill` to filter prefill data for new solutions.
 */

defined('ABSPATH') || exit;

$objet_type = $args['objet_type'] ?? '';
$objet_id   = isset($args['objet_id']) ? (int) $args['objet_id'] : 0;

if (!$objet_type || !$objet_id) {
    return;
}

$liens               = $args['liens'] ?? [];
$peut_modifier       = $args['peut_modifier'] ?? false;
$statut_metier       = $args['statut_metier'] ?? '';
$mode_fin            = $args['mode_fin'] ?? '';
$bloc_fin_chasse     = $args['bloc_fin_chasse'] ?? '';
$date_decouverte     = $args['date_decouverte_formatee'] ?? '';
$gagnants            = $args['gagnants'] ?? '';
$afficher_qr_code    = $args['afficher_qr_code'] ?? false;
$url                 = $args['url'] ?? '';
$url_qr_code         = $args['url_qr_code'] ?? '';

// Prefill hooks for future dynamic field population.
$indice_prefill   = apply_filters('chassesautresor/edition_animation_indice_prefill', [], $args);
$solution_prefill = apply_filters('chassesautresor/edition_animation_solution_prefill', [], $args);

?>
<div id="<?= esc_attr($objet_type); ?>-tab-animation" class="edition-tab-content" style="display:none;">
  <i class="fa-solid fa-bullhorn tab-watermark" aria-hidden="true"></i>
  <div class="edition-panel-header">
    <h2>
      <i class="fa-solid fa-bullhorn"></i>
      <?php
      if ($objet_type === 'enigme') {
          esc_html_e('Animation de cette énigme', 'chassesautresor-com');
      } else {
          esc_html_e('Animation', 'chassesautresor-com');
      }
      ?>
    </h2>
  </div>
  <div class="edition-panel-body">
    <div class="edition-panel-section edition-panel-section-ligne">
      <div class="section-content">
        <div class="dashboard-grid stats-cards">
          <?php if ($objet_type === 'chasse') : ?>
            <div class="dashboard-card carte-orgy champ-chasse champ-liens <?= empty($liens) ? 'champ-vide' : 'champ-rempli'; ?>"
              data-champ="chasse_principale_liens"
              data-cpt="chasse"
              data-post-id="<?= esc_attr($objet_id); ?>">
              <span class="carte-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
              <i class="fa-solid fa-share-nodes icone-defaut" aria-hidden="true"></i>
              <div class="champ-affichage champ-affichage-liens">
                <?= render_liens_publics($liens, 'chasse', ['placeholder' => false]); ?>
              </div>
              <h3><?= esc_html__('Sites et réseaux de la chasse', 'chassesautresor-com'); ?></h3>
              <?php if ($peut_modifier) : ?>
                <button type="button"
                  class="bouton-cta champ-modifier ouvrir-panneau-liens"
                  data-champ="chasse_principale_liens"
                  data-cpt="chasse"
                  data-post-id="<?= esc_attr($objet_id); ?>">
                  <?= empty($liens)
                    ? esc_html__('Ajouter', 'chassesautresor-com')
                    : esc_html__('Éditer', 'chassesautresor-com'); ?>
                </button>
              <?php endif; ?>
              <div class="champ-donnees"
                data-valeurs='<?= json_encode($liens, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'></div>
              <div class="champ-feedback"></div>
            </div>
          <?php elseif ($objet_type === 'organisateur') : ?>
            <div class="dashboard-card carte-orgy champ-organisateur champ-liens <?= empty($liens) ? 'champ-vide' : 'champ-rempli'; ?>"
              data-champ="liens_publics"
              data-cpt="organisateur"
              data-post-id="<?= esc_attr($objet_id); ?>">
              <span class="carte-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
              <i class="fa-solid fa-share-nodes icone-defaut" aria-hidden="true"></i>
              <div class="champ-affichage champ-affichage-liens">
                <?= render_liens_publics($liens, 'organisateur', ['placeholder' => false]); ?>
              </div>
              <h3><?= esc_html__("Sites et réseaux de l'organisation", 'chassesautresor-com'); ?></h3>
              <?php if ($peut_modifier) : ?>
                <button type="button"
                  class="bouton-cta champ-modifier ouvrir-panneau-liens"
                  data-champ="liens_publics"
                  data-cpt="organisateur"
                  data-post-id="<?= esc_attr($objet_id); ?>">
                  <?= empty($liens)
                    ? esc_html__('Ajouter', 'chassesautresor-com')
                    : esc_html__('Éditer', 'chassesautresor-com'); ?>
                </button>
              <?php endif; ?>
              <div class="champ-donnees"
                data-valeurs='<?= json_encode($liens, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>'></div>
              <div class="champ-feedback"></div>
            </div>
          <?php endif; ?>

          <?php
          if ($objet_type !== 'organisateur') {
              get_template_part('template-parts/chasse/partials/chasse-partial-indices', null, [
                  'objet_id'   => $objet_id,
                  'objet_type' => $objet_type,
              ]);
              get_template_part('template-parts/chasse/partials/chasse-partial-solutions', null, [
                  'objet_id'   => $objet_id,
                  'objet_type' => $objet_type,
              ]);
          }
          ?>

          <?php if ($objet_type === 'chasse') : ?>
            <div class="dashboard-card carte-orgy champ-chasse carte-arret-chasse" style="<?= ($statut_metier !== 'termine' && $mode_fin !== 'manuelle') ? 'display:none;' : ''; ?>">
              <span class="carte-check" aria-hidden="true"><i class="fa-solid fa-check"></i></span>
              <i class="fa-solid fa-hand icone-defaut" aria-hidden="true"></i>
              <h3><?= esc_html__('Arrêt chasse', 'chassesautresor-com'); ?></h3>
              <div class="stat-value fin-chasse-actions">
                <?php if ($statut_metier === 'termine') : ?>
                  <p class="message-chasse-terminee">
                    <?= sprintf(__('Chasse gagnée le %s par %s', 'chassesautresor-com'), esc_html($date_decouverte), esc_html($gagnants)); ?>
                  </p>
                <?php elseif ($mode_fin === 'manuelle') : ?>
                  <?= $bloc_fin_chasse; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($afficher_qr_code && $url && $url_qr_code) : ?>
          <div class="dashboard-card carte-orgy champ-qr-code">
            <div class="qr-code-block">
              <div class="qr-code-url txt-small">
                <?php if ($objet_type === 'organisateur') : ?>
                  <?= esc_html__("Adresse de votre organisation :", 'chassesautresor-com'); ?>
                <?php else : ?>
                  <?= esc_html__('Adresse de votre chasse&nbsp;:', 'chassesautresor-com'); ?>
                <?php endif; ?>
                <?= esc_html($url); ?>
              </div>
              <div class="qr-code-image">
                <img src="<?= esc_url($url_qr_code); ?>" alt="<?= esc_attr($objet_type === 'organisateur' ? __('QR code de votre organisation', 'chassesautresor-com') : __('QR code de votre chasse', 'chassesautresor-com')); ?>">
              </div>
              <div class="qr-code-content">
                <?php if ($objet_type === 'organisateur') : ?>
                  <h3><?= esc_html__("QR code de votre organisation", 'chassesautresor-com'); ?></h3>
                  <h4><?= esc_html__("Partagez votre organisation en un scan", 'chassesautresor-com'); ?></h4>
                  <p><?= esc_html__('Facilitez l\'accès à votre organisation avec un simple scan. Un QR code évite de saisir une URL et se partage facilement.', 'chassesautresor-com'); ?></p>
                  <a class="bouton-cta qr-code-download" href="<?= esc_url($url_qr_code); ?>" download="<?= esc_attr('qr-organisateur-' . $objet_id . '.png'); ?>">
                    <?= esc_html__('Télécharger', 'chassesautresor-com'); ?>
                  </a>
                <?php else : ?>
                  <h3><?= esc_html__('QR code de votre chasse', 'chassesautresor-com'); ?></h3>
                  <h4><?= esc_html__('Partagez votre chasse en un scan', 'chassesautresor-com'); ?></h4>
                  <p><?= esc_html__('Facilitez l\'accès à votre chasse avec un simple scan. Un QR code évite de saisir une URL et se partage facilement.', 'chassesautresor-com'); ?></p>
                  <a class="bouton-cta qr-code-download" href="<?= esc_url($url_qr_code); ?>" download="<?= esc_attr('qr-chasse-' . $objet_id . '.png'); ?>">
                    <?= esc_html__('Télécharger', 'chassesautresor-com'); ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($objet_type !== 'organisateur') : ?>
          <?php
          $par_page_indices = 5;
          $page_indices     = 1;

          $indices_objet_type = $objet_type;
          $indices_objet_id   = $objet_id;
          $chasse_id          = 0;
          $has_enigme_indices = false;

          if ($objet_type === 'enigme') {
              $chasse_id = (int) recuperer_id_chasse_associee($objet_id);
              $has_enigme_indices = function_exists('get_posts') ? count(get_posts([
                  'post_type'   => 'indice',
                  'post_status' => ['publish', 'pending', 'draft'],
                  'fields'      => 'ids',
                  'nopaging'    => true,
                  'meta_query'  => [
                      [
                          'key'   => 'indice_cible_type',
                          'value' => 'enigme',
                      ],
                      [
                          'key'   => 'indice_enigme_linked',
                          'value' => $objet_id,
                      ],
                  ],
              ])) > 0 : false;

              if (!$has_enigme_indices && $chasse_id) {
                  $indices_objet_type = 'chasse';
                  $indices_objet_id   = $chasse_id;
              }
          }

          if ($indices_objet_type === 'chasse') {
              $enigme_ids = recuperer_ids_enigmes_pour_chasse($indices_objet_id);
              $meta       = [
                  'relation' => 'OR',
                  [
                      'relation' => 'AND',
                      [
                          'key'   => 'indice_cible_type',
                          'value' => 'chasse',
                      ],
                      [
                          'key'   => 'indice_chasse_linked',
                          'value' => $indices_objet_id,
                      ],
                  ],
              ];
              if (!empty($enigme_ids)) {
                  $meta[] = [
                      'relation' => 'AND',
                      [
                          'key'   => 'indice_cible_type',
                          'value' => 'enigme',
                      ],
                      [
                          'key'     => 'indice_enigme_linked',
                          'value'   => $enigme_ids,
                          'compare' => 'IN',
                      ],
                  ];
              }
          } else {
              $meta = [
                  [
                      'key'   => 'indice_cible_type',
                      'value' => 'enigme',
                  ],
                  [
                      'key'   => 'indice_enigme_linked',
                      'value' => $indices_objet_id,
                  ],
              ];
          }

          $indices_query_args = [
              'post_type'      => 'indice',
              'post_status'    => ['publish', 'pending', 'draft'],
              'orderby'        => 'date',
              'order'          => 'DESC',
              'posts_per_page' => $par_page_indices,
              'paged'          => $page_indices,
              'meta_query'     => $meta,
          ];

          $indices_query_args = apply_filters('chassesautresor/edition_animation_indices_query_args', $indices_query_args, $args);
          $indices_query      = new WP_Query($indices_query_args);
          $indices_list       = $indices_query->posts;
          $pages_indices      = (int) $indices_query->max_num_pages;

          if ($indices_objet_type === 'chasse') {
              $count_chasse = function_exists('get_posts') ? count(get_posts([
                  'post_type'   => 'indice',
                  'post_status' => ['publish', 'pending', 'draft'],
                  'fields'      => 'ids',
                  'nopaging'    => true,
                  'meta_query'  => [
                      [
                          'key'   => 'indice_cible_type',
                          'value' => 'chasse',
                      ],
                      [
                          'key'   => 'indice_chasse_linked',
                          'value' => $indices_objet_id,
                      ],
                  ],
              ])) : 0;
              $count_enigme = !empty($enigme_ids) && function_exists('get_posts') ? count(get_posts([
                  'post_type'   => 'indice',
                  'post_status' => ['publish', 'pending', 'draft'],
                  'fields'      => 'ids',
                  'nopaging'    => true,
                  'meta_query'  => [
                      [
                          'key'   => 'indice_cible_type',
                          'value' => 'enigme',
                      ],
                      [
                          'key'     => 'indice_enigme_linked',
                          'value'   => $enigme_ids,
                          'compare' => 'IN',
                      ],
                  ],
              ])) : 0;
              $count_total  = $count_chasse + $count_enigme;
          } else {
              $count_enigme = function_exists('get_posts') ? count(get_posts([
                  'post_type'   => 'indice',
                  'post_status' => ['publish', 'pending', 'draft'],
                  'fields'      => 'ids',
                  'nopaging'    => true,
                  'meta_query'  => [
                      [
                          'key'   => 'indice_cible_type',
                          'value' => 'enigme',
                      ],
                      [
                          'key'   => 'indice_enigme_linked',
                          'value' => $indices_objet_id,
                      ],
                  ],
              ])) : 0;
              $count_chasse = 0;
              $count_total  = $count_enigme;
          }

          $par_page_solutions = 5;
          $page_solutions     = 1;

          if ($objet_type === 'chasse') {
              $meta_solutions = [
                  'relation' => 'OR',
                  [
                      'relation' => 'AND',
                      [
                          'key'   => 'solution_cible_type',
                          'value' => 'chasse',
                      ],
                      [
                          'key'   => 'solution_chasse_linked',
                          'value' => $objet_id,
                      ],
                  ],
              ];
              if (!empty($enigme_ids)) {
                  $meta_solutions[] = [
                      'relation' => 'AND',
                      [
                          'key'   => 'solution_cible_type',
                          'value' => 'enigme',
                      ],
                      [
                          'key'     => 'solution_enigme_linked',
                          'value'   => $enigme_ids,
                          'compare' => 'IN',
                      ],
                  ];
              }
          } else {
              $meta_solutions = [
                  [
                      'key'   => 'solution_cible_type',
                      'value' => 'enigme',
                  ],
                  [
                      'key'   => 'solution_enigme_linked',
                      'value' => $objet_id,
                  ],
              ];
          }

          $solutions_query_args = [
              'post_type'      => 'solution',
              'post_status'    => ['publish', 'pending', 'draft'],
              'orderby'        => 'date',
              'order'          => 'DESC',
              'posts_per_page' => $par_page_solutions,
              'paged'          => $page_solutions,
              'meta_query'     => $meta_solutions,
          ];

          $solutions_query_args = apply_filters('chassesautresor/edition_animation_solutions_query_args', $solutions_query_args, $args);
          $solutions_query      = new WP_Query($solutions_query_args);
          $solutions_list       = $solutions_query->posts;
          $pages_solutions      = (int) $solutions_query->max_num_pages;
          ?>

          <h3
            id="<?= esc_attr($objet_type); ?>-section-indices"
            data-titre-template="<?= esc_attr__('Indices pour %s', 'chassesautresor-com'); ?>"
            style="margin-top: var(--space-xl);"
          >
            <?php if ($indices_objet_type === 'enigme') : ?>
              <?= esc_html(sprintf(__('Indices pour %s', 'chassesautresor-com'), get_the_title($objet_id))); ?>
            <?php else : ?>
              <?= esc_html__('Indices', 'chassesautresor-com'); ?>
            <?php endif; ?>
          </h3>
          <?php if ($has_enigme_indices) : ?>
          <div class="indices-table-toggle">
            <span class="etiquette">
              <button type="button" class="indices-toggle champ-modifier" data-chasse-id="<?= esc_attr($chasse_id); ?>" data-enigme-id="<?= esc_attr($objet_id); ?>">
                <?= esc_html__('Voir tous les indices de la chasse', 'chassesautresor-com'); ?>
              </button>
            </span>
          </div>
          <?php endif; ?>
          <div class="liste-indices" data-page="1" data-pages="<?= esc_attr($pages_indices); ?>" data-objet-type="<?= esc_attr($indices_objet_type); ?>" data-objet-id="<?= esc_attr($indices_objet_id); ?>" data-enigme-id="<?= esc_attr($objet_id); ?>" data-chasse-id="<?= esc_attr($chasse_id); ?>" data-ajax-url="<?= esc_url(admin_url('admin-ajax.php')); ?>">
            <?php
            get_template_part('template-parts/common/indices-table', null, [
                'indices'      => $indices_list,
                'page'         => 1,
                'pages'        => $pages_indices,
                'objet_type'   => $indices_objet_type,
                'objet_id'     => $indices_objet_id,
                'count_total'  => $count_total,
                'count_chasse' => $count_chasse,
                'count_enigme' => $count_enigme,
            ]);
            ?>
          </div>

          <div class="dashboard-card carte-orgy champ-protection-solutions">
            <div class="qr-code-block">
              <div class="qr-code-image">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
              </div>
              <div class="qr-code-content">
                <h3><?= esc_html__('Sécurité des PDF de solution', 'chassesautresor-com'); ?></h3>
                <h4><?= esc_html__('Vos PDF sont conservés dans un coffre-fort numérique', 'chassesautresor-com'); ?></h4>
                <p>
                  <?= esc_html__('Les fichiers PDF de solution sont conservés dans un dossier protégé. ', 'chassesautresor-com'); ?>
                  <?= esc_html__("Ils ne seront partagés qu'à la date que vous aurez choisie : ", 'chassesautresor-com'); ?>
                  <?= esc_html__('immédiatement après la fin de la chasse ou après un délai paramétrable.', 'chassesautresor-com'); ?>
                </p>
              </div>
            </div>
          </div>

          <h3 id="<?= esc_attr($objet_type); ?>-section-solutions">
            <?= esc_html__('Solutions', 'chassesautresor-com'); ?>
          </h3>
          <div class="liste-solutions"
            data-page="1"
            data-pages="<?= esc_attr($pages_solutions); ?>"
            data-objet-type="<?= esc_attr($objet_type); ?>"
            data-objet-id="<?= esc_attr($objet_id); ?>"
            data-ajax-url="<?= esc_url(admin_url('admin-ajax.php')); ?>">
            <?php
            get_template_part('template-parts/common/solutions-table', null, [
                'solutions'  => $solutions_list,
                'page'       => 1,
                'pages'      => $pages_solutions,
                'objet_type' => $objet_type,
                'objet_id'   => $objet_id,
            ]);
            ?>
          </div>

          <div class="edition-animation-prefill" data-indice='<?= wp_json_encode($indice_prefill); ?>' data-solution='<?= wp_json_encode($solution_prefill); ?>'></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
