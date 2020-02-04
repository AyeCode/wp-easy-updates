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

	// if we have a download failed error then try to replace the url with the correct one
	$(function() {
		if($('.wpeu-download-failed-error').length){
			console.log('start');
			$('.wpeu-download-failed-error').each(function(i, obj) {
				console.log('it');
				var $download_url = $(this).closest( ".error" ).prev().find('.code').text();
				if($download_url){
					$(this).attr("href", $download_url);
				}
			});
		}
	});



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

function exup_install_from_licence_key(plugin,pluginName,exupNonce){
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


function exup_activate_membership_licence_key(plugin,pluginName,exupNonce,item_ids){
	console.log(jQuery(plugin).prev().prev('.external-updates-key-value').val());
	console.log(pluginName);

	var key = jQuery(plugin).prev().prev('.external-updates-key-value').val();
	if(!key){return;}

	var data = {
		'security': exupNonce,
		'action': 'exup_ajax_handler',
		'exup_action': 'activate_membership_key',
		'exup_key': key,
		'exup_domain': pluginName,
		'exup_item_ids'	: item_ids
	};

	jQuery.post(ajaxurl, data, function(response) {
		var obj = jQuery.parseJSON(response);
		console.log(response);
		if(obj.error){alert(obj.error);}
		else if(obj.success){
			alert(obj.success);
			location.reload();
			// jQuery(plugin).prev('.button-primary').show();
			// jQuery(plugin).prev().prev('.external-updates-key-value').prop('disabled', true);
			// jQuery(plugin).parent().parent().prev('.external-updates-licence-toggle').addClass('external-updates-active');


		}else{
			alert('error');
		}
	});
}


function exup_deactivate_membership_licence_key(plugin,pluginName,exupNonce,item_ids){
	console.log(jQuery(plugin).prev('.external-updates-key-value').val());
	console.log(pluginName);

	var key = jQuery(plugin).prev('.external-updates-key-value').val();
	if(!key){return;}

	var data = {
		'security': exupNonce,
		'action': 'exup_ajax_handler',
		'exup_action': 'deactivate_membership_key',
		'exup_key': key,
		'exup_domain': pluginName,
		'exup_item_ids'	: item_ids
	};

	jQuery.post(ajaxurl, data, function(response) {
		var obj = jQuery.parseJSON(response);
		console.log(response);
		if(obj.error){
			alert(obj.error);
			jQuery(plugin).hide();
			jQuery(plugin).prev('.external-updates-key-value').val('');
			jQuery(plugin).prev('.external-updates-key-value').prop('disabled', false);
			jQuery(plugin).next('.button-primary').show();
			//membership-content
		}
		else if(obj.success){
			alert(obj.success);
			location.reload();
			// jQuery(plugin).prev('.button-primary').show();
			// jQuery(plugin).prev().prev('.external-updates-key-value').prop('disabled', true);
			// jQuery(plugin).parent().parent().prev('.external-updates-licence-toggle').addClass('external-updates-active');
		}else{
			alert('error');
		}
	});
}


function wpeu_licence_popup($this,$slug,$nonce,$update_url,$item_id,$type){

	$url = jQuery($this).attr("href");
	$title = jQuery($this).data("title");
	if(!$type){$type = 'plugin';}

	jQuery('#wpeu-licence-popup .wpeu-licence-title').html($title);
	jQuery('#wpeu-licence-popup .wpeu-licence-link').attr("href",$url);
	$licenced = jQuery($this).data("licensing");
	$single_licence = jQuery($this).data("licence");

	if($licenced && !$single_licence){
		jQuery('#wpeu-licence-popup .wpeu-licence-title').html(''); // not needed with thickbox
		tb_show($title, "#TB_inline?&width=300&height=80&inlineId=wpeu-licence-popup");
		
		jQuery(".wpeu-licence-popup-button").unbind('click').click(function(){
			$licence =  jQuery(".wpeu-licence-key").val();
			if($licenced && $licence==''){
				alert("Please enter a key");
			}else{
				jQuery(".wpeu-licence-key").val('');
				tb_remove();
				if($type=='plugin'){
					wpeu_install_plugin($this,$slug,$nonce,$update_url,$item_id,$licence);
				}else if($type=='theme'){
					wpeu_install_theme($this,$slug,$nonce,$update_url,$item_id,$licence);
				}
			}
		});
	}

}

function wpeu_install_plugin($this,$slug,$nonce,$update_url,$item_id,$licence){

	var data = {
		'action':           'install-plugin',
		'_ajax_nonce':       $nonce,
		'slug':              $slug,
		'item_id':           $item_id
	};


	if($update_url){
		data.update_url = $update_url;
	}

	if($licence && $licence!='free'){
		data.license = $licence;
		data.wpeu_activate = 1; // activate the licence first or it won't allow download from the url.
	}else if($licence=='free'){
		data.free_download = '1'; // requires EDD free downloads to work
	}

	// console.log(data);return;

	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data, // serializes the form's elements.
		beforeSend: function()
		{
			jQuery($this).html('<i class="fas fa-sync fa-spin" ></i> ' + jQuery($this).data("text-installing")).attr("disabled", true);
		},
		success: function(data)
		{
			console.log(data);
			if(data.success){
				if(data.data.activateUrl){
					jQuery($this).html(jQuery($this).data("text-activate")).removeAttr('target').attr('onclick','wpeu_set_button_activating(this);').attr('href',data.data.activateUrl).attr("disabled", false);
				}else{
					jQuery($this).html(jQuery($this).data("text-installed")).removeClass('button-primary').addClass('button-secondary');
				}
			}else{
				jQuery($this).html(jQuery($this).data("text-error"));
				var error_msg = jQuery($this).data("text-error-message");
				if(data.data.errorMessage){
					error_msg += " : " + data.data.errorMessage;
				}
				alert( error_msg );

			}
		}
	});
}

function wpeu_install_theme($this,$slug,$nonce,$update_url,$item_id,$licence){

	var data = {
		'action':           'install-theme',
		'_ajax_nonce':       $nonce,
		'slug':              $slug,
		'item_id':           $item_id
	};


	if($update_url){
		data.update_url = $update_url;
	}

	if($licence && $licence!='free'){
		data.license = $licence;
		data.wpeu_activate = 1; // activate the licence first or it won't allow download from the url.
	}else if($licence=='free'){
		data.free_download = '1'; // requires EDD free downloads to work
	}

	// console.log(data);return;

	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data, // serializes the form's elements.
		beforeSend: function()
		{
			jQuery($this).html('<i class="fas fa-sync fa-spin" ></i> ' + jQuery($this).data("text-installing")).attr("disabled", true);
		},
		success: function(data)
		{
			console.log(data);
			if(data.success){
				if(data.data.activateUrl){
					jQuery($this).html(jQuery($this).data("text-activate")).removeAttr('target').attr('onclick','wpeu_set_button_activating(this);').attr('href',data.data.activateUrl).attr("disabled", false);
				}else{
					jQuery($this).html(jQuery($this).data("text-installed")).removeClass('button-primary').addClass('button-secondary');
				}
			}else{
				jQuery($this).html(jQuery($this).data("text-error"));
				var error_msg = jQuery($this).data("text-error-message");
				if(data.data.errorMessage){
					error_msg += " : " + data.data.errorMessage;
				}
				alert( error_msg );

			}
		}
	});
}

function wpeu_set_button_activating($this){
	jQuery($this).html('<i class="fas fa-sync fa-spin" ></i> ' + jQuery($this).data("text-activating")).attr("disabled", true);
}