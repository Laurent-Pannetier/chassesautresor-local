<?php
/**
 * File to include all controller files
 *
 * @package password-policy-manager/controllers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	global $moppm_utility, $moppm_dirname;
	$moppm_controller_base = $moppm_dirname . 'controllers' . DIRECTORY_SEPARATOR;


if ( current_user_can( 'manage_options' ) ) {
	$moppm_curr_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading GET parameter from the URL for checking the page name, doesn't require nonce verification.
	if ( ! is_null( $moppm_curr_page ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading GET parameter from the URL for checking the page name, doesn't require nonce verification.
		if ( 'moppm_upgrade' === $moppm_curr_page ) {
			include $moppm_controller_base . 'moppm-upgrade.php';
		} else {
			include $moppm_controller_base . 'navbar.php';
			echo ' <br> <table class="moppm_main_table" style="width:100%;"><tr><td class="moppm_layout" style="width:74%;">';
			switch ( $moppm_curr_page ) {
				case 'moppm_account':
					include $moppm_controller_base . 'account.php';
					break;
				case 'moppm_menu':
					include $moppm_controller_base . 'configuration.php';
					break;
				case 'moppm':
					include $moppm_controller_base . 'password-policy.php';
					break;
				case 'moppm_addons':
					include $moppm_controller_base . 'moppm-addons.php';
					break;
				case 'moppm_reports':
					include $moppm_controller_base . 'moppm-reports.php';
					break;
				case 'moppm_registration_form':
					include $moppm_controller_base . 'moppm-registration-form.php';
					break;
				case 'moppm_advertise':
					include $moppm_controller_base . 'moppm-advertise.php';
					break;

			}
			echo '</td><td></td><td class="moppm_support_layout" style="width:25%;vertical-align:top;">';
			include $moppm_controller_base . 'support.php';
			echo '</td ></tr></table>';

		}
	}
}
