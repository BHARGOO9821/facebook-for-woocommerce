<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

use WooCommerce\Facebook\Admin;
use WooCommerce\Facebook\Events\AAMSettings;
use WooCommerce\Facebook\Framework\Api\Exception as ApiException;
use WooCommerce\Facebook\Framework\Helper;
use WooCommerce\Facebook\Framework\Plugin\Exception as PluginException;
use WooCommerce\Facebook\Products;
use WooCommerce\Facebook\Products\Feed;

defined( 'ABSPATH' ) || exit;

require_once 'facebook-config-warmer.php';
require_once 'includes/fbproduct.php';
require_once 'facebook-commerce-pixel-event.php';
require_once 'facebook-commerce-admin-notice.php';

/**
 * Class WC_Facebookcommerce_Integration
 *
 * This class is the main integration class for Facebook for WooCommerce.
 */
class WC_Facebookcommerce_Integration extends WC_Integration {

	/**
	 * The WordPress option name where the page access token is stored.
	 *
	 * @var string option name.
	 * @deprecated 2.1.0
	 */
	const OPTION_PAGE_ACCESS_TOKEN = 'wc_facebook_page_access_token';

	/** @var string the WordPress option name where the product catalog ID is stored */
	const OPTION_PRODUCT_CATALOG_ID = 'wc_facebook_product_catalog_id';

	/** @var string the WordPress option name where the external merchant settings ID is stored */
	const OPTION_EXTERNAL_MERCHANT_SETTINGS_ID = 'wc_facebook_external_merchant_settings_id';

	/** @var string Option name for disabling feed. */
	const OPTION_LEGACY_FEED_FILE_GENERATION_ENABLED = 'wc_facebook_legacy_feed_file_generation_enabled';

	/** @var string the WordPress option name where the feed ID is stored */
	const OPTION_FEED_ID = 'wc_facebook_feed_id';

	/** @var string the WordPress option name where the upload ID is stored */
	const OPTION_UPLOAD_ID = 'wc_facebook_upload_id';

	/** @var string the WordPress option name where the JS SDK version is stored */
	const OPTION_JS_SDK_VERSION = 'wc_facebook_js_sdk_version';

	/** @var string the WordPress option name where the latest pixel install time is stored */
	const OPTION_PIXEL_INSTALL_TIME = 'wc_facebook_pixel_install_time';

	/** @var string the facebook page ID setting ID */
	const SETTING_FACEBOOK_PAGE_ID = 'wc_facebook_page_id';

	/** @var string the facebook pixel ID setting ID */
	const SETTING_FACEBOOK_PIXEL_ID = 'wc_facebook_pixel_id';

	/** @var string the "enable advanced matching" setting ID */
	const SETTING_ENABLE_ADVANCED_MATCHING = 'enable_advanced_matching';

	/** @var string the "use s2s" setting ID */
	const SETTING_USE_S2S = 'use_s2s';

	/** @var string the "access token" setting ID */
	const SETTING_ACCESS_TOKEN = 'access_token';

	/** @var string the "enable product sync" setting ID */
	const SETTING_ENABLE_PRODUCT_SYNC = 'wc_facebook_enable_product_sync';

	/** @var string the excluded product category IDs setting ID */
	const SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS = 'wc_facebook_excluded_product_category_ids';

	/** @var string the excluded product tag IDs setting ID */
	const SETTING_EXCLUDED_PRODUCT_TAG_IDS = 'wc_facebook_excluded_product_tag_ids';

	/** @var string the product description mode setting ID */
	const SETTING_PRODUCT_DESCRIPTION_MODE = 'wc_facebook_product_description_mode';

	/** @var string the scheduled resync offset setting ID */
	const SETTING_SCHEDULED_RESYNC_OFFSET = 'scheduled_resync_offset';

	/** @var string the "meta diagnosis" setting ID */
	const SETTING_ENABLE_META_DIAGNOSIS = 'wc_facebook_enable_meta_diagnosis';

	/** @var string the "debug mode" setting ID */
	const SETTING_ENABLE_DEBUG_MODE = 'wc_facebook_enable_debug_mode';

	/** @var string the "debug mode" setting ID */
	const SETTING_ENABLE_NEW_STYLE_FEED_GENERATOR = 'wc_facebook_enable_new_style_feed_generator';

	/** @var string request headers in the debug log */
	const SETTING_REQUEST_HEADERS_IN_DEBUG_MODE = 'wc_facebook_request_headers_in_debug_log';

	/** @var string custom taxonomy Facebook Product Set ID */
	const FB_PRODUCT_SET_ID = 'fb_product_set_id';

	/** @var string the WordPress option name where the access token is stored */
	const OPTION_ACCESS_TOKEN = 'wc_facebook_access_token';

	/** @var string the WordPress option name where the merchant access token is stored */
	const OPTION_MERCHANT_ACCESS_TOKEN = 'wc_facebook_merchant_access_token';

	/** @var string the WordPress option name where the business manager ID is stored */
	const OPTION_BUSINESS_MANAGER_ID = 'wc_facebook_business_manager_id';

	/** @var string the WordPress option name where the ad account ID is stored */
	const OPTION_AD_ACCOUNT_ID = 'wc_facebook_ad_account_id';

	/** @var string the WordPress option name where the system user ID is stored */
	const OPTION_SYSTEM_USER_ID = 'wc_facebook_system_user_id';

	/** @var string the WordPress option name where the commerce merchant settings ID is stored */
	const OPTION_COMMERCE_MERCHANT_SETTINGS_ID = 'wc_facebook_commerce_merchant_settings_id';

	/** @var string the WordPress option name where the commerce partner integration ID is stored */
	const OPTION_COMMERCE_PARTNER_INTEGRATION_ID = 'wc_facebook_commerce_partner_integration_id';

	/** @var string the WordPress option name where the profiles are stored */
	const OPTION_PROFILES = 'wc_facebook_profiles';

	/** @var string the WordPress option name where the installed features are stored */
	const OPTION_INSTALLED_FEATURES = 'wc_facebook_installed_features';

	/** @var string the WordPress option name where the FBE 2 connection status is stored */
	const OPTION_HAS_CONNECTED_FBE_2 = 'wc_facebook_has_connected_fbe_2';

	/** @var string the WordPress option name where the pages read engagement authorization status is stored */
	const OPTION_HAS_AUTHORIZED_PAGES_READ_ENGAGEMENT = 'wc_facebook_has_authorized_pages_read_engagement';

	/** @var string the WordPress option name where the messenger chat status is stored */
	const OPTION_ENABLE_MESSENGER = 'wc_facebook_enable_messenger';

	/** @var string|null the configured product catalog ID */
	public $product_catalog_id;

	/** @var string|null the configured external merchant settings ID */
	public $external_merchant_settings_id;

	/** @var string|null the configured feed ID */
	public $feed_id;

	/** @var string|null the configured upload ID */
	private $upload_id;

	/** @var string|null the configured pixel install time */
	public $pixel_install_time;

	/** @var string|null the configured JS SDK version */
	private $js_sdk_version;

	/** @var bool|null whether the feed has been migrated from FBE 1 to FBE 1.5 */
	private $feed_migrated;

	/** @var array the page name and url */
	private $page;

	/** Legacy properties *********************************************************************************************/


	// TODO probably some of these meta keys need to be moved to Facebook\Products {FN 2020-01-13}.
	public const FB_PRODUCT_GROUP_ID      = 'fb_product_group_id';
	public const FB_PRODUCT_ITEM_ID       = 'fb_product_item_id';
	public const FB_PRODUCT_DESCRIPTION   = 'fb_product_description';
	public const FB_RICH_TEXT_DESCRIPTION = 'fb_rich_text_description';
	/** @var string the API flag to set a product as visible in the Facebook shop */
	public const FB_SHOP_PRODUCT_VISIBLE = 'published';

	/** @var string the API flag to set a product as not visible in the Facebook shop */
	public const FB_SHOP_PRODUCT_HIDDEN = 'hidden';

	/** @var string @deprecated */
	public const FB_CART_URL = 'fb_cart_url';

	public const FB_MESSAGE_DISPLAY_TIME = 180;

	// Number of days to query tip.
	public const FB_TIP_QUERY = 1;

	// TODO: this constant is no longer used and can probably be removed {WV 2020-01-21}.
	public const FB_VARIANT_IMAGE = 'fb_image';

	public const FB_ADMIN_MESSAGE_PREPEND = '<b>Facebook for WooCommerce</b><br/>';

	public const FB_SYNC_IN_PROGRESS = 'fb_sync_in_progress';
	public const FB_SYNC_REMAINING   = 'fb_sync_remaining';
	public const FB_SYNC_TIMEOUT     = 30;
	public const FB_PRIORITY_MID     = 9;

	/**
	 * Facebook exception test mode switch.
	 *
	 * @var bool
	 */
	private $test_mode = false;

	/** @var WC_Facebookcommerce */
	private $facebook_for_woocommerce;

	/** @var WC_Facebookcommerce_EventsTracker instance. */
	private $events_tracker;

	/** @var WC_Facebookcommerce_Background_Process instance. */
	private $background_processor;

	/** @var WC_Facebook_Product_Feed instance. */
	private $fbproductfeed;

	/** @var WC_Facebookcommerce_Whatsapp_Utility_Event instance. */
	private $wa_utility_event_processor;

