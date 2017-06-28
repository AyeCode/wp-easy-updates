(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */



})( jQuery );

function exup_enter_licence_key(link){
	jQuery(link).next('.external-updates-key-input').toggle('slow');
}

function exup_activate_theme_licence_key(theme,themeName,exupNonce){
	console.log(jQuery(theme).prev().prev('.external-updates-key-value').val());
	console.log(themeName);

	var key = jQuery(theme).prev().prev('.external-updates-key-value').val();
	if(!key){return;}

	var data = {
		'security': exupNonce,
		'action': 'exup_ajax_handler',
		'exup_action': 'activate_key',
		'exup_key': key,
		'exup_theme': themeName
	};

	console.log(data);

	jQuery.post(ajaxurl, data, function(response) {
		var obj = jQuery.parseJSON(response);
		console.log(response);
		if(obj.error){alert(obj.error);}
		else if(obj.success){


			jQuery(theme).hide();
			jQuery(theme).prev('.button-primary').show();
			jQuery(theme).prev().prev('.external-updates-key-value').prop('disabled', true);
			jQuery(theme).parent().parent().parent().removeClass('notice-warning');
			//jQuery(theme).parent().parent().toggle('slow');

			alert(obj.success);
			location.reload();


		}else{
			alert('error');
		}
	});
}

function exup_deactivate_theme_licence_key(theme,themeName,exupNonce){
	console.log(jQuery(theme).prev('.external-updates-key-value').val());
	console.log(themeName);

	var key = jQuery(theme).prev('.external-updates-key-value').val();
	if(!key){return;}

	var data = {
		'security': exupNonce,
		'action': 'exup_ajax_handler',
		'exup_action': 'deactivate_key',
		'exup_key': key,
		'exup_theme': themeName
	};

	jQuery.post(ajaxurl, data, function(response) {
		var obj = jQuery.parseJSON(response);
		console.log(response);
		if(obj.error){
			alert(obj.error);
			jQuery(theme).prev('.external-updates-key-value').val('');
			jQuery(theme).prev('.external-updates-key-value').prop('disabled', false);
			jQuery(theme).parent().parent().parent().addClass('notice-warning');
		}
		else if(obj.success){
			//alert(obj.success);

			jQuery(theme).hide();
			jQuery(theme).next('.button-primary').show();
			jQuery(theme).prev('.external-updates-key-value').val('');
			jQuery(theme).prev('.external-updates-key-value').prop('disabled', false);
			jQuery(theme).parent().parent().parent().addClass('notice-warning');

			//jQuery(theme).parent().parent().toggle('slow');
			alert(obj.success);
			location.reload();


		}else{
			alert('error');
		}
	});
}

function exup_activate_licence_key(plugin,pluginName,exupNonce){
	console.log(jQuery(plugin).prev().prev('.external-updates-key-value').val());
	console.log(pluginName);

	var key = jQuery(plugin).prev().prev('.external-updates-key-value').val();
	if(!key){return;}

	var data = {
		'security': exupNonce,
		'action': 'exup_ajax_handler',
		'exup_action': 'activate_key',
		'exup_key': key,
		'exup_plugin': pluginName
	};

	jQuery.post(ajaxurl, data, function(response) {
		var obj = jQuery.parseJSON(response);
		console.log(response);
		if(obj.error){alert(obj.error);}
		else if(obj.success){
			alert(obj.success);
			
			jQuery(plugin).hide();
			jQuery(plugin).prev('.button-primary').show();
			jQuery(plugin).prev().prev('.external-updates-key-value').prop('disabled', true);
			jQuery(plugin).parent().parent().prev('.external-updates-licence-toggle').addClass('external-updates-active');
			jQuery(plugin).parent().parent().toggle('slow');


		}else{
			alert('error');
		}
	});
}

function exup_deactivate_licence_key(plugin,pluginName,exupNonce){
	console.log(jQuery(plugin).prev('.external-updates-key-value').val());
	console.log(pluginName);

	var key = jQuery(plugin).prev('.external-updates-key-value').val();
	if(!key){return;}

	var data = {
		'security': exupNonce,
		'action': 'exup_ajax_handler',
		'exup_action': 'deactivate_key',
		'exup_key': key,
		'exup_plugin': pluginName
	};

	jQuery.post(ajaxurl, data, function(response) {
		var obj = jQuery.parseJSON(response);
		console.log(response);
		if(obj.error){
			alert(obj.error);
			jQuery(plugin).prev('.external-updates-key-value').val('');
			jQuery(plugin).prev('.external-updates-key-value').prop('disabled', false);
			jQuery(plugin).parent().parent().prev('.external-updates-licence-toggle').removeClass('external-updates-active');
		}
		else if(obj.success){
			alert(obj.success);

			jQuery(plugin).hide();
			jQuery(plugin).next('.button-primary').show();
			jQuery(plugin).prev('.external-updates-key-value').val('');
			jQuery(plugin).prev('.external-updates-key-value').prop('disabled', false);
			jQuery(plugin).parent().parent().prev('.external-updates-licence-toggle').removeClass('external-updates-active');
			jQuery(plugin).parent().parent().toggle('slow');


		}else{
			alert('error');
		}
	});
}


