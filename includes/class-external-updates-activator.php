<?php

/**
 * Fired during plugin activation
 *
 * @link       http://ayecode.io/
 * @since      1.0.0
 *
 * @package    External_Updates
 * @subpackage External_Updates/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    External_Updates
 * @subpackage External_Updates/includes
 * @author     Stiofan O'Connor <info@ayecode.io>
 */
class External_Updates_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {


		// if GeoDirectory active then copy over licence keys to new location
		if(defined("GEODIRECTORY_VERSION")){

			$licence_keys = get_option( 'geodir_licence_keys' );
			if(!empty($licence_keys)){

				$current_keys = get_site_option( 'exup_keys', array() );
				if(empty($current_keys)){
					$plugins = get_plugins();

					foreach($plugins as $key => $plugin){

						if($key=='geodir_advance_search_filters/geodir_advance_search_filters.php' && isset($current_keys['geodiradvancesearch'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodiradvancesearch']['status'],
								'key'       =>  $current_keys['geodiradvancesearch']['license'],
							);
						}elseif($key=='geodir_affiliate/geodir_affiliate.php' && isset($current_keys['gdaffiliate'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['gdaffiliate']['status'],
								'key'       =>  $current_keys['gdaffiliate']['license'],
							);
						}elseif($key=='geodir_ajax_duplicate_alert/geodir_ajax_duplicate_alert.php' && isset($current_keys['geodir_duplicatealert'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir_duplicatealert']['status'],
								'key'       =>  $current_keys['geodir_duplicatealert']['license'],
							);
						}elseif($key=='geodir_buddypress/geodir_buddypress.php' && isset($current_keys['gdbuddypress'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['gdbuddypress']['status'],
								'key'       =>  $current_keys['gdbuddypress']['license'],
							);
						}elseif($key=='geodir_claim_listing/geodir_claim_listing.php' && isset($current_keys['geodirclaim'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodirclaim']['status'],
								'key'       =>  $current_keys['geodirclaim']['license'],
							);
						}elseif($key=='geodir_custom_google_maps/geodir_custom_google_maps.php' && isset($current_keys['geodir_customgmaps'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir_customgmaps']['status'],
								'key'       =>  $current_keys['geodir_customgmaps']['license'],
							);
						}elseif($key=='geodir_custom_posts/geodir_custom_posts.php' && isset($current_keys['geodir_custom_posts'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir_custom_posts']['status'],
								'key'       =>  $current_keys['geodir_custom_posts']['license'],
							);
						}elseif($key=='geodir_event_manager/geodir_event_manager.php' && isset($current_keys['geodirevents'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodirevents']['status'],
								'key'       =>  $current_keys['geodirevents']['license'],
							);
						}elseif($key=='geodir_franchise/geodir_franchise.php' && isset($current_keys['geodir-franchise'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir-franchise']['status'],
								'key'       =>  $current_keys['geodir-franchise']['license'],
							);
						}elseif($key=='geodir_list_manager/geodir_list_manager.php' && isset($current_keys['geodirlists'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodirlists']['status'],
								'key'       =>  $current_keys['geodirlists']['license'],
							);
						}elseif($key=='geodir_location_manager/geodir_location_manager.php' && isset($current_keys['geodirlocation'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodirlocation']['status'],
								'key'       =>  $current_keys['geodirlocation']['license'],
							);
						}elseif($key=='geodir_marker_cluster/geodir_marker_cluster.php' && isset($current_keys['geodir_markercluster'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir_markercluster']['status'],
								'key'       =>  $current_keys['geodir_markercluster']['license'],
							);
						}elseif($key=='geodir_payment_manager/geodir_payment_manager.php' && isset($current_keys['geodir_payments'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir_payments']['status'],
								'key'       =>  $current_keys['geodir_payments']['license'],
							);
						}elseif($key=='geodir_recaptcha/geodir_recaptcha.php' && isset($current_keys['geodir-recaptcha'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir-recaptcha']['status'],
								'key'       =>  $current_keys['geodir-recaptcha']['license'],
							);
						}elseif($key=='geodir_review_rating_manager/geodir_review_rating_manager.php' && isset($current_keys['geodir_reviewratings'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir_reviewratings']['status'],
								'key'       =>  $current_keys['geodir_reviewratings']['license'],
							);
						}elseif($key=='geodir_social_importer/geodir_social_importer.php' && isset($current_keys['geodir_socialimporter'])){
							$licence_keys[$key] = (object) array(
								'license'   =>  $current_keys['geodir_socialimporter']['status'],
								'key'       =>  $current_keys['geodir_socialimporter']['license'],
							);
						}

					}

					if(!empty($licence_keys)){
						update_site_option( 'exup_keys', $licence_keys  ); // update network option

						update_option( 'exup_keys', $licence_keys ); // update single site option
					}

				}

			}

		}

	}

}
