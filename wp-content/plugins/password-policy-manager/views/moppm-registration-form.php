<?php
/**
 * File to display different login forms supported by premium plugins.
 *
 * @package    password-policy-manager/views
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $moppm_dir;
$moppm_logo_woocommerce       = $moppm_dir . '/includes/images/woocommerce.png';
$moppm_logo_ultimate_member   = $moppm_dir . '/includes/images/ultimate_member.png';
$moppm_logo_user_registration = $moppm_dir . '/includes/images/user_registration.png';
$moppm_logo_buddy_press       = $moppm_dir . '/includes/images/BuddyPress.png';
$moppm_logo_memberpress       = $moppm_dir . '/includes/images/memberpress.png';
$moppm_logo_userpro           = $moppm_dir . '/includes/images/userpro.png';
$moppm_logo_gravity_forms     = $moppm_dir . '/includes/images/gravity-forms.png';
$moppm_logo_bbpress           = $moppm_dir . '/includes/images/bbpress.png';
$moppm_logo_ninja             = $moppm_dir . '/includes/images/ninja.png';
$moppm_logo_elementor         = $moppm_dir . '/includes/images/elementor.png';
?>
<div class="moppm_table_layout">
<h1 class="moppm_h1_ad"><b><?php esc_html_e( 'Integrations', 'password-policy-manager' ); ?></b> </h1><br>
<span class="moppm_text"><?php esc_html_e( 'We support most of the login forms in our premium plugin present on WordPress. And our plugin is tested with almost all the forms like Woocommerce, Ultimate Member, Elementor Pro, and so on ', 'password-policy-manager' ); ?><?php echo '  <a href="' . esc_url_raw( $moppm_upgrade_url ) . '" style="color:red;font-weight:bold;text-decoration: none !important;">'; ?>[ UPGRADE ]</a></span><br><br>


<div class="">
	<div>
		<table class="moppm_customloginform" style="width: 95%">
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_woocommerce ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">Woocommerce</h3>
				</td>
				<td style="align-items: right;">
					<label class="moppm_switch" >
					<input disabled type="checkbox"  id="moppm_woocommerce_form" name="moppm_woocommerce_form" style="opacity:0;">    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_ultimate_member ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">Ultimate Member</h3>
				</td>
				<td style="text-align: center;">
					<label class="moppm_switch" >
					<input disabled type="checkbox"  id="moppm_ultimete_member_form" name="moppm_ultimete_member_form">    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_buddy_press ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">Buddypress</h3>
				</td>
				<td style="text-align: center;">
					<label class="moppm_switch" >
					<input disabled type="checkbox"  id="moppm_Buddypress_form" name="moppm_Buddypress_form">    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td >
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_bbpress ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">BB press</h3>
				</td>
				<td style="text-align: center;">
				<label class="moppm_switch" >
					<input disabled type="checkbox"  id="moppm_BBpress_form" name="moppm_BBpress_form" >    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_user_registration ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">User Registration</h3>
				</td>
				<td style="text-align: center;">
				<label class="moppm_switch" >
				<input disabled type="checkbox"  id="moppm_User_registration_form" name="moppm_User_registration_form" >    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_memberpress ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">member press</h3>
				</td>
				<td style="text-align: center;">
				<label class="moppm_switch" >
				<input disabled type="checkbox"  id="moppm_Member_Press_form" name="moppm_Member_Press_form" >    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_userpro ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">User pro</h3>
				</td>
				<td style="text-align: center;">
				<label class="moppm_switch" >
				<input disabled type="checkbox"  id="moppm_USer_pro_form" name="moppm_USer_pro_form" >    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_gravity_forms ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">Gravity Forms</h3>
				</td>
				<td style="text-align: center; ">
				<label class="moppm_switch" >
				<input disabled type="checkbox"  id="moppm_Gravity_form" name="moppm_Gravity_form" >    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label> 
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
					<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_ninja ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">Ninja form</h3>
				</td>
				<td style="text-align: center; ">
				<label class="moppm_switch" >
				<input disabled type="checkbox"  id="moppm_Ninja_form" name="moppm_Ninja_form">    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label>
				</td>
				<td>
				</td>
			</tr>
			<tr>
				<td>
				<?php echo '<img style="height:30px;width:30px;display: inline;"src="' . esc_url_raw( $moppm_logo_elementor ) . '">'; ?><h3 style="margin-left: 15px; font-size: large; display: inline; float: inherit; padding-right: 50px;">Elementor Pro</h3>
				</td>
				<td style="text-align: center; ">
				<label class="moppm_switch" >
				<input disabled type="checkbox"  id="moppm_Elementor_pro_form" name="moppm_Elementor_pro_form">    
				<span class="moppm_switch_slider moppm_switch_round mo_ppm_form_switch"></span>
				</label> 
				</td>
				<td>
				</td>
			</tr>
		</table>
		<div style="text-align: center"> 
		</div>
	</div>
</div>
<script>
var elements = document.querySelectorAll(".moppm_form_column");
jQuery(".mo_ppm_form_switch").click(function(e){ 
	Moppm_error_msg("This feature is available in premium plugins.");
}); 
</script>
