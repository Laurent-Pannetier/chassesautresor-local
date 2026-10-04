<?php
/**
 * File to display navbar in the plugin.
 *
 * @package    password-policy-manager/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
			echo '<div class="wrap">
			<div id ="moppm_message"></div>
				<div><img  style="float:left;margin-top:5px;" src="' . esc_url( $moppm_logo_url ) . '"></div>
				<h1 class="moppm_h1_ad">
					' . esc_html__( 'miniOrange Password Policy', 'password-policy-manager' ) . '
					
                    <a class="add-new-h2" id ="moppm_upgrade" style="font-size:14px;border-radius:4px;background-color:orange; color:white; color:#fff;border:none" href="' . esc_url( $moppm_upgrade_url ) . '">' . esc_html__( 'Upgrade', 'password-policy-manager' ) . '</a>
                    <a class="add-new-h2" id ="moppm_account" style="font-size:14px;border-radius:4px;background:var(--wp-admin-theme-color); color:white;" href="' . esc_url( $moppm_account_url ) . '">' . esc_html__( 'Account', 'password-policy-manager' ) . '</a></h1>';


					echo '<div class="nav-tab-wrapper" style="width: 98%;">';
					echo '<a id="moppm_menu" class="nav-tab ' . esc_attr(
						( 'moppm' === $moppm_active_tab
						? 'nav-tab-active' : '' )
					) . '" href="' . esc_url( $moppm_configuration_url ) . '"><span class="dashicons dashicons-admin-generic"></span>' . esc_html__( 'Policy Settings', 'password-policy-manager' ) . '</a>';

					echo '<a id="moppm_reports" class="nav-tab ' . esc_attr(
						( 'moppm_reports' === $moppm_active_tab
						? 'nav-tab-active' : '' )
					) . '" href="' . esc_url( $moppm_report_url ) . '"><span class="dashicons dashicons-media-spreadsheet"></span>' . esc_html__( 'Reports', 'password-policy-manager' ) . '</a>';

					echo '<a id="moppm_registration_form" class="nav-tab ' . esc_attr(
						( 'moppm_registration_form' === $moppm_active_tab
						? 'nav-tab-active' : '' )
					) . '" href="' . esc_url( $moppm_registration_url ) . '"><span class="dashicons dashicons-forms"></span>' . esc_html__( 'Integrations', 'password-policy-manager' ) . '</a>';

					echo '<a id="moppm_addons" class="nav-tab ' . esc_attr(
						( 'moppm_addons' === $moppm_active_tab
						? 'nav-tab-active' : '' )
					) . '" href="' . esc_url( $moppm_addon_url ) . '"><span class="dashicons dashicons-money-alt"></span>' . esc_html__( 'Premium Features', 'password-policy-manager' ) . '</a>';


					echo '<a id="moppm_advertise" class="nav-tab ' . esc_attr(
						( 'moppm_advertise' === $moppm_active_tab
						? 'nav-tab-active' : '' )
					) . '" href="' . esc_url( $moppm_advertise_url ) . '"><span class="dashicons dashicons-admin-plugins"></span>' . esc_html__( 'Other Plugins', 'password-policy-manager' ) . ' <sup>2</sup></a></div>';
