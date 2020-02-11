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

						if($key=='geodir_advance_search_filters/geodir_advance_search_filters.php' && isset($licence_keys['geodiradvancesearch'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodiradvancesearch']['status'],
								'key'       =>  $licence_keys['geodiradvancesearch']['licence'],
							);
						}elseif($key=='geodir_affiliate/geodir_affiliate.php' && isset($licence_keys['gdaffiliate'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['gdaffiliate']['status'],
								'key'       =>  $licence_keys['gdaffiliate']['licence'],
							);
						}elseif($key=='geodir_ajax_duplicate_alert/geodir_ajax_duplicate_alert.php' && isset($licence_keys['geodir_duplicatealert'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir_duplicatealert']['status'],
								'key'       =>  $licence_keys['geodir_duplicatealert']['licence'],
							);
						}elseif($key=='geodir_buddypress/geodir_buddypress.php' && isset($licence_keys['gdbuddypress'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['gdbuddypress']['status'],
								'key'       =>  $licence_keys['gdbuddypress']['licence'],
							);
						}elseif($key=='geodir_claim_listing/geodir_claim_listing.php' && isset($licence_keys['geodirclaim'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodirclaim']['status'],
								'key'       =>  $licence_keys['geodirclaim']['licence'],
							);
						}elseif($key=='geodir_custom_google_maps/geodir_custom_google_maps.php' && isset($licence_keys['geodir_customgmaps'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir_customgmaps']['status'],
								'key'       =>  $licence_keys['geodir_customgmaps']['licence'],
							);
						}elseif($key=='geodir_custom_posts/geodir_custom_posts.php' && isset($licence_keys['geodir_custom_posts'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir_custom_posts']['status'],
								'key'       =>  $licence_keys['geodir_custom_posts']['licence'],
							);
						}elseif($key=='geodir_event_manager/geodir_event_manager.php' && isset($licence_keys['geodirevents'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodirevents']['status'],
								'key'       =>  $licence_keys['geodirevents']['licence'],
							);
						}elseif($key=='geodir_franchise/geodir_franchise.php' && isset($licence_keys['geodir-franchise'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir-franchise']['status'],
								'key'       =>  $licence_keys['geodir-franchise']['licence'],
							);
						}elseif($key=='geodir_list_manager/geodir_list_manager.php' && isset($licence_keys['geodirlists'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodirlists']['status'],
								'key'       =>  $licence_keys['geodirlists']['licence'],
							);
						}elseif($key=='geodir_location_manager/geodir_location_manager.php' && isset($licence_keys['geodirlocation'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodirlocation']['status'],
								'key'       =>  $licence_keys['geodirlocation']['licence'],
							);
						}elseif($key=='geodir_marker_cluster/geodir_marker_cluster.php' && isset($licence_keys['geodir_markercluster'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir_markercluster']['status'],
								'key'       =>  $licence_keys['geodir_markercluster']['licence'],
							);
						}elseif($key=='geodir_payment_manager/geodir_payment_manager.php' && isset($licence_keys['geodir_payments'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir_payments']['status'],
								'key'       =>  $licence_keys['geodir_payments']['licence'],
							);
						}elseif($key=='geodir_recaptcha/geodir_recaptcha.php' && isset($licence_keys['geodir-recaptcha'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir-recaptcha']['status'],
								'key'       =>  $licence_keys['geodir-recaptcha']['licence'],
							);
						}elseif($key=='geodir_review_rating_manager/geodir_review_rating_manager.php' && isset($licence_keys['geodir_reviewratings'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir_reviewratings']['status'],
								'key'       =>  $licence_keys['geodir_reviewratings']['licence'],
							);
						}elseif($key=='geodir_social_importer/geodir_social_importer.php' && isset($licence_keys['geodir_socialimporter'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir_socialimporter']['status'],
								'key'       =>  $licence_keys['geodir_socialimporter']['licence'],
							);
						}elseif($key=='geodir_gd_booster/geodir_gd_booster.php' && isset($licence_keys['geodir-gd-booster'])){
							$current_keys[$key] = (object) array(
								'license'   =>  $licence_keys['geodir-gd-booster']['status'],
								'key'       =>  $licence_keys['geodir-gd-booster']['licence'],
							);
						}

					}

					if(!empty($current_keys)){
						update_site_option( 'exup_keys', $current_keys  ); // update network option

						update_option( 'exup_keys', $current_keys ); // update single site option
					}

				}

				delete_option("geodir_licence_keys");
				update_option( 'geodir_licence_keys_backup', $licence_keys);

			}

		}

	}

}
