function Moppm_nav_popup() {
  document.getElementById("notice_div").style.width = "40%";
  setTimeout(function(){ jQuery('#notice_div').fadeOut('slow'); }, 3000);
}

function moppm_notice_plain_text( raw ) {
	if ( typeof raw === 'object' && raw !== null && typeof raw.message === 'string' ) {
		return raw.message;
	}
	if ( raw === null || raw === undefined || typeof raw === 'object' ) {
		return '';
	}
	return String( raw );
}

function Moppm_error_msg( error ) {
	jQuery( '#moppm_message' ).empty();
	var text = moppm_notice_plain_text( error );
	var $notice = jQuery( '<div/>', { id: 'notice_div', class: 'moppm_overlay_error' } );
	jQuery( '<div/>', { class: 'popup_text', css: { marginLeft: '10px' } } ).text( text ).appendTo( $notice );
	jQuery( '#moppm_message' ).append( $notice );
	window.onload = Moppm_nav_popup();
}

function Moppm_success_msg( success ) {
	jQuery( '#moppm_message' ).empty();
	var text = moppm_notice_plain_text( success );
	var $notice = jQuery( '<div/>', { id: 'notice_div', class: 'moppm_overlay_success' } );
	jQuery( '<div/>', { class: 'popup_text', css: { marginLeft: '10px' } } ).text( text ).appendTo( $notice );
	jQuery( '#moppm_message' ).append( $notice );
	window.onload = Moppm_nav_popup();
}

function moSharingSizeValidate(e){
	var t=parseInt(e.value.trim());t>60?e.value=60:10>t&&(e.value=10)
}
function moSharingSpaceValidate(e){
	var t=parseInt(e.value.trim());t>50?e.value=50:0>t&&(e.value=0)
}
function moLoginSizeValidate(e){
	var t=parseInt(e.value.trim());t>60?e.value=60:20>t&&(e.value=20)
}
function moLoginSpaceValidate(e){
	var t=parseInt(e.value.trim());t>60?e.value=60:0>t&&(e.value=0)
}
function moLoginWidthValidate(e){
	var t=parseInt(e.value.trim());t>1000?e.value=1000:140>t&&(e.value=140)
}
function moLoginHeightValidate(e){
	var t=parseInt(e.value.trim());t>50?e.value=50:35>t&&(e.value=35)
}