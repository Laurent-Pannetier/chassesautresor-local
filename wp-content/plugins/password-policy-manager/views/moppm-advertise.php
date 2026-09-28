<?php
/**
 *  File to display other miniorange plugins.
 *
 * @package    password-policy-manager/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$moppm_allowed_html = array(
	'a'     => array(
		'href'  => array(),
		'class' => array(),
		'title' => array(),
	),
	'input' => array(
		'type'  => array(),
		'class' => array(),
		'value' => array(),
	),
);
?>
	<div class="moppm-2fa-ad">
		<div class="moppm-2fa-logo"></div>
		<div class="moppm-2fa-info">
			<div class="moppm-trynow-btn">
			<?php
			$moppm_ad_plugin_slug  = 'miniorange-2-factor-authentication';
			$moppm_ad_install_link = '<a href="' . esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . esc_html( $moppm_ad_plugin_slug ) . '&TB_iframe=true&width=800&height=600' ) ) . '" class="thickbox" title="More info about miniOrange\'s Two-Factor Authentication Plugin"><input type="button" class="button button-primary" value="Try Now"/></a>';
			echo wp_kses( $moppm_ad_install_link, $moppm_allowed_html );
			?>
		</div>
			<h1 class="moppm_h1_ad"><a href="https://wordpress.org/plugins/miniorange-2-factor-authentication/" target="_blank"><?php esc_html_e( 'miniOrange 2-factor Authentication (2FA with SMS, Email, Google Authenticator)', 'password-policy-manager' ); ?></a></h1>
			<div class="wporg-ratings" aria-label="4.5 out of 5 stars" data-title-template="%s out of 5 stars" data-rating="4.5" style="color:#ffb900;"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-half"></span>
			<span> <span><span><a href="https://wordpress.org/support/plugin/miniorange-2-factor-authentication/reviews" target="_blank">(350+ reviews)</a><span></div>
			<p> <?php esc_html_e( 'Ensures security of user applications and environments, so that right set of eyes have access to your sensitive information sitting on the cloud or on-premise. Provides multiple 2FA methods like Google Authenticator, Microsoft Authenticator, OTP over Email/SMS and more.', 'password-policy-manager' ); ?></b></p>
		</div>
	</div>

	<div class="moppm-2fa-ad">
		<div class="moppm-otp-logo"></div>
		<div class="moppm-2fa-info">
			<div class="moppm-trynow-btn">
			<?php
			$moppm_otp_plugin_slug  = 'miniorange-otp-verification';
			$moppm_otp_install_link = '<a href="' . esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . esc_html( $moppm_otp_plugin_slug ) . '&TB_iframe=true&width=800&height=600' ) ) . '" class="thickbox" title="More info about miniOrange\'s OTP Verification Plugin"><input type="button" class="button button-primary" value="Try Now"/></a>';
			echo wp_kses( $moppm_otp_install_link, $moppm_allowed_html );
			?>
		</div>
			<h1 class="moppm_h1_ad"><a href="https://wordpress.org/plugins/miniorange-otp-verification/" target="_blank"><?php esc_html_e( 'miniOrange OTP Login, Verification and SMS Notifications', 'password-policy-manager' ); ?></a></h1>
			<div class="wporg-ratings" aria-label="4.5 out of 5 stars" data-title-template="%s out of 5 stars" data-rating="4.5" style="color:#ffb900;"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-half"></span>
			<span> <span><span><a href="https://wordpress.org/support/plugin/miniorange-otp-verification/reviews" target="_blank">(400+ reviews)</a><span></div>
			<p> <?php esc_html_e( 'The WordPress OTP Verification plugin verifies users\' email IDs and mobile numbers through an OTP (One-Time Password) sent during registration, login, and contact form submissions. This ensures that only genuine users can sign up while preventing fake registrations and duplicate accounts.', 'password-policy-manager' ); ?></b></p>
		</div>
	</div>
