<?php
/**
 * Template Name: Traitement Tentative (Confirmation explicite)
 */

require_once get_stylesheet_directory() . '/inc/enigme-functions.php';

$uid = sanitize_text_field($_GET['uid'] ?? '');
if (!$uid) wp_die( __( 'Paramètre UID manquant.', 'chassesautresor-com' ) );

$tentative = get_tentative_by_uid($uid);
if (!$tentative) wp_die( __( 'Tentative introuvable.', 'chassesautresor-com' ) );

$enigme_id = (int) $tentative->enigme_id;
$infos = recuperer_infos_tentative($uid);
$etat = $infos['etat_tentative'] ?? 'invalide';
$permalink = get_permalink($enigme_id);

// 🔐 Protection d’accès : organisateur ou admin
$chasse_id = recuperer_id_chasse_associee($enigme_id);
$organisateur_id = get_organisateur_from_chasse($chasse_id);
$organisateur_user_ids = (array) get_field('utilisateurs_associes', $organisateur_id);
$current_user_id = get_current_user_id();

if (
  !current_user_can('manage_options') &&
  !in_array($current_user_id, array_map('intval', $organisateur_user_ids), true)
) {
  wp_die( __( '⛔️ Accès refusé.', 'chassesautresor-com' ) );
}

// 💚 Réinitialisations
if (isset($_GET['reset_tentatives'])) {
    global $wpdb;
    $reset = $wpdb->delete($wpdb->prefix . 'enigme_tentatives', ['enigme_id' => $enigme_id], ['%d']);
    printf('<p style="text-align:center;">%s</p>', sprintf( esc_html__( '🧹 %d tentative(s) supprimée(s).', 'chassesautresor-com' ), $reset ));
    return;
  }

if (isset($_GET['reset_statuts'])) {
    global $wpdb;
    $reset = $wpdb->delete($wpdb->prefix . 'enigme_statuts_utilisateur', ['enigme_id' => $enigme_id], ['%d']);
    printf('<p style="text-align:center;">%s</p>', sprintf( esc_html__( '🗑️ %d statut(s) utilisateur supprimé(s).', 'chassesautresor-com' ), $reset ));
    return;
  }

if (isset($_GET['reset_all'])) {
    global $wpdb;
    $reset1 = $wpdb->delete($wpdb->prefix . 'enigme_tentatives', ['enigme_id' => $enigme_id], ['%d']);
    $reset2 = $wpdb->delete($wpdb->prefix . 'enigme_statuts_utilisateur', ['enigme_id' => $enigme_id], ['%d']);
    printf('<p style="text-align:center;">%s</p>', sprintf( esc_html__( '🔥 %1$d tentative(s) & %2$d statut(s) supprimés.', 'chassesautresor-com' ), $reset1, $reset2 ));
    return;
  }

// ✅ Traitement si POST (validation ou refus)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_traitement'], $_POST['uid'])) {
  check_admin_referer('traiter_tentative_' . $uid);
  $action = sanitize_text_field($_POST['action_traitement']);
  $uid_post = sanitize_text_field($_POST['uid']);

  if ($uid_post === $uid && in_array($action, ['valider', 'invalider'], true)) {
    $resultat = $action === 'valider' ? 'bon' : 'faux';
    $effectue = traiter_tentative_manuelle($uid, $resultat);
    wp_safe_redirect(add_query_arg('done', $effectue ? '1' : '0'));
    exit;
  }
}

get_header();
?>

<main class="page-traitement-tentative">
  <div class="container">
    <section class="bloc-infos">
        <h2>
          <?php
          printf(
            esc_html__( 'Tentative de %1$s pour l’énigme %2$s', 'chassesautresor-com' ),
            '<strong>' . esc_html( $infos['nom_user'] ?? esc_html__( 'Inconnu', 'chassesautresor-com' ) ) . '</strong>',
            '<strong>' . esc_html( get_the_title( $enigme_id ) ) . '</strong>'
          );
          ?>
        </h2>

        <p><strong><?php esc_html_e('Identifiant unique de tentative :', 'chassesautresor-com'); ?></strong> <?= esc_html($uid); ?></p>
        <p><strong><?php esc_html_e('Statut :', 'chassesautresor-com'); ?></strong> <?= esc_html( ucfirst( $etat ) ); ?></p>
        <p><a href="<?= esc_url($permalink); ?>" class="lien-enigme"><?php esc_html_e('🔍 Voir l’énigme', 'chassesautresor-com'); ?></a></p>
    </section>

    <?php if ($etat === 'attente'): ?>
      <form method="post" class="form-traitement">
        <?php wp_nonce_field('traiter_tentative_' . $uid); ?>
        <input type="hidden" name="uid" value="<?= esc_attr($uid); ?>">

          <div class="boutons">
            <button type="submit" name="action_traitement" value="valider" class="bouton-cta"><?php esc_html_e('✅ Valider', 'chassesautresor-com'); ?></button>
            <button type="submit" name="action_traitement" value="invalider" class="btn-danger"><?php esc_html_e('❌ Refuser', 'chassesautresor-com'); ?></button>
          </div>
      </form>
    <?php else: ?>
        <div class="bloc-deja-traitee">
          <?php
          $etat_label = $etat === 'validee' ? esc_html__( 'validée', 'chassesautresor-com' ) : esc_html__( 'refusée', 'chassesautresor-com' );
          printf( esc_html__( 'Cette tentative a été %s.', 'chassesautresor-com' ), '<strong>' . esc_html( $etat_label ) . '</strong>' );
          ?>
        </div>
    <?php endif; ?>
  </div>

  <div class="traitement-actions">
      <a href="<?= esc_url(add_query_arg('reset_statuts', '1')); ?>"
        onclick="return confirm('<?php echo esc_js( __( 'Supprimer tous les statuts utilisateurs pour cette énigme ?', 'chassesautresor-com' ) ); ?>');"
        class="btn-danger">
        <?php esc_html_e('🧹 Réinitialiser les statuts', 'chassesautresor-com'); ?>
      </a>

      <a href="<?= esc_url(add_query_arg('reset_tentatives', '1')); ?>"
        onclick="return confirm('<?php echo esc_js( __( 'Supprimer toutes les tentatives pour cette énigme ?', 'chassesautresor-com' ) ); ?>');"
        class="btn-danger">
        <?php esc_html_e('❌ Supprimer les tentatives', 'chassesautresor-com'); ?>
      </a>

      <a href="<?= esc_url(add_query_arg('reset_all', '1')); ?>"
        onclick="return confirm('<?php echo esc_js( __( 'Supprimer TOUT (statuts + tentatives) ?', 'chassesautresor-com' ) ); ?>');"
        class="btn-danger">
        <?php esc_html_e('🔥 Tout supprimer', 'chassesautresor-com'); ?>
      </a>
  </div>
</main>

<?php get_footer(); ?>