	/**
	 * Init and hook in the integration.
	 *
	 * @param WC_Facebookcommerce $facebook_for_woocommerce
	 *
	 * @return void
	 */
	public function __construct( WC_Facebookcommerce $facebook_for_woocommerce ) {
		$this->facebook_for_woocommerce = $facebook_for_woocommerce;

		if ( ! class_exists( 'WC_Facebookcommerce_EventsTracker' ) ) {
			include_once 'facebook-commerce-events-tracker.php';
		}

		$this->id                 = WC_Facebookcommerce::INTEGRATION_ID;
		$this->method_title       = __(
			'Facebook for WooCommerce',
			'facebook-for-woocommerce'
		);
		$this->method_description = __(
			'Facebook Commerce and Dynamic Ads (Pixel) Extension',
			'facebook-for-woocommerce'
		);

		// Load the settings.
		$this->init_settings();

		$pixel_id = WC_Facebookcommerce_Pixel::get_pixel_id();

		// If there is a pixel option saved and no integration setting saved, inherit the pixel option.
		if ( $pixel_id && ! $this->get_facebook_pixel_id() ) {
			$this->settings[ self::SETTING_FACEBOOK_PIXEL_ID ] = $pixel_id;
		}

		$advanced_matching_enabled = WC_Facebookcommerce_Pixel::get_use_pii_key();

		// If Advanced Matching (use_pii) is enabled on the saved pixel option and not on the saved integration setting, inherit the pixel option.
		if ( $advanced_matching_enabled && ! $this->is_advanced_matching_enabled() ) {
			$this->settings[ self::SETTING_ENABLE_ADVANCED_MATCHING ] = $advanced_matching_enabled;
		}

		// For now, the values of use s2s and access token will be the ones returned from WC_Facebookcommerce_Pixel.
		$this->settings[ self::SETTING_USE_S2S ]      = WC_Facebookcommerce_Pixel::get_use_s2s();
		$this->settings[ self::SETTING_ACCESS_TOKEN ] = WC_Facebookcommerce_Pixel::get_access_token();

		WC_Facebookcommerce_Utils::$ems = $this->get_external_merchant_settings_id();

		// Set meta diagnosis to yes by default
		if ( ! get_option( self::SETTING_ENABLE_META_DIAGNOSIS ) ) {
			update_option( self::SETTING_ENABLE_META_DIAGNOSIS, 'yes' );
		}

		if ( is_admin() ) {

			$this->init_pixel();

			if ( ! class_exists( 'WC_Facebookcommerce_EventsTracker' ) ) {
				include_once 'includes/fbutils.php';
			}

			// Display an info banner for eligible pixel and user.
			if ( $this->get_external_merchant_settings_id()
				&& $this->get_facebook_pixel_id()
				&& $this->get_pixel_install_time() ) {
				$should_query_tip =
					WC_Facebookcommerce_Utils::check_time_cap(
						get_option( 'fb_info_banner_last_query_time', '' ),
						self::FB_TIP_QUERY
					);
				$last_tip_info    = WC_Facebookcommerce_Utils::get_cached_best_tip();

				if ( $should_query_tip || $last_tip_info ) {
					if ( ! class_exists( 'WC_Facebookcommerce_Info_Banner' ) ) {
						include_once 'includes/fbinfobanner.php';
					}
					WC_Facebookcommerce_Info_Banner::get_instance( $this->get_external_merchant_settings_id(), $should_query_tip );
				}
			}

			if ( ! $this->get_pixel_install_time() && $this->get_facebook_pixel_id() ) {
				$this->update_pixel_install_time( time() );
			}

			add_action( 'admin_notices', [ $this, 'checks' ] );

			add_action( 'admin_enqueue_scripts', [ $this, 'load_assets' ] );

			add_action(
				'wp_ajax_ajax_sync_all_fb_products',
				[ $this, 'ajax_sync_all_fb_products' ],
				self::FB_PRIORITY_MID
			);

			add_action(
				'wp_ajax_ajax_check_feed_upload_status',
				[ $this, 'ajax_check_feed_upload_status' ],
				self::FB_PRIORITY_MID
			);

			add_action(
				'wp_ajax_ajax_reset_all_fb_products',
				[ $this, 'ajax_reset_all_fb_products' ],
				self::FB_PRIORITY_MID
			);
			add_action(
				'wp_ajax_ajax_display_test_result',
				[ $this, 'ajax_display_test_result' ]
			);

			// Don't duplicate product FBID meta.
			add_filter( 'woocommerce_duplicate_product_exclude_meta', [ $this, 'fb_duplicate_product_reset_meta' ] );

			// Add product processing hooks if the plugin is configured only.
			if ( $this->is_configured() && $this->get_product_catalog_id() ) {

				// On_product_save() must run with priority larger than 20 to make sure WooCommerce has a chance to save the submitted product information.
				add_action( 'woocommerce_process_product_meta', [ $this, 'on_product_save' ], 40 );

				add_action(
					'woocommerce_product_quick_edit_save',
					[ $this, 'on_quick_and_bulk_edit_save' ]
				);

				add_action(
					'woocommerce_product_bulk_edit_save',
					[ $this, 'on_quick_and_bulk_edit_save' ]
				);

				add_action( 'add_meta_boxes_product', [ $this, 'display_batch_api_completed' ], 10, 2 );

				add_action(
					'wp_ajax_ajax_fb_toggle_visibility',
					array( $this, 'ajax_fb_toggle_visibility' )
				);

				add_action(
					'pmxi_after_xml_import',
					array( $this, 'wp_all_import_compat' )
				);

				add_action(
					'wp_ajax_wpmelon_adv_bulk_edit',
					array( $this, 'ajax_woo_adv_bulk_edit_compat' ),
					self::FB_PRIORITY_MID
				);

				// Used to remove the 'you need to resync' message.
				if ( isset( $_GET['remove_sticky'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$this->remove_sticky_message();
				}
			}
			$this->load_background_sync_process();
		}

		if ( $this->get_facebook_pixel_id() ) {
			$aam_settings         = $this->load_aam_settings_of_pixel();
			$user_info            = WC_Facebookcommerce_Utils::get_user_info( $aam_settings );
			$this->events_tracker = new WC_Facebookcommerce_EventsTracker( $user_info, $aam_settings );
		}

		// Update products on change of status.
		add_action(
			'transition_post_status',
			array( $this, 'fb_change_product_published_status' ),
			10,
			3
		);

		add_action( 'before_delete_post', [ $this, 'on_product_delete' ] );

		// Ensure product is deleted from FB when moved to trash.
		add_action( 'wp_trash_post', [ $this, 'on_product_delete' ] );

		add_action( 'untrashed_post', [ $this, 'fb_restore_untrashed_variable_product' ] );

		// Ensure product is deleted from FB when status is changed to draft.
		add_action( 'publish_to_draft', [ $this, 'delete_draft_product' ] );

		// Product Set hooks.
		add_action( 'fb_wc_product_set_sync', [ $this, 'create_or_update_product_set_item' ], 99, 2 );
		add_action( 'fb_wc_product_set_delete', [ $this, 'delete_product_set_item' ], 99 );

		// Init Whatsapp Utility Event Processor
		$this->wa_utility_event_processor = $this->load_whatsapp_utility_event_processor();
	}

	/**
	 * __get method for backward compatibility.
	 *
	 * @param string $key property name
	 *
	 * @return mixed
	 * @since 3.0.32
	 */
	public function __get( $key ) {
		// Add warning for private properties.
		if ( in_array( $key, array( 'events_tracker', 'background_processor' ), true ) ) {
			/* translators: %s property name. */
			_doing_it_wrong( __FUNCTION__, sprintf( esc_html__( 'The %s property is private and should not be accessed outside its class.', 'facebook-for-woocommerce' ), esc_html( $key ) ), '3.0.32' );

			return $this->$key;
		}

		return null;
	}

	/**
	 * Initialises Facebook Pixel and its settings.
	 *
	 * @return bool
	 */
	public function init_pixel() {
		/* Not sure this one is needed. Config warmer is never written. */
		WC_Facebookcommerce_Pixel::initialize();

		/**
		 * Migrate WC customer pixel_id from WC settings to WP options.
		 * This is part of a larger effort to consolidate all the FB-specific
		 * settings for all plugin integrations.
		 */
		if ( is_admin() ) {
			$pixel_id          = WC_Facebookcommerce_Pixel::get_pixel_id();
			$settings_pixel_id = $this->get_facebook_pixel_id();
			if (
				WC_Facebookcommerce_Utils::is_valid_id( $settings_pixel_id ) &&
				( ! WC_Facebookcommerce_Utils::is_valid_id( $pixel_id ) ||
					$pixel_id !== $settings_pixel_id
				)
			) {
				WC_Facebookcommerce_Pixel::set_pixel_id( $settings_pixel_id );
			}
			/**
			 * Migrate Advanced Matching enabled (use_pii) from the integration setting to the pixel option,
			 * so that it works the same way the pixel ID does
			 */
			$settings_advanced_matching_enabled = $this->is_advanced_matching_enabled();
			WC_Facebookcommerce_Pixel::set_use_pii_key( $settings_advanced_matching_enabled );

			$settings_use_s2s = WC_Facebookcommerce_Pixel::get_use_s2s();
			WC_Facebookcommerce_Pixel::set_use_s2s( $settings_use_s2s );

			$settings_access_token = WC_Facebookcommerce_Pixel::get_access_token();
			WC_Facebookcommerce_Pixel::set_access_token( $settings_access_token );

			return true;
		}

		return false;
	}

	/**
	 * Returns the Automatic advanced matching of this pixel
	 *
	 * @return AAMSettings
	 * @since 2.0.3
	 */
	private function load_aam_settings_of_pixel() {
		$installed_pixel = $this->get_facebook_pixel_id();
		// If no pixel is installed, reading the DB is not needed.
		if ( ! $installed_pixel ) {
			return null;
		}
		$config_key       = 'wc_facebook_aam_settings';
		$saved_value      = get_transient( $config_key );
		$refresh_interval = 20 * MINUTE_IN_SECONDS;
		$aam_settings     = null;
		// If wc_facebook_aam_settings is present in the DB it is converted into an AAMSettings object.
		if ( false !== $saved_value ) {
			$cached_aam_settings = new AAMSettings( json_decode( $saved_value, true ) );
			// This condition is added because
			// it is possible that the AAMSettings saved do not belong to the current
			// installed pixel
			// because the admin could have changed the connection to Facebook
			// during the refresh interval.
			if ( $cached_aam_settings->get_pixel_id() === $installed_pixel ) {
				$aam_settings = $cached_aam_settings;
			}
		}
		// If the settings are not present or invalid
		// they are fetched from Facebook domain
		// and cached in WP database if they are not null.
		if ( ! $aam_settings ) {
			$aam_settings = AAMSettings::build_from_pixel_id( $installed_pixel );
			if ( $aam_settings ) {
				set_transient( $config_key, strval( $aam_settings ), $refresh_interval );
			}
		}

		return $aam_settings;
	}

	/**
	 * Init background process.
	 *
	 * @return void
	 */
	public function load_background_sync_process() {
		// Attempt to load background processing (Woo 3.x.x only).
		include_once 'includes/fbbackground.php';
		if ( class_exists( 'WC_Facebookcommerce_Background_Process' ) ) {
			if ( ! isset( $this->background_processor ) ) {
				$this->background_processor = new WC_Facebookcommerce_Background_Process( $this );
			}
		}
		add_action(
			'wp_ajax_ajax_fb_background_check_queue',
			[ $this, 'ajax_fb_background_check_queue' ]
		);
	}

	/**
	 * Ajax background check handler.
	 *
	 * @return void
	 */
	public function ajax_fb_background_check_queue() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'background check queue', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		$request_time = null;
		if ( isset( $_POST['request_time'] ) ) {
			$request_time = esc_js( sanitize_text_field( wp_unslash( $_POST['request_time'] ) ) );
		}
		if ( $this->facebook_for_woocommerce->get_connection_handler()->get_access_token() ) {
			if ( isset( $this->background_processor ) ) {
				$is_processing = $this->background_processor->handle_cron_healthcheck();
				$remaining     = $this->background_processor->get_item_count();
				$response      = [
					'connected'    => true,
					'background'   => true,
					'processing'   => $is_processing,
					'remaining'    => $remaining,
					'request_time' => $request_time,
				];
			} else {
				$response = [
					'connected'  => true,
					'background' => false,
				];
			}
		} else {
			$response = [
				'connected'  => false,
				'background' => false,
			];
		}
		printf( json_encode( $response ) );
		wp_die();
	}


	/**
	 * Gets a list of Product Item IDs indexed by the ID of the variation.
	 *
	 * @param WC_Facebook_Product|WC_Product $product product
	 * @param string                         $product_group_id product group ID
	 *
	 * @return array
	 * @since 2.0.0
	 */
	public function get_variation_product_item_ids( $product, $product_group_id ) {
		$product_item_ids_by_variation_id = [];
		$missing_product_item_ids         = [];

		// get the product item IDs from meta data and build a list of variations that don't have a product item ID stored
		foreach ( $product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( $variation ) {
				$product_item_id = $variation->get_meta( self::FB_PRODUCT_ITEM_ID );
				if ( $product_item_id ) {
					$product_item_ids_by_variation_id[ $variation_id ] = $product_item_id;
				} else {
					$retailer_id                                       = WC_Facebookcommerce_Utils::get_fb_retailer_id( $variation );
					$missing_product_item_ids[ $retailer_id ]          = $variation;
					$product_item_ids_by_variation_id[ $variation_id ] = null;
				}
			}
		}
		// use the Graph API to try to find and store the product item IDs for variations that don't have a value yet
		if ( $missing_product_item_ids ) {
			$product_item_ids = $this->find_variation_product_item_ids( $product_group_id );
			foreach ( $missing_product_item_ids as $retailer_id => $variation ) {
				if ( isset( $product_item_ids[ $retailer_id ] ) ) {
					$variation->update_meta_data( self::FB_PRODUCT_ITEM_ID, $product_item_ids[ $retailer_id ] );
					$variation->save_meta_data();
					$product_item_ids_by_variation_id[ $variation->get_id() ] = $product_item_ids[ $retailer_id ];
				}
			}
		}

		return $product_item_ids_by_variation_id;
	}


	/**
	 * Uses the Graph API to return a list of Product Item IDs indexed by the variation's retailer ID.
	 *  Returns a map of pairs
	 *  e.g.
	 *  (
	 *      `woo-vneck-tee-blue_28` -> `7344216055651160`,
	 *      `woo-vneck-tee-red_26`  -> `5102436146508829`
	 *  )
	 *
	 * @param string $product_group_id product group ID
	 *
	 * @return array a map of ( `retailer id` -> `id` ) pairs.
	 */
	private function find_variation_product_item_ids( string $product_group_id ): array {
		$product_item_ids = [];
		try {
			$response = $this->facebook_for_woocommerce->get_api()->get_product_group_products( $product_group_id );
			do {
				$product_item_ids = array_merge( $product_item_ids, $response->get_ids() );
				// get up to two additional pages of results
			} while ( $response = $this->facebook_for_woocommerce->get_api()->next( $response, 2 ) );
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to find the IDs for Product Items in the Product Group %s: %s', $product_group_id, $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}

		return $product_item_ids;
	}

	/**
	 * Gets the total number of published products.
	 *
	 * @return int
	 */
	public function get_product_count() {
		$product_counts = wp_count_posts( 'product' );

		return $product_counts->publish;
	}

	/**
	 * Should full batch-API sync be allowed?
	 *
	 * May be used to disable various full sync UI/APIs to avoid performance impact.
	 *
	 * @return boolean True if full batch sync is safe.
	 * @since 2.6.1
	 */
	public function allow_full_batch_api_sync() {
		/**
		 * Block the full batch API sync.
		 *
		 * @param bool $block_sync Should the full batch API sync be blocked?
		 *
		 * @return boolean True if full batch sync should be blocked.
		 * @since 2.6.10
		 */
		$block_sync = apply_filters(
			'facebook_for_woocommerce_block_full_batch_api_sync',
			false
		);

		if ( $block_sync ) {
			return false;
		}

		$default_allow_sync = true;
		// If 'facebook_for_woocommerce_allow_full_batch_api_sync' is not used, prevent get_product_count from firing.
		if ( ! has_filter( 'facebook_for_woocommerce_allow_full_batch_api_sync' ) ) {
			return $default_allow_sync;
		}

		/**
		 * Allow full batch api sync to be enabled or disabled.
		 *
		 * @param bool $allow Default value - is full batch sync allowed?
		 * @param int $product_count Number of products in store.
		 *
		 * @return boolean True if full batch sync is safe.
		 *
		 * @since 2.6.1
		 * @deprecated deprecated since version 2.6.10
		 */
		return apply_filters_deprecated(
			'facebook_for_woocommerce_allow_full_batch_api_sync',
			[
				$default_allow_sync,
				$this->get_product_count(),
			],
			'2.6.10',
			'facebook_for_woocommerce_block_full_batch_api_sync'
		);
	}

	/**
	 * Load DIA specific JS Data
	 */
	public function load_assets() {
		$ajax_data = [
			'nonce' => wp_create_nonce( 'wc_facebook_infobanner_jsx' ),
		];
		// load banner assets
		wp_enqueue_script(
			'wc_facebook_infobanner_jsx',
			$this->facebook_for_woocommerce->get_asset_build_dir_url() . '/admin/infobanner.js',
			[],
			\WC_Facebookcommerce::PLUGIN_VERSION
		);
		wp_localize_script( 'wc_facebook_infobanner_jsx', 'wc_facebook_infobanner_jsx', $ajax_data );
		wp_enqueue_style(
			'wc_facebook_infobanner_css',
			plugins_url(
				'/assets/css/facebook-infobanner.css',
				__FILE__
			),
			[],
			\WC_Facebookcommerce::PLUGIN_VERSION
		);

		if ( ! $this->facebook_for_woocommerce->is_plugin_settings() ) {
			return;
		}

		?>
		<script>
			window.facebookAdsToolboxConfig = {
				hasGzipSupport: '<?php echo extension_loaded( 'zlib' ) ? 'true' : 'false'; ?>',
				enabledPlugins: ['INSTAGRAM_SHOP', 'PAGE_SHOP'],
				enableSubscription: '<?php echo class_exists( 'WC_Subscriptions' ) ? 'true' : 'false'; ?>',
				popupOrigin: '<?php echo isset( $_GET['url'] ) ? esc_js( sanitize_text_field( wp_unslash( $_GET['url'] ) ) ) : 'https://www.facebook.com/'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>',
				feedWasDisabled: 'true',
				platform: 'WooCommerce',
				pixel: {
					pixelId: '<?php echo $this->get_facebook_pixel_id() ? esc_js( $this->get_facebook_pixel_id() ) : ''; ?>',
					advanced_matching_supported: true
				},
				diaSettingId: '<?php echo $this->get_external_merchant_settings_id() ? esc_js( $this->get_external_merchant_settings_id() ) : ''; ?>',
				store: {
					baseUrl: window.location.protocol + '//' + window.location.host,
					baseCurrency: '<?php echo esc_js( WC_Admin_Settings::get_option( 'woocommerce_currency' ) ); ?>',
					timezoneId: '<?php echo esc_js( date( 'Z' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date ?>',
					storeName: '<?php echo esc_js( WC_Facebookcommerce_Utils::get_store_name() ); ?>',
					version: '<?php echo esc_js( WC()->version ); ?>',
					php_version: '<?php echo PHP_VERSION; ?>',
					plugin_version: '<?php echo esc_js( WC_Facebookcommerce_Utils::PLUGIN_VERSION ); ?>'
				},
				feed: {
					totalVisibleProducts: '<?php echo esc_js( $this->get_product_count() ); ?>',
					hasClientSideFeedUpload: '<?php echo esc_js( (bool) $this->get_feed_id() ); ?>',
					enabled: true,
					format: 'csv'
				},
				feedPrepared: {
					feedUrl: '<?php echo esc_url_raw( Feed::get_feed_data_url() ); ?>',
					feedPingUrl: '',
					feedMigrated: <?php echo $this->is_feed_migrated() ? 'true' : 'false'; ?>,
					samples: <?php echo $this->get_sample_product_feed(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				},
			};
		</script>
		<?php
		$ajax_data = [
			'nonce' => wp_create_nonce( 'wc_facebook_settings_jsx' ),
		];
		wp_localize_script(
			'wc_facebook_settings_jsx',
			'wc_facebook_settings_jsx',
			$ajax_data
		);
		wp_enqueue_style(
			'wc_facebook_css',
			plugins_url(
				'/assets/css/facebook.css',
				__FILE__
			),
			[],
			\WC_Facebookcommerce::PLUGIN_VERSION
		);
	}

	/**
	 * Gets the IDs of products marked for deletion from Facebook when removed from Sync.
	 *
	 * @return array
	 * @since 2.3.0
	 *
	 * @internal
	 */
	private function get_removed_from_sync_products_to_delete() {
		$posted_products = Helper::get_posted_value( WC_Facebook_Product::FB_REMOVE_FROM_SYNC );
		if ( empty( $posted_products ) ) {
			return [];
		}

		return array_map( 'absint', explode( ',', $posted_products ) );
	}

	/**
	 * Checks the product type and calls the corresponding on publish method.
	 *
	 * @param int $wp_id post ID
	 *
	 * @since 1.10.0
	 *
	 * @internal
	 */
	public function on_product_save( int $wp_id ) {
		$product = wc_get_product( $wp_id );
		if ( ! $product ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$sync_mode = isset( $_POST['wc_facebook_sync_mode'] )
			? sanitize_text_field( wp_unslash( $_POST['wc_facebook_sync_mode'] ) )
			: Admin::SYNC_MODE_SYNC_DISABLED;

		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$sync_enabled = Admin::SYNC_MODE_SYNC_DISABLED !== $sync_mode;

		if ( Admin::SYNC_MODE_SYNC_AND_SHOW === $sync_mode && $product->is_virtual() && 'bundle' !== $product->get_type() ) {
			// force to Sync and hide
			$sync_mode = Admin::SYNC_MODE_SYNC_AND_HIDE;
		}

		$products_to_delete_from_facebook = $this->get_removed_from_sync_products_to_delete();
		if ( $product->is_type( 'variable' ) ) {
			$this->save_variable_product_settings( $product );
			// check variations for deletion
			foreach ( $products_to_delete_from_facebook as $delete_product_id ) {
				$delete_product = wc_get_product( $delete_product_id );
				if ( empty( $delete_product ) ) {
					continue;
				}
				if ( Products::is_sync_enabled_for_product( $delete_product ) ) {
					continue;
				}
				$this->delete_fb_product( $delete_product );
			}
		}

		if ( $sync_enabled ) {
				Products::enable_sync_for_products( [ $product ] );
				Products::set_product_visibility( $product, Admin::SYNC_MODE_SYNC_AND_HIDE !== $sync_mode );
				$this->save_product_settings( $product );
		} else {
			// if previously enabled, add a notice on the next page load
			Products::disable_sync_for_products( [ $product ] );
			if ( in_array( $wp_id, $products_to_delete_from_facebook, true ) ) {
				$this->delete_fb_product( $product );
			}
		}
		if ( $sync_enabled ) {
			Admin\Products::save_commerce_fields( $product );
			switch ( $product->get_type() ) {
				case 'simple':
				case 'booking':
				case 'external':
				case 'composite':
					$this->on_simple_product_publish( $wp_id );
					break;
				case 'variable':
					$this->on_variable_product_publish( $wp_id );
					break;
				case 'subscription':
				case 'variable-subscription':
				case 'bundle':
					$this->on_product_publish( $wp_id );
					break;
			}
		}
	}

	/**
	 * Saves Facebook product attributes from POST data.
	 *
	 * @param WC_Facebook_Product $woo_product The Facebook product object
	 */
	private function save_facebook_product_attributes( $woo_product ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST[ WC_Facebook_Product::FB_BRAND ] ) ) {
			$woo_product->set_fb_brand( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_BRAND ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_MPN ] ) ) {
			$woo_product->set_fb_mpn( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_MPN ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_SIZE ] ) ) {
			$woo_product->set_fb_size( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_SIZE ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_COLOR ] ) ) {
			$woo_product->set_fb_color( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_COLOR ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_MATERIAL ] ) ) {
			$woo_product->set_fb_material( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_MATERIAL ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_PATTERN ] ) ) {
			$woo_product->set_fb_pattern( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PATTERN ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_AGE_GROUP ] ) ) {
			$woo_product->set_fb_age_group( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_AGE_GROUP ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_GENDER ] ) ) {
			$woo_product->set_fb_gender( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_GENDER ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_PRODUCT_CONDITION ] ) ) {
			$woo_product->set_fb_condition( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PRODUCT_CONDITION ] ) ) );
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Saves the submitted Facebook settings for a variable product.
	 *
	 * @param \WC_Product $product The variable product object.
	 */
	private function save_variable_product_settings( $product ) {
		$woo_product = new WC_Facebook_Product( $product->get_id() );
		$this->save_facebook_product_attributes( $woo_product );
	}

	/**
	 * Saves the submitted Facebook settings for a product.
	 *
	 * @param \WC_Product $product the product object
	 *
	 * @since 1.10.0
	 */
	private function save_product_settings( WC_Product $product ) {
		$woo_product = new WC_Facebook_Product( $product->get_id() );

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST[ self::FB_PRODUCT_DESCRIPTION ] ) ) {
			$woo_product->set_description( sanitize_text_field( wp_unslash( $_POST[ self::FB_PRODUCT_DESCRIPTION ] ) ) );
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			$woo_product->set_rich_text_description( $_POST[ self::FB_PRODUCT_DESCRIPTION ] );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_PRODUCT_PRICE ] ) ) {
			$woo_product->set_price( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PRODUCT_PRICE ] ) ) );
		}

		if ( isset( $_POST['fb_product_image_source'] ) ) {
			$product->update_meta_data( Products::PRODUCT_IMAGE_SOURCE_META_KEY, sanitize_key( wp_unslash( $_POST['fb_product_image_source'] ) ) );
			$product->save_meta_data();
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_PRODUCT_IMAGE ] ) ) {
			$woo_product->set_product_image( sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PRODUCT_IMAGE ] ) ) );
		}

		if ( isset( $_POST[ WC_Facebook_Product::FB_PRODUCT_VIDEO ] ) ) {
			$attachment_ids = sanitize_text_field( wp_unslash( $_POST[ WC_Facebook_Product::FB_PRODUCT_VIDEO ] ) );
			$woo_product->set_product_video_urls( $attachment_ids );
		}

		$this->save_facebook_product_attributes( $woo_product );
	}

	/**
	 * Deletes a product from Facebook.
	 *
	 * @param int $product_id product ID
	 */
	public function on_product_delete( int $product_id ) {
		$product = wc_get_product( $product_id );

		// bail if product does not exist
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		/**
		 * Bail if not enabled for sync, except if explicitly deleting from the metabox or when deleting the
		 * parent product ( Products::published_product_should_be_synced( $product ) will fail for the parent product
		 * when deleting a variable product. This causes the fb_group_id to remain on the DB. )
		 *
		 * @see ajax_delete_fb_product()
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ( ! wp_doing_ajax() || ! isset( $_POST['action'] ) || 'ajax_delete_fb_product' !== $_POST['action'] )
			&& ! Products::published_product_should_be_synced( $product ) && ! $product->is_type( 'variable' ) ) {
			return;
		}

		$this->delete_fb_product( $product );
	}

	/**
	 * Deletes Facebook product.
	 *
	 * @param \WC_Product $product WooCommerce product object
	 *
	 * @since 2.3.0
	 *
	 * @internal
	 */
	public function delete_fb_product( $product ) {

		$product_id = $product->get_id();

		if ( $product->is_type( 'variation' ) ) {
			$retailer_id = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $product );
			// enqueue variation to be deleted in the background
			$this->facebook_for_woocommerce->get_products_sync_handler()->delete_products( [ $retailer_id ] );
		} elseif ( $product->is_type( 'variable' ) ) {
			$retailer_ids = [];
			foreach ( $product->get_children() as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation instanceof \WC_Product ) {
					$retailer_ids[] = \WC_Facebookcommerce_Utils::get_fb_retailer_id( $variation );
				}
				delete_post_meta( $variation_id, self::FB_PRODUCT_ITEM_ID );
			}
			// enqueue variations to be deleted in the background
			$this->facebook_for_woocommerce->get_products_sync_handler()->delete_products( $retailer_ids );
		} else {

			$this->delete_product_item( $product_id );
		}

		// clear out both item and group IDs
		delete_post_meta( $product_id, self::FB_PRODUCT_ITEM_ID );
		delete_post_meta( $product_id, self::FB_PRODUCT_GROUP_ID );
	}

	/**
	 * Updates Facebook Visibility upon trashing and restore.
	 *
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_post $post
	 *
	 * @internal
	 */
	public function fb_change_product_published_status( $new_status, $old_status, $post ) {
		if ( ! $post ) {
			return;
		}

		if ( ! $this->should_update_visibility_for_product_status_change( $new_status, $old_status ) ) {
			return;
		}

		$product = wc_get_product( $post->ID );

		// Bail if we couldn't retrieve a valid product object or the product isn't enabled for sync
		//
		// Note that while moving a variable product to the trash, this method is called for each one of the
		// variations before it gets called with the variable product. As a result, Products::product_should_be_synced()
		// always returns false for the variable product (since all children are in the trash at that point).
		// This causes update_fb_visibility() to be called on simple products and product variations only.
		if ( ! $product instanceof \WC_Product || ( ! Products::published_product_should_be_synced( $product ) ) ) {
			return;
		}

		// Exclude variants. Product variables visibility is handled separately.
		// @See fb_restore_untrashed_variable_product.
		if ( $product->is_type( 'variant' ) ) {
			return;
		}

		$visibility = $product->is_visible() ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN;

		if ( self::FB_SHOP_PRODUCT_VISIBLE === $visibility ) {
			// - new status is 'publish' regardless of old status, sync to Facebook
			$this->on_product_publish( $product->get_id() );
		} else {
			$this->update_fb_visibility( $product, $visibility );
		}
	}

	/**
	 * Re-publish restored variable product.
	 *
	 * @param int $post_id
	 *
	 * @internal
	 */
	public function fb_restore_untrashed_variable_product( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}

		$visibility = $product->is_visible() ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN;

		if ( self::FB_SHOP_PRODUCT_VISIBLE === $visibility ) {
			// - new status is 'publish' regardless of old status, sync to Facebook
			$this->on_product_publish( $product->get_id() );
		}
	}


	/**
	 * Determines whether the product visibility needs to be updated for the given status change.
	 *
	 * Change from publish status -> unpublish status (e.g. trash, draft, etc.)
	 * Change from trash status -> publish status
	 * No need to update for change from trash <-> unpublish status
	 *
	 * @param string $new_status
	 * @param string $old_status
	 *
	 * @return bool
	 * @since 2.0.2
	 */
	private function should_update_visibility_for_product_status_change( $new_status, $old_status ) {
		return ( 'publish' === $old_status && 'publish' !== $new_status ) || ( 'trash' === $old_status && 'publish' === $new_status ) || ( 'future' === $old_status && 'publish' === $new_status );
	}

	/**
	 * Deletes a product from Facebook when status is changed to draft.
	 *
	 * @param \WP_post $post
	 *
	 * @since 3.0.27
	 */
	public function delete_draft_product( $post ) {

		if ( ! $post ) {
			return;
		}

		$this->on_product_delete( $post->ID );
	}


	/**
	 * Generic function for use with any product publishing.
	 *
	 * Will determine product type (simple or variable) and delegate to
	 * appropriate handler.
	 *
	 * @param int $product_id product ID
	 */
	public function on_product_publish( $product_id ) {
		// bail if the plugin is not configured properly
		if ( ! $this->is_configured() || ! $this->get_product_catalog_id() ) {
			return;
		}

		$product = wc_get_product( $product_id );

		if ( $product->is_type( 'variable' ) ) {
			$this->on_variable_product_publish( $product_id );
		} else {
			$this->on_simple_product_publish( $product_id );
		}
	}

	/**
	 * If the user has opt-in to remove products that are out of stock,
	 * this function will delete the product from FB Page as well.
	 *
	 * @param int        $wp_id
	 * @param WC_Product $woo_product
	 *
	 * @return bool
	 */
	public function delete_on_out_of_stock( int $wp_id, WC_Product $woo_product ): bool {
		if ( Products::product_should_be_deleted( $woo_product ) ) {
			$product = wc_get_product( $wp_id );
			$this->delete_fb_product( $product );

			return true;
		}

		return false;
	}

	/**
	 * Syncs product to Facebook when saving a variable product.
	 *
	 * @param int                      $wp_id product post ID
	 * @param WC_Facebook_Product|null $woo_product product object
	 */
	public function on_variable_product_publish( $wp_id, $woo_product = null ) {
		if ( ! $woo_product instanceof \WC_Facebook_Product ) {
			$woo_product = new \WC_Facebook_Product( $wp_id );
		}

		if ( $this->delete_on_out_of_stock( $wp_id, $woo_product->woo_product ) ) {
			return;
		}

		if ( ! $this->product_should_be_synced( $woo_product->woo_product ) ) {
			return;
		}

		// Check if product group has been published to FB. If not, it's new.
		// If yes, loop through variants and see if product items are published.
		$fb_product_group_id = $this->get_product_fbid( self::FB_PRODUCT_GROUP_ID, $wp_id, $woo_product );
		if ( $fb_product_group_id ) {
			$woo_product->fb_visibility = Products::is_product_visible( $woo_product->woo_product );
			$this->update_product_group( $woo_product );
		} else {
			$retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $woo_product->woo_product );
			$this->create_product_group( $woo_product, $retailer_id );
		}

		$variation_ids = [];

		// scheduled update for each variation that should be synced
		foreach ( $woo_product->get_children() as $variation_id ) {
			$variation = wc_get_product( $variation_id );
			if ( $variation instanceof WC_Product && $this->product_should_be_synced( $variation ) && ! $this->delete_on_out_of_stock( $variation_id, $variation ) ) {
				$variation_ids[] = $variation_id;
			}
		}

		$this->facebook_for_woocommerce->get_products_sync_handler()->create_or_update_products( $variation_ids );
	}

	/**
	 * Syncs product to Facebook when saving a simple product.
	 *
	 * @param int                      $wp_id product post ID
	 * @param WC_Facebook_Product|null $woo_product product object
	 * @param WC_Facebook_Product|null $parent_product parent object
	 *
	 * @return int|mixed|void|null
	 */
	public function on_simple_product_publish( $wp_id, $woo_product = null, &$parent_product = null ) {
		if ( ! $woo_product instanceof \WC_Facebook_Product ) {
			$woo_product = new \WC_Facebook_Product( $wp_id, $parent_product );
		}

		if ( $this->delete_on_out_of_stock( $wp_id, $woo_product->woo_product ) ) {
			return;
		}

		if ( ! $this->product_should_be_synced( $woo_product->woo_product ) ) {
			return;
		}

		// Check if this product has already been published to FB.
		// If not, it's new!
		$fb_product_item_id = $this->get_product_fbid( self::FB_PRODUCT_ITEM_ID, $wp_id, $woo_product );

		if ( $fb_product_item_id ) {
			$woo_product->fb_visibility = Products::is_product_visible( $woo_product->woo_product );
			$this->update_product_item_batch_api( $woo_product, $fb_product_item_id );
			return $fb_product_item_id;
		} else {
			return $this->create_product_simple( $woo_product );  // new product
		}
	}

	/**
	 * Determines whether the product with the given ID should be synced.
	 *
	 * @param WC_Product $product product object
	 *
	 * @since 2.0.0
	 */
	public function product_should_be_synced( WC_Product $product ): bool {
		try {
			$this->facebook_for_woocommerce->get_product_sync_validator( $product )->validate();

			return true;
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Create product group and product, store fb-specific info.
	 *
	 * @param WC_Facebook_Product $woo_product
	 *
	 * @return string
	 */
	public function create_product_simple( WC_Facebook_Product $woo_product ): string {
		$retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $woo_product );
		return $this->create_product_item_batch_api( $woo_product, $retailer_id );
	}

	/**
	 * @param WC_Facebook_Product $woo_product
	 * @param string              $retailer_id
	 *
	 * @return ?string
	 */
	public function create_product_group( WC_Facebook_Product $woo_product, string $retailer_id ): ?string {
		$product_group_data             = [
			'retailer_id' => $retailer_id,
		];
		$product_group_data['variants'] = $woo_product->prepare_variants_for_group();

		try {
			$create_product_group_result = $this->facebook_for_woocommerce->get_api()->create_product_group(
				$this->get_product_catalog_id(),
				$product_group_data
			);

			// New variant added
			if ( $create_product_group_result->id ) {
				$fb_product_group_id = $create_product_group_result->id;
				update_post_meta(
					$woo_product->get_id(),
					self::FB_PRODUCT_GROUP_ID,
					$fb_product_group_id
				);

				return $fb_product_group_id;
			}
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to create the product group: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}

		return null;
	}

	/**
	 * Update existing product group (variant data only)
	 *
	 * @param WC_Facebook_Product $woo_product
	 **/
	public function update_product_group( WC_Facebook_Product $woo_product ) {
		$fb_product_group_id = $this->get_product_fbid(
			self::FB_PRODUCT_GROUP_ID,
			$woo_product->get_id(),
			$woo_product
		);

		if ( ! $fb_product_group_id ) {
			return;
		}

		$variants = $woo_product->prepare_variants_for_group();

		if ( ! $variants ) {
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled(
				sprintf(
				/* translators: %1$s is referring to facebook product group id. */
					__(
						'Nothing to update for product group for %1$s',
						'facebook-for-woocommerce'
					),
					$fb_product_group_id
				)
			);

			return;
		}

		$product_group_data = [
			'variants' => $variants,
		];

		// Figure out the matching default variation.
		$default_product_fbid = $this->get_product_group_default_variation( $woo_product, $fb_product_group_id );

		if ( $default_product_fbid ) {
			$product_group_data['default_product_id'] = $default_product_fbid;
		}

		try {
			$response = $this->facebook_for_woocommerce->get_api()->update_product_group( $fb_product_group_id, $product_group_data );
			if ( $response->success ) {
				$this->display_success_message(
					'Updated product group <a href="https://facebook.com/' .
					$fb_product_group_id . '" target="_blank">' . $fb_product_group_id .
					'</a> on Facebook.'
				);
			} else {
				$this->display_error_message(
					'Updating product group <a href="https://facebook.com/' .
					$fb_product_group_id . '" target="_blank">' . $fb_product_group_id .
					'</a> on Facebook has failed.'
				);
			}
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to update Product Group %s: %s', $fb_product_group_id, $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}
	}

	/**
	 * Creates a product item using the facebook Catalog Batch API. This replaces existing functionality,
	 * which is currently using facebook Product Item API implemented by `WC_Facebookcommerce_Integration::create_product_item`
	 *
	 * @param WC_Facebook_Product $woo_product
	 * @param string              $retailer_id
	 * *@since 3.1.7
	 */
	public function create_product_item_batch_api( $woo_product, $retailer_id ): string {
		try {
			$product_data        = $woo_product->prepare_product( $retailer_id, \WC_Facebook_Product::PRODUCT_PREP_TYPE_ITEMS_BATCH );
			$requests            = WC_Facebookcommerce_Utils::prepare_product_requests_items_batch( $product_data );
			$facebook_catalog_id = $this->get_product_catalog_id();
			$response            = $this->facebook_for_woocommerce->get_api()->send_item_updates( $facebook_catalog_id, $requests );

			if ( $response->handles ) {
				return '';
			} else {
				$this->display_error_message(
					'Updated product on Facebook has failed.'
				);
			}
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to create a product item: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}

		return '';
	}

	public function create_product_item( $woo_product, $retailer_id, $product_group_id ): string {
		try {
			$product_data   = $woo_product->prepare_product( $retailer_id );
			$product_result = $this->facebook_for_woocommerce->get_api()->create_product_item( $product_group_id, $product_data );

			if ( $product_result->id ) {
				$fb_product_item_id = $product_result->id;

				update_post_meta(
					$woo_product->get_id(),
					self::FB_PRODUCT_ITEM_ID,
					$fb_product_item_id
				);

				$this->display_success_message(
					'Created product item <a href="https://facebook.com/' .
					$fb_product_item_id . '" target="_blank">' .
					$fb_product_item_id . '</a> on Facebook.'
				);

				return $fb_product_item_id;
			}
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to create a product item: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}

		return '';
	}

	/**
	 * Determines if there is a matching variation for the default attributes.
	 * Select closest matching if best can't be found.
	 *
	 * @param WC_Facebook_Product $woo_product
	 * @param string              $fb_product_group_id
	 *
	 * @return integer|null Facebook Catalog variation id.
	 * @since 2.1.2
	 *
	 * @since 2.6.6
	 * The algorithm only considers the variations that already have been synchronized to the catalog successfully.
	 */
	private function get_product_group_default_variation( WC_Facebook_Product $woo_product, string $fb_product_group_id ) {
		$default_attributes = $woo_product->woo_product->get_default_attributes( 'edit' );
		$default_variation  = null;

		// Fetch variations that exist in the catalog.
		$existing_catalog_variations              = $this->find_variation_product_item_ids( $fb_product_group_id );
		$existing_catalog_variations_retailer_ids = array_keys( $existing_catalog_variations );

		// All woocommerce variations for the product.
		$product_variations = $woo_product->woo_product->get_available_variations();

		if ( ! empty( $default_attributes ) ) {

			$best_match_count = 0;
			foreach ( $product_variations as $variation ) {

				$fb_retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id(
					wc_get_product(
						$variation['variation_id']
					)
				);

				// Check if currently processed variation exist in the catalog.
				if ( ! in_array( $fb_retailer_id, $existing_catalog_variations_retailer_ids ) ) {
					continue;
				}

				$variation_attributes       = $this->get_product_variation_attributes( $variation );
				$variation_attributes_count = count( $variation_attributes );
				$matching_attributes_count  = count( array_intersect_assoc( $default_attributes, $variation_attributes ) );

				// Check how much current variation matches the selected default attributes.
				if ( $matching_attributes_count === $variation_attributes_count ) {
					// We found a perfect match;
					$default_variation = $existing_catalog_variations[ $fb_retailer_id ];
					break;
				}
				if ( $matching_attributes_count > $best_match_count ) {
					// We found a better match.
					$default_variation = $existing_catalog_variations[ $fb_retailer_id ];
				}
			}
		}

		/**
		 * Filter product group default variation.
		 * This can be used to customize the choice of a default variation (e.g. choose one with the lowest price).
		 *
		 * @param integer|null Facebook Catalog variation id.
		 * @param \WC_Facebook_Product WooCommerce product.
		 * @param string product group ID.
		 * @param array List of available WC_Product variations.
		 * @param array List of Product Item IDs indexed by the variation's retailer ID.
		 *
		 * @since 2.6.25
		 */
		return apply_filters(
			'wc_facebook_product_group_default_variation',
			$default_variation,
			$woo_product,
			$fb_product_group_id,
			$product_variations,
			$existing_catalog_variations
		);
	}

	/**
	 * Parses given product variation for it's attributes
	 *
	 * @param array $variation
	 *
	 * @return array
	 * @since 2.1.2
	 */
	private function get_product_variation_attributes( array $variation ): array {
		$final_attributes     = [];
		$variation_attributes = $variation['attributes'];

		foreach ( $variation_attributes as $attribute_name => $attribute_value ) {
			$final_attributes[ str_replace( 'attribute_', '', $attribute_name ) ] = $attribute_value;
		}

		return $final_attributes;
	}

	/**
	 * Update existing product using batch API.
	 *
	 * @param WC_Facebook_Product $woo_product
	 * @param string              $fb_product_item_id
	 *
	 * @return void
	 */
	public function update_product_item_batch_api( WC_Facebook_Product $woo_product, string $fb_product_item_id ): void {
		$product  = $woo_product->prepare_product( null, \WC_Facebook_Product::PRODUCT_PREP_TYPE_ITEMS_BATCH );
		$requests = WC_Facebookcommerce_Utils::prepare_product_requests_items_batch( $product );

		try {
			$facebook_catalog_id = $this->get_product_catalog_id();
			$response            = $this->facebook_for_woocommerce->get_api()->send_item_updates( $facebook_catalog_id, $requests );
			if ( $response->handles ) {
				$this->display_success_message(
					'Updated product  <a href="https://facebook.com/' . $fb_product_item_id .
					'" target="_blank">' . $fb_product_item_id . '</a> on Facebook.'
				);
			} else {
				$this->display_error_message(
					'Updated product  <a href="https://facebook.com/' . $fb_product_item_id .
					'" target="_blank">' . $fb_product_item_id . '</a> on Facebook has failed.'
				);
			}
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to update a product item: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}
	}

	/**
	 * Update existing product.
	 *
	 * @param WC_Facebook_Product $woo_product
	 * @param string              $fb_product_item_id
	 *
	 * @return void
	 */
	public function update_product_item( WC_Facebook_Product $woo_product, string $fb_product_item_id ): void {
		$product_data = $woo_product->prepare_product();

		// send an empty string to clear the additional_image_urls property if the product has no additional images
		if ( empty( $product_data['additional_image_urls'] ) ) {
			$product_data['additional_image_urls'] = '';
		}
		try {
			$result = $this->facebook_for_woocommerce->get_api()->update_product_item( $fb_product_item_id, $product_data );
			if ( $result->success ) {
				$this->display_success_message(
					'Updated product  <a href="https://facebook.com/' . $fb_product_item_id .
					'" target="_blank">' . $fb_product_item_id . '</a> on Facebook.'
				);
			} else {
				$this->display_error_message(
					'Updated product  <a href="https://facebook.com/' . $fb_product_item_id .
					'" target="_blank">' . $fb_product_item_id . '</a> on Facebook has failed.'
				);
			}
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to update a product item: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}
	}


	/**
	 * Create or update product set
	 *
	 * @param array $product_set_data Product Set data.
	 * @param int   $product_set_id Product Set Term Id.
	 * *@since 2.3.0
	 */
	public function create_or_update_product_set_item( $product_set_data, $product_set_id ) {
		// check if exists in FB
		$fb_product_set_id = get_term_meta( $product_set_id, self::FB_PRODUCT_SET_ID, true );

		try {
			// set data and execute API call
			$result = empty( $fb_product_set_id )
				? $this->facebook_for_woocommerce->get_api()->create_product_set_item( $this->get_product_catalog_id(), $product_set_data )
				: $this->facebook_for_woocommerce->get_api()->update_product_set_item( $fb_product_set_id, $product_set_data );

			// update product set to set Facebook Product Set ID
			if ( $result && empty( $fb_product_set_id ) ) {
				$fb_product_set_id = $result->id;
				update_term_meta(
					$product_set_id,
					self::FB_PRODUCT_SET_ID,
					$fb_product_set_id
				);
			}
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to create/update a product set: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}
	}

	/**
	 * Delete product set
	 *
	 * @param string $fb_product_set_id Facebook Product Set ID.
	 *
	 * @return void
	 * @throws ApiException If the request fails.
	 * @throws \WooCommerce\Facebook\API\Exceptions\Request_Limit_Reached If the request limit is reached.
	 */
	public function delete_product_set_item( string $fb_product_set_id ) {
		$allow_live_deletion = apply_filters( 'wc_facebook_commerce_allow_live_product_set_deletion', true, $fb_product_set_id );
		try {
			$this->facebook_for_woocommerce->get_api()->delete_product_set_item( $fb_product_set_id, $allow_live_deletion );
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to delete a product set item: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}
	}

	/**
	 * Displays Batch API completed message on simple_product_publish.
	 * This is called by the hook `add_meta_boxes_product` because that is sufficient time
	 * to retrieve product_item_id for the product item created via batch API.
	 *
	 * Some sanity checks are added before displaying the message after publish
	 *  - product_item_id : if exists, means product was created else not and don't display
	 *  - should_sync: Don't display if the product is not supposed to be synced.
	 *
	 * @param WP_Post $post WordPress Post
	 * @return void
	 */
	public function display_batch_api_completed( $post ) {
		$fb_product         = new \WC_Facebook_Product( $post->ID );
		$fb_product_item_id = null;
		$should_sync        = true;

		// Bail if this is not a WooCommerce product.
		if ( ! $fb_product->woo_product instanceof \WC_Product ) {
			return;
		}

		try {
			facebook_for_woocommerce()->get_product_sync_validator( $fb_product->woo_product )->validate();
		} catch ( \Exception $e ) {
			$should_sync = false;
		}

		if ( $should_sync ) {
			if ( $fb_product->woo_product->is_type( 'variable' ) ) {
				$fb_product_item_id = $this->get_product_fbid( self::FB_PRODUCT_GROUP_ID, $post->ID, $fb_product->woo_product );
			} else {
				$fb_product_item_id = $this->get_product_fbid( self::FB_PRODUCT_ITEM_ID, $post->ID, $fb_product->woo_product );
			}
		}

		if ( $fb_product_item_id ) {
			$this->display_success_message(
				'<a href="https://business.facebook.com/commerce/catalogs/' .
					$this->get_product_catalog_id() .
					'/products/" target="_blank">View product on Meta catalog</a>'
			);
		}
	}


	/**
	 * Checks the feed upload status (FBE v1.0).
	 *
	 * @internal
	 */
	public function ajax_check_feed_upload_status() {
		$response = [
			'connected' => true,
			'status'    => 'complete',
		];
		printf( json_encode( $response ) );
		wp_die();
	}

	/**
	 * Check Feed Upload Status (FBE v2.0)
	 * TODO: When migrating to FBE v2.0, remove above function and rename
	 * below function to ajax_check_feed_upload_status()
	 **/
	public function ajax_check_feed_upload_status_v2() {
		\WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'check feed upload status', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		if ( $this->is_configured() ) {
			$response = [
				'connected' => true,
				'status'    => 'in progress',
			];

			if ( ! empty( $this->get_upload_id() ) ) {

				if ( ! isset( $this->fbproductfeed ) ) {

					if ( ! class_exists( 'WC_Facebook_Product_Feed' ) ) {
						include_once 'includes/fbproductfeed.php';
					}

					$this->fbproductfeed = new \WC_Facebook_Product_Feed();
				}

				$status = $this->fbproductfeed->is_upload_complete( $this->settings );

				$response['status'] = $status;
			} else {
				$response = [
					'connected' => true,
					'status'    => 'error',
				];
			}

			if ( 'complete' === $response['status'] ) {
				update_option(
					$this->get_option_key(),
					apply_filters(
						'woocommerce_settings_api_sanitized_fields_' . $this->id,
						$this->settings
					)
				);
			}
		} else {
			$response = [ 'connected' => false ];
		}
		printf( json_encode( $response ) );
		wp_die();
	}

	/**
	 * Display custom success message (sugar).
	 *
	 * @param string $msg
	 *
	 * @return void
	 * @deprecated 2.1.0
	 */
	public function display_success_message( string $msg ): void {
		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
		set_transient(
			'facebook_plugin_api_success',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
	}

	/**
	 * Display custom info message (sugar).
	 *
	 * @param string $msg
	 *
	 * @return void
	 */
	public function display_info_message( string $msg ): void {
		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
		set_transient(
			'facebook_plugin_api_info',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
	}

	/**
	 * Display custom "sticky" info message.
	 * Call remove_sticky_message or wait for time out.
	 *
	 * @param string $msg
	 *
	 * @return void
	 */
	public function display_sticky_message( string $msg ): void {
		$msg = self::FB_ADMIN_MESSAGE_PREPEND . $msg;
		set_transient(
			'facebook_plugin_api_sticky',
			$msg,
			self::FB_MESSAGE_DISPLAY_TIME
		);
	}

	/**
	 * Remove custom "sticky" info message.
	 *
	 * @return void
	 */
	public function remove_sticky_message() {
		delete_transient( 'facebook_plugin_api_sticky' );
	}

	/**
	 * Remove 'resync' message.
	 *
	 * @return void
	 */
	public function remove_resync_message() {
		$msg = get_transient( 'facebook_plugin_api_sticky' );
		if ( $msg && strpos( $msg, 'Sync' ) !== false ) {
			delete_transient( 'facebook_plugin_resync_sticky' );
		}
	}

	/**
	 * Logs and stores custom error message (sugar).
	 *
	 * @param string $msg
	 *
	 * @return void
	 */
	public function display_error_message( string $msg ): void {
		WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $msg );
		set_transient( 'facebook_plugin_api_error', $msg, self::FB_MESSAGE_DISPLAY_TIME );
	}

	/**
	 * Displays out of sync message if products are edited using WooCommerce Advanced Bulk Edit.
	 *
	 * @param string $import_id
	 *
	 * @return void
	 */
	public function ajax_woo_adv_bulk_edit_compat( string $import_id ): void {
		if ( ! WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'adv bulk edit', false ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';

		if ( strpos( $type, 'product' ) !== false && strpos( $type, 'load' ) === false ) {
			$this->display_out_of_sync_message( 'advanced bulk edit' );
		}
	}

	/**
	 * Display import message.
	 *
	 * @param string $import_id
	 *
	 * @return void
	 */
	public function wp_all_import_compat( string $import_id ): void {
		$import = new PMXI_Import_Record();
		$import->getById( $import_id );
		if ( ! $import->isEmpty() && in_array(
			$import->options['custom_type'],
			[
				'product',
				'product_variation',
			],
			true
		) ) {
			$this->display_out_of_sync_message( 'import' );
		}
	}

	/**
	 * Displays out of sync message.
	 *
	 * @param string $action_name
	 *
	 * @return void
	 */
	public function display_out_of_sync_message( string $action_name ): void {
		$this->display_sticky_message(
			sprintf(
				'Products may be out of Sync with Facebook due to your recent ' . $action_name . '.' .
				' <a href="%s&fb_force_resync=true&remove_sticky=true">Re-Sync them with FB.</a>',
				$this->facebook_for_woocommerce->get_settings_url()
			)
		);
	}

	/**
	 * If we get a product group ID or product item ID back for a dupe retailer
	 * id error, update existing ID.
	 *
	 * @param stdClass $error_data
	 * @param int      $wpid
	 *
	 * @return null
	 **/
	public function get_existing_fbid( stdClass $error_data, int $wpid ) {
		if ( isset( $error_data->product_group_id ) ) {
			update_post_meta(
				$wpid,
				self::FB_PRODUCT_GROUP_ID,
				(string) $error_data->product_group_id
			);

			return $error_data->product_group_id;
		} elseif ( isset( $error_data->product_item_id ) ) {
			update_post_meta(
				$wpid,
				self::FB_PRODUCT_ITEM_ID,
				(string) $error_data->product_item_id
			);

			return $error_data->product_item_id;
		} else {
			return null;
		}
	}

	/**
	 * Checks for API key and other API errors.
	 */
	public function checks() {
		// TODO improve this by checking the settings page with Framework method and ensure error notices are displayed under the Integration sections {FN 2020-01-30}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && 'wc-facebook' === $_GET['page'] ) {
			$this->display_errors();
		}

		$this->maybe_display_facebook_api_messages();
	}

	/**
	 * Gets a sample feed with up to 12 published products.
	 *
	 * @return string
	 */
	public function get_sample_product_feed() {
		ob_start();
		// get up to 12 published posts that are products
		$args = [
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'fields'         => 'ids',
		];

		$post_ids = get_posts( $args );
		$items    = [];

		foreach ( $post_ids as $post_id ) {
			$woo_product  = new WC_Facebook_Product( $post_id );
			$product_data = $woo_product->prepare_product();

			$feed_item = [
				'title'        => strip_tags( $product_data['name'] ),
				'availability' => $woo_product->is_in_stock() ? 'in stock' :
					'out of stock',
				'description'  => strip_tags( $product_data['description'] ),
				'id'           => $product_data['retailer_id'],
				'image_link'   => $product_data['image_url'],
				'brand'        => Helper::str_truncate( wp_strip_all_tags( WC_Facebookcommerce_Utils::get_store_name() ), 100 ),
				'link'         => $product_data['url'],
				'price'        => $product_data['price'] . ' ' . get_woocommerce_currency(),
			];
			array_push( $items, $feed_item );
		}
		// https://codex.wordpress.org/Function_Reference/wp_reset_postdata
		wp_reset_postdata();
		ob_end_clean();

		return json_encode( [ $items ] );
	}

	/**
	 * Loop through array of WPIDs to remove metadata.
	 *
	 * @param array $products
	 */
	public function delete_post_meta_loop( array $products ) {
		foreach ( $products as $product_id ) {
			delete_post_meta( $product_id, self::FB_PRODUCT_GROUP_ID );
			delete_post_meta( $product_id, self::FB_PRODUCT_ITEM_ID );
			delete_post_meta( $product_id, Products::VISIBILITY_META_KEY );
		}
	}

	/**
	 * Remove FBIDs from all products when resetting store.
	 **/
	public function reset_all_products() {
		if ( ! is_admin() ) {
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled(
				'Not resetting any FBIDs from products,
        must call reset from admin context.'
			);

			return false;
		}

		// Include draft products (omit 'post_status' => 'publish')
		WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Removing FBIDs from all products' );

		$post_ids = get_posts(
			[
				'post_type'      => 'product',
				'posts_per_page' => - 1,
				'fields'         => 'ids',
			]
		);

		$children = [];
		foreach ( $post_ids as $post_id ) {
			$children = array_merge(
				get_posts(
					[
						'post_type'      => 'product_variation',
						'posts_per_page' => - 1,
						'post_parent'    => $post_id,
						'fields'         => 'ids',
					]
				),
				$children
			);
		}
		$post_ids = array_merge( $post_ids, $children );
		$this->delete_post_meta_loop( $post_ids );

		WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Product FBIDs deleted' );

		return true;
	}

	/**
	 * Remove FBIDs from a single WC product
	 *
	 * @param int $wp_id
	 */
	public function reset_single_product( int $wp_id ) {
		$woo_product = new WC_Facebook_Product( $wp_id );
		$products    = [ $woo_product->get_id() ];
		if ( WC_Facebookcommerce_Utils::is_variable_type( $woo_product->get_type() ) ) {
			$products = array_merge( $products, $woo_product->get_children() );
		}

		$this->delete_post_meta_loop( $products );

		WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Deleted FB Metadata for product ' . $wp_id );
	}

	/**
	 * Ajax reset all Facebook products.
	 *
	 * @return void
	 */
	public function ajax_reset_all_fb_products() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'reset products', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		$this->reset_all_products();
		wp_reset_postdata();
		wp_die();
	}

	/**
	 * Special function to run all visible products through on_product_publish
	 *
	 * @internal
	 */
	public function ajax_sync_all_fb_products() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'syncall products', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );

		$this->sync_facebook_products();
	}

	/**
	 * Syncs Facebook products using the GraphAPI.
	 *
	 * It can either use a Feed upload or update each product individually based on the selecetd method.
	 * Ends the request sending a JSON response indicating success or failure.
	 *
	 * @since 1.10.2
	 */
	private function sync_facebook_products() {
		try {
			$this->sync_facebook_products_using_background_processor();
			wp_send_json_success();
		} catch ( PluginException $e ) {
			// Access token has expired
			if ( 190 === $e->getCode() ) {
				$error_message = __( 'Your connection has expired.', 'facebook-for-woocommerce' ) . ' <strong>' . __( 'Please click Manage connection > Advanced Options > Update Token to refresh your connection to Facebook.', 'facebook-for-woocommerce' ) . '</strong>';
			} else {
				$error_message = $e->getMessage();
			}

			$message = sprintf(
			/* translators: Placeholders %s - error message */
				__( 'There was an error trying to sync the products to Facebook. %s', 'facebook-for-woocommerce' ),
				$error_message
			);

			wp_send_json_error( [ 'error' => $message ] );
		}
	}

	/**
	 * Syncs Facebook products using the background processor.
	 *
	 * @since 1.10.2
	 *
	 * @return bool
	 * @throws PluginException If the plugin is not configured or the Catalog ID is missing.
	 */
	private function sync_facebook_products_using_background_processor() {
		if ( ! $this->is_product_sync_enabled() ) {
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Sync to Facebook is disabled' );
			throw new PluginException( __( 'Product sync is disabled.', 'facebook-for-woocommerce' ) );
		}

		if ( ! $this->is_configured() || ! $this->get_product_catalog_id() ) {
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( sprintf( 'Not syncing, the plugin is not configured or the Catalog ID is missing' ) );
			throw new PluginException( __( 'The plugin is not configured or the Catalog ID is missing.', 'facebook-for-woocommerce' ) );
		}

		$this->remove_resync_message();

		$currently_syncing = get_transient( self::FB_SYNC_IN_PROGRESS );
		if ( isset( $this->background_processor ) ) {
			if ( $this->background_processor->is_updating() ) {
				$this->background_processor->handle_cron_healthcheck();
				$currently_syncing = 1;
			}
		}

		if ( $currently_syncing ) {
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Not syncing again, sync already in progress' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync during an in-progress sync!',
				[],
				true
			);
			throw new PluginException( __( 'A product sync is in progress. Please wait until the sync finishes before starting a new one.', 'facebook-for-woocommerce' ) );
		}

		try {
			$catalog = $this->facebook_for_woocommerce->get_api()->get_catalog( $this->get_product_catalog_id() );
		} catch ( ApiException $e ) {
			$message = sprintf( 'There was an error trying to delete a product set item: %s', $e->getMessage() );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
		}
		if ( $catalog->id ) {
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Not syncing, invalid product catalog!' );
			WC_Facebookcommerce_Utils::fblog(
				'Tried to sync with an invalid product catalog!',
				[],
				true
			);
			throw new PluginException( __( "We've detected that your Facebook Product Catalog is no longer valid. This may happen if it was deleted, but could also be a temporary error. If the error persists, please click Manage connection > Advanced Options > Remove and setup the plugin again.", 'facebook-for-woocommerce' ) );
		}

		// Get all published posts. First unsynced then already-synced.
		$post_ids_new = WC_Facebookcommerce_Utils::get_wp_posts( self::FB_PRODUCT_GROUP_ID, 'NOT EXISTS' );
		$post_ids_old = WC_Facebookcommerce_Utils::get_wp_posts( self::FB_PRODUCT_GROUP_ID, 'EXISTS' );

		$total_new = count( $post_ids_new );
		$total_old = count( $post_ids_old );
		$post_ids  = array_merge( $post_ids_new, $post_ids_old );
		$total     = count( $post_ids );

		WC_Facebookcommerce_Utils::fblog(
			'Attempting to sync ' . $total . ' ( ' .
			$total_new . ' new) products with settings: ',
			$this->settings,
			false
		);

		// Check for background processing (Woo 3.x.x)
		if ( isset( $this->background_processor ) ) {
			$starting_message = sprintf(
				'Starting background sync to Facebook: %d products...',
				$total
			);
			set_transient( self::FB_SYNC_IN_PROGRESS, true, self::FB_SYNC_TIMEOUT );
			set_transient( self::FB_SYNC_REMAINING, (int) $total );
			$this->display_info_message( $starting_message );
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $starting_message );
			foreach ( $post_ids as $post_id ) {
				WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Pushing post to queue: ' . $post_id );
				$this->background_processor->push_to_queue( $post_id );
			}

			$this->background_processor->save()->dispatch();
			// reset FB_SYNC_REMAINING to avoid race condition
			set_transient( self::FB_SYNC_REMAINING, (int) $total );
			// handle_cron_healthcheck must be called
			// https://github.com/A5hleyRich/wp-background-processing/issues/34
			$this->background_processor->handle_cron_healthcheck();
		} else {
			// Oldschool sync for WooCommerce 2.x
			$count = ( $total_old === $total ) ? 0 : $total_old;
			foreach ( $post_ids as $post_id ) {
				// Repeatedly overwrite sync total while in actual sync loop
				set_transient( self::FB_SYNC_IN_PROGRESS, true, self::FB_SYNC_TIMEOUT );

				$this->display_sticky_message(
					sprintf(
						'Syncing products to Facebook: %d out of %d...',
						// Display different # when resuming to avoid confusion.
						min( $count, $total ),
						$total
					),
					true
				);

				$this->on_product_publish( $post_id );
				++$count;
			}
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( 'Synced ' . $count . ' products' );
			$this->remove_sticky_message();
			$this->display_info_message( 'Facebook product sync complete!' );
			delete_transient( self::FB_SYNC_IN_PROGRESS );
			WC_Facebookcommerce_Utils::fblog(
				'Product sync complete. Total products synced: ' . $count
			);
		}

		// https://codex.wordpress.org/Function_Reference/wp_reset_postdata
		wp_reset_postdata();

		return true;
	}

	/**
	 * Toggles product visibility via AJAX.
	 *
	 * @internal
	 * @deprecated since 1.10.0
	 **/
	public function ajax_fb_toggle_visibility() {
		wc_deprecated_function( __METHOD__, '1.10.0' );
	}

	/** Getter methods ************************************************************************************************/

	/**
	 * Gets the product catalog ID.
	 *
	 * @return string
	 * @since 1.10.0
	 */
	public function get_product_catalog_id() {
		if ( ! is_string( $this->product_catalog_id ) ) {
			$value                    = get_option( self::OPTION_PRODUCT_CATALOG_ID, '' );
			$this->product_catalog_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook product catalog ID.
		 *
		 * @param string $product_catalog_id Facebook product catalog ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return apply_filters( 'wc_facebook_product_catalog_id', $this->product_catalog_id, $this );
	}

	/**
	 * Gets the external merchant settings ID.
	 *
	 * @return string
	 * @since 1.10.0
	 */
	public function get_external_merchant_settings_id() {
		if ( ! is_string( $this->external_merchant_settings_id ) ) {
			$value                               = get_option( self::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, '' );
			$this->external_merchant_settings_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook external merchant settings ID.
		 *
		 * @param string $external_merchant_settings_id Facebook external merchant settings ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (string) apply_filters( 'wc_facebook_external_merchant_settings_id', $this->external_merchant_settings_id, $this );
	}

	/**
	 * Gets the feed ID.
	 *
	 * @return string
	 * @since 1.10.0
	 */
	public function get_feed_id() {
		if ( ! is_string( $this->feed_id ) ) {
			$value         = get_option( self::OPTION_FEED_ID, '' );
			$this->feed_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook feed ID.
		 *
		 * @param string $feed_id Facebook feed ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (string) apply_filters( 'wc_facebook_feed_id', $this->feed_id, $this );
	}

	/***
	 * Gets the Facebook Upload ID.
	 *
	 * @return string
	 * @since 1.11.0
	 */
	public function get_upload_id() {
		if ( ! is_string( $this->upload_id ) ) {
			$value           = get_option( self::OPTION_UPLOAD_ID, '' );
			$this->upload_id = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook upload ID.
		 *
		 * @param string $upload_id Facebook upload ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.11.0
		 */
		return (string) apply_filters( 'wc_facebook_upload_id', $this->upload_id, $this );
	}

	/**
	 * Gets the Facebook pixel install time in UTC seconds.
	 *
	 * @return int
	 * @since 1.10.0
	 */
	public function get_pixel_install_time() {
		if ( ! (int) $this->pixel_install_time ) {
			$value = (int) get_option( self::OPTION_PIXEL_INSTALL_TIME, 0 );
			// phpcs:ignore Universal.Operators.DisallowShortTernary.Found
			$this->pixel_install_time = $value ?: null;
		}

		/**
		 * Filters the Facebook pixel install time.
		 *
		 * @param string $pixel_install_time Facebook pixel install time in UTC seconds, or null if none set
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (int) apply_filters( 'wc_facebook_pixel_install_time', $this->pixel_install_time, $this );
	}

	/**
	 * Gets the configured JS SDK version.
	 *
	 * @return string
	 * @since 1.10.0
	 */
	public function get_js_sdk_version() {
		if ( ! is_string( $this->js_sdk_version ) ) {
			$value                = get_option( self::OPTION_JS_SDK_VERSION, '' );
			$this->js_sdk_version = is_string( $value ) ? $value : '';
		}

		/**
		 * Filters the Facebook JS SDK version.
		 *
		 * @param string $js_sdk_version Facebook JS SDK version
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (string) apply_filters( 'wc_facebook_js_sdk_version', $this->js_sdk_version, $this );
	}

	/**
	 * Gets the configured Facebook page ID.
	 *
	 * @return string
	 * @since 1.10.0
	 */
	public function get_facebook_page_id() {
		/**
		 * Filters the configured Facebook page ID.
		 *
		 * @param string $page_id the configured Facebook page ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (string) apply_filters( 'wc_facebook_page_id', get_option( self::SETTING_FACEBOOK_PAGE_ID, '' ), $this );
	}

	/**
	 * Gets the configured Facebook pixel ID.
	 *
	 * @return string
	 * @since 1.10.0
	 */
	public function get_facebook_pixel_id() {
		/**
		 * Filters the configured Facebook pixel ID.
		 *
		 * @param string $pixel_id the configured Facebook pixel ID
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (string) apply_filters( 'wc_facebook_pixel_id', get_option( self::SETTING_FACEBOOK_PIXEL_ID, '' ), $this );
	}

	/**
	 * Gets the IDs of the categories to be excluded from sync.
	 *
	 * @return int[]
	 * @since 1.10.0
	 */
	public function get_excluded_product_category_ids() {
		/**
		 * Filters the configured excluded product category IDs.
		 *
		 * @param int[] $category_ids the configured excluded product category IDs
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (array) apply_filters( 'wc_facebook_excluded_product_category_ids', get_option( self::SETTING_EXCLUDED_PRODUCT_CATEGORY_IDS, [] ), $this );
	}

	/**
	 * Gets the IDs of the tags to be excluded from sync.
	 *
	 * @return int[]
	 * @since 1.10.0
	 */
	public function get_excluded_product_tag_ids() {
		/**
		 * Filters the configured excluded product tag IDs.
		 *
		 * @param int[] $tag_ids the configured excluded product tag IDs
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (array) apply_filters( 'wc_facebook_excluded_product_tag_ids', get_option( self::SETTING_EXCLUDED_PRODUCT_TAG_IDS, [] ), $this );
	}

	/** Setter methods ************************************************************************************************/


	/**
	 * Updates the Facebook product catalog ID.
	 *
	 * @param string $value product catalog ID value
	 *
	 * @since 1.10.0
	 */
	public function update_product_catalog_id( $value ) {
		$this->product_catalog_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_PRODUCT_CATALOG_ID, $this->product_catalog_id );
	}

	/**
	 * Updates the Facebook external merchant settings ID.
	 *
	 * @param string $value external merchant settings ID value
	 *
	 * @since 1.10.0
	 */
	public function update_external_merchant_settings_id( $value ) {
		$this->external_merchant_settings_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_EXTERNAL_MERCHANT_SETTINGS_ID, $this->external_merchant_settings_id );
	}

	/**
	 * Updates the Facebook feed ID.
	 *
	 * @param string $value feed ID value
	 *
	 * @since 1.10.0
	 */
	public function update_feed_id( $value ) {
		$this->feed_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_FEED_ID, $this->feed_id );
	}

	/**
	 * Updates the Facebook upload ID.
	 *
	 * @param string $value upload ID value
	 *
	 * @since 1.11.0
	 */
	public function update_upload_id( $value ) {
		$this->upload_id = $this->sanitize_facebook_credential( $value );

		update_option( self::OPTION_UPLOAD_ID, $this->upload_id );
	}

	/**
	 * Updates the Facebook pixel install time.
	 *
	 * @param int $value pixel install time, in UTC seconds
	 *
	 * @since 1.10.0
	 */
	public function update_pixel_install_time( $value ) {
		$value = (int) $value;
		// phpcs:ignore Universal.Operators.DisallowShortTernary.Found
		$this->pixel_install_time = $value ?: null;

		// phpcs:ignore Universal.Operators.DisallowShortTernary.Found
		update_option( self::OPTION_PIXEL_INSTALL_TIME, $value ?: '' );
	}

	/**
	 * Updates the Facebook JS SDK version.
	 *
	 * @param string $value JS SDK version
	 *
	 * @since 1.10.0
	 */
	public function update_js_sdk_version( $value ) {
		$this->js_sdk_version = $this->sanitize_facebook_credential( $value );
		update_option( self::OPTION_JS_SDK_VERSION, $this->js_sdk_version );
	}


	/**
	 * Sanitizes a value that's a Facebook credential.
	 *
	 * @param string $value value to sanitize
	 *
	 * @return string
	 * @since 1.10.0
	 */
	private function sanitize_facebook_credential( $value ) {
		return wc_clean( is_string( $value ) ? $value : '' );
	}

	/**
	 * Determines whether Facebook for WooCommerce is configured.
	 *
	 * @return bool
	 * @since 1.10.0
	 */
	public function is_configured() {
		return $this->get_facebook_page_id() && $this->facebook_for_woocommerce->get_connection_handler()->is_connected();
	}

	/**
	 * Determines whether advanced matching is enabled.
	 *
	 * @return bool
	 * @since 1.10.0
	 */
	public function is_advanced_matching_enabled() {
		/**
		 * Filters whether advanced matching is enabled.
		 *
		 * @param bool $is_enabled whether advanced matching is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (bool) apply_filters( 'wc_facebook_is_advanced_matching_enabled', true, $this );
	}

	/**
	 * Determines whether product sync is enabled.
	 *
	 * @return bool
	 * @since 1.10.0
	 */
	public function is_product_sync_enabled() {
		/**
		 * Filters whether product sync is enabled.
		 *
		 * @param bool $is_enabled whether product sync is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.0
		 */
		return (bool) apply_filters( 'wc_facebook_is_product_sync_enabled', 'yes' === get_option( self::SETTING_ENABLE_PRODUCT_SYNC, 'yes' ), $this );
	}

	/**
	 * Return true if (legacy) feed generation is enabled.
	 *
	 * Feed generation for product sync is enabled by default, and generally recommended.
	 * Large stores, or stores running on shared hosting (low resources) may have issues
	 * with feed generation. This option allows those stores to disable generation to
	 * work around the issue.
	 *
	 * Note - this is temporary. In a future release, an improved feed system will be
	 * implemented, which should work well for all stores. This option will not disable
	 * the new improved implementation.
	 *
	 * @since 2.5.0
	 *
	 * @return bool
	 */
	public function is_legacy_feed_file_generation_enabled() {
		return 'yes' === get_option( self::OPTION_LEGACY_FEED_FILE_GENERATION_ENABLED, 'yes' );
	}

	/**
	 * Determines whether meta diagnosis is enabled.
	 *
	 * @return bool
	 * @since 3.4.4
	 */
	public function is_meta_diagnosis_enabled() {
		return (bool) ( 'yes' === get_option( self::SETTING_ENABLE_META_DIAGNOSIS ) );
	}

	/**
	 * Determines whether debug mode is enabled.
	 *
	 * @return bool
	 * @since 1.10.2
	 */
	public function is_debug_mode_enabled() {
		/**
		 * Filters whether debug mode is enabled.
		 *
		 * @param bool $is_enabled whether debug mode is enabled
		 * @param \WC_Facebookcommerce_Integration $integration the integration instance
		 *
		 * @since 1.10.2
		 */
		return (bool) apply_filters( 'wc_facebook_is_debug_mode_enabled', 'yes' === get_option( self::SETTING_ENABLE_DEBUG_MODE ), $this );
	}

	/**
	 * Determines whether debug mode is enabled.
	 *
	 * @return bool
	 * @since 2.6.6
	 */
	public function is_new_style_feed_generation_enabled() {
		return (bool) ( 'yes' === get_option( self::SETTING_ENABLE_NEW_STYLE_FEED_GENERATOR ) );
	}

	/**
	 * Check if logging headers is requested.
	 * For a typical troubleshooting session the request headers bring zero value except making the log unreadable.
	 * They will be disabled by default. Enabling them will require setting an option in the options table.
	 *
	 * @since 2.6.6
	 */
	public function are_headers_requested_for_debug() {
		return (bool) get_option( self::SETTING_REQUEST_HEADERS_IN_DEBUG_MODE, false );
	}

	/***
	 * Determines if the feed has been migrated from FBE 1 to FBE 1.5
	 *
	 * @return bool
	 * @since 1.11.0
	 */
	public function is_feed_migrated() {
		if ( ! is_bool( $this->feed_migrated ) ) {
			$value               = get_option( 'wc_facebook_feed_migrated', 'no' );
			$this->feed_migrated = wc_string_to_bool( $value );
		}

		return $this->feed_migrated;
	}

	/**
	 * Gets message HTML.
	 *
	 * @param string $message
	 * @param string $type
	 *
	 * @return string
	 *
	 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 */
	private function get_message_html( string $message, string $type = 'error' ): string {
		ob_start();

		printf(
			'<div class="notice is-dismissible notice-%s"><p>%s</p></div>',
			esc_attr( $type ),
			$message
		);

		return ob_get_clean();
	}

	/**
	 * Displays relevant messages to user from transients, clear once displayed.
	 */
	public function maybe_display_facebook_api_messages() {
		$error_msg = get_transient( 'facebook_plugin_api_error' );
		if ( $error_msg ) {
			$message = '<strong>' . __( 'Facebook for WooCommerce error:', 'facebook-for-woocommerce' ) . '</strong></br>' . $error_msg;
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $message );
			delete_transient( 'facebook_plugin_api_error' );
			WC_Facebookcommerce_Utils::fblog(
				$error_msg,
				[],
				true
			);
		}
		$warning_msg = get_transient( 'facebook_plugin_api_warning' );
		if ( $warning_msg ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $warning_msg, 'warning' );
			delete_transient( 'facebook_plugin_api_warning' );
		}
		$success_msg = get_transient( 'facebook_plugin_api_success' );
		if ( $success_msg ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $success_msg, 'success' );
			delete_transient( 'facebook_plugin_api_success' );
		}
		$info_msg = get_transient( 'facebook_plugin_api_info' );
		if ( $info_msg ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $info_msg, 'info' );
			delete_transient( 'facebook_plugin_api_info' );
		}
		$sticky_msg = get_transient( 'facebook_plugin_api_sticky' );
		if ( $sticky_msg ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_message_html( $sticky_msg, 'info' );
			// transient must be deleted elsewhere, or wait for timeout
		}
	}

	/**
	 * Admin Panel Options
	 *
	 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	 */
	public function admin_options() {
		$this->facebook_for_woocommerce->get_message_handler()->show_messages();

		printf(
			'<div id="integration-settings" %s>%s</div>',
			! $this->is_configured() ? 'style="display: none"' : '',
			sprintf(
				'<table class="form-table">%s</table>',
				$this->generate_settings_html( $this->get_form_fields() )
			)
		);
	}

	/**
	 * Delete product item by id.
	 *
	 * @param int $wp_id
	 *
	 * @return void
	 */
	public function delete_product_item( int $wp_id ): void {
		$fb_product_item_id = $this->get_product_fbid(
			self::FB_PRODUCT_ITEM_ID,
			$wp_id
		);
		if ( $fb_product_item_id ) {
			try {
				$pi_result = $this->facebook_for_woocommerce->get_api()->delete_product_item( $fb_product_item_id );
				WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $pi_result );
			} catch ( ApiException $e ) {
				$message = sprintf( 'There was an error trying to delete a product set item: %s', $e->getMessage() );
				WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
			}
		}
	}

	/**
	 * Filter function for woocommerce_duplicate_product_exclude_meta filter.
	 *
	 * @param array $to_delete
	 *
	 * @return array
	 */
	public function fb_duplicate_product_reset_meta( array $to_delete ): array {
		$to_delete[] = self::FB_PRODUCT_ITEM_ID;
		$to_delete[] = self::FB_PRODUCT_GROUP_ID;

		return $to_delete;
	}

	/**
	 * Helper function to update FB visibility.
	 *
	 * @param int|WC_Product $product_id product ID or product object
	 * @param string         $visibility visibility
	 */
	public function update_fb_visibility( $product_id, $visibility ) {
		// bail if the plugin is not configured properly
		if ( ! $this->is_configured() || ! $this->get_product_catalog_id() ) {
			return;
		}

		$product = $product_id instanceof WC_Product ? $product_id : wc_get_product( $product_id );

		// bail if product isn't found
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$should_set_visible = self::FB_SHOP_PRODUCT_VISIBLE === $visibility;
		if ( $product->is_type( 'variation' ) ) {
			Products::set_product_visibility( $product, $should_set_visible );
			$this->facebook_for_woocommerce->get_products_sync_handler()->create_or_update_products( [ $product->get_id() ] );
		} elseif ( $product->is_type( 'variable' ) ) {
			// parent product
			Products::set_product_visibility( $product, $should_set_visible );
			// we should not add the parent product ID to the array of product IDs to be
			// updated because product groups, which are used to represent the parent product
			// for variable products, don't have the visibility property on Facebook
			$product_ids = [];
			// set visibility for all children
			foreach ( $product->get_children() as $index => $id ) {
				$product = wc_get_product( $id );
				if ( ! $product instanceof WC_Product ) {
					continue;
				}
				Products::set_product_visibility( $product, $should_set_visible );
				$product_ids[] = $product->get_id();
			}
			// sync product with all variations
			$this->facebook_for_woocommerce->get_products_sync_handler()->create_or_update_products( $product_ids );
		} else {
			$fb_product_item_id = $this->get_product_fbid( self::FB_PRODUCT_ITEM_ID, $product->get_id() );
			if ( ! $fb_product_item_id ) {
				\WC_Facebookcommerce_Utils::fblog( $fb_product_item_id . " doesn't exist but underwent a visibility transform.", [], true );
				return;
			}
			try {
				$set_visibility = $this->facebook_for_woocommerce->get_api()->update_product_item( $fb_product_item_id, [ 'visibility' => $visibility ] );
				if ( $set_visibility->success ) {
					Products::set_product_visibility( $product, $should_set_visible );
				}
			} catch ( ApiException $e ) {
				$message = sprintf( 'There was an error trying to update product item: %s', $e->getMessage() );
				WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $message );
			}
		}
	}

	/**
	 * Sync product upon quick or bulk edit save action.
	 *
	 * @param \WC_Product $product product object
	 *
	 * @internal
	 */
	public function on_quick_and_bulk_edit_save( $product ) {
		// bail if not a product or product is not enabled for sync
		if ( ! $product instanceof \WC_Product || ! Products::published_product_should_be_synced( $product ) ) {
			return;
		}

		$wp_id      = $product->get_id();
		$visibility = get_post_status( $wp_id ) === 'publish' ? self::FB_SHOP_PRODUCT_VISIBLE : self::FB_SHOP_PRODUCT_HIDDEN;

		if ( self::FB_SHOP_PRODUCT_VISIBLE === $visibility ) {
			// - new status is 'publish' regardless of old status, sync to Facebook
			$this->on_product_publish( $wp_id );
		} else {
			// - product never published to Facebook, new status is not publish
			// - product new status is not publish but may have been published before
			$this->update_fb_visibility( $product, $visibility );
		}
	}

	/**
	 * Gets Facebook product ID from meta or from Facebook API.
	 *
	 * @param string                   $fbid_type ID type (group or item)
	 * @param int                      $wp_id post ID
	 * @param WC_Facebook_Product|null $woo_product product
	 *
	 * @return string facebook product id or an empty string
	 */
	public function get_product_fbid( string $fbid_type, int $wp_id, $woo_product = null ) {
		$fb_id = WC_Facebookcommerce_Utils::get_fbid_post_meta( $wp_id, $fbid_type );
		if ( $fb_id ) {
			return $fb_id;
		}
		if ( ! $woo_product ) {
			$woo_product = new WC_Facebook_Product( $wp_id );
		}
		$products = WC_Facebookcommerce_Utils::get_product_array( $woo_product );
		// if the product with ID equal to $wp_id is variable, $woo_product will be the first child
		$woo_product = new WC_Facebook_Product( current( $products ) );

		$fb_retailer_id = WC_Facebookcommerce_Utils::get_fb_retailer_id( $woo_product );

		try {
			$response = $this->facebook_for_woocommerce->get_api()->get_product_facebook_ids(
				$this->get_product_catalog_id(),
				$fb_retailer_id
			);

			if ( $response->data && $response->data[0] && $response->data[0]['id'] ) {
				$fb_id = self::FB_PRODUCT_GROUP_ID === $fbid_type
					? $response->data[0]['product_group']['id']
					: $response->data[0]['id'];
				update_post_meta( $wp_id, $fbid_type, $fb_id );
				return $fb_id;
			} elseif ( $response->id ) {
				$fb_id = self::FB_PRODUCT_GROUP_ID === $fbid_type
					? $response->get_facebook_product_group_id()
					: $response->id;
				update_post_meta( $wp_id, $fbid_type, $fb_id );
				return $fb_id;
			}
		} catch ( Exception $e ) {
			/* @TODO: Log exception. */
			WC_Facebookcommerce_Utils::log_with_debug_mode_enabled( $e->getMessage() );
			$this->display_error_message(
				sprintf(
				/* translators: Placeholders %1$s - original error message from Facebook API */
					esc_html__( 'There was an issue connecting to the Facebook API: %s', 'facebook-for-woocommerce' ),
					$e->getMessage()
				)
			);
		}

		return null;
	}

	/**
	 * Display test result.
	 **/
	public function ajax_display_test_result() {
		WC_Facebookcommerce_Utils::check_woo_ajax_permissions( 'test result', true );
		check_ajax_referer( 'wc_facebook_settings_jsx' );
		$response  = [
			'pass' => 'true',
		];
		$test_pass = get_option( 'fb_test_pass', null );
		if ( ! isset( $test_pass ) ) {
			$response['pass'] = 'in progress';
		} elseif ( 0 === $test_pass ) {
			$response['pass']        = 'false';
			$response['debug_info']  = get_transient( 'facebook_plugin_test_fail' );
			$response['stack_trace'] =
				get_transient( 'facebook_plugin_test_stack_trace' );
			$response['stack_trace'] =
				preg_replace( "/\n/", '<br>', $response['stack_trace'] );
			delete_transient( 'facebook_plugin_test_fail' );
			delete_transient( 'facebook_plugin_test_stack_trace' );
		}
		delete_option( 'fb_test_pass' );
		printf( json_encode( $response ) );
		wp_die();
	}

	/**
	 * Init WhatsApp Utility Event Processor.
	 *
	 * @return void
	 */
	public function load_whatsapp_utility_event_processor() {
		// Attempt to load WhatsApp Utility Event Processor
		include_once 'facebook-commerce-whatsapp-utility-event.php';
		if ( class_exists( 'WC_Facebookcommerce_Whatsapp_Utility_Event' ) ) {
			if ( ! isset( $this->wa_utility_event_processor ) ) {
				$this->wa_utility_event_processor = new WC_Facebookcommerce_Whatsapp_Utility_Event( $this );
			}
		}
	}
}