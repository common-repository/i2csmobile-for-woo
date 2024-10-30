<?php
/*
Plugin Name: WooCommerce Mobile App Source Code by i2CSMobile
Plugin URI: http://i2csmobile.com
Description: i2CSMobile API plugin connector for WooCommerce backend. Please contact support@i2csmobile.com to get the source for the ionic cordova mobile app.
Author: i2CS Solutions
Author URI: http://i2csmobile.com
Version: 1.7.1
*/
if ( ! class_exists( 'WC_I2CSMobile' ) ) {
	
	/**
	 * Localisation
	 **/
	load_plugin_textdomain( 'wc_i2csmobile', false, dirname( plugin_basename( __FILE__ ) ) . '/' );

	class WC_I2CSMobile {
		/**
		 * Notices (array)
		 * @var array
		 */
		public $notices = array();

		/**
		 * Constructor.
		 */
		public function __construct() {

			// called after all plugins have loaded
			add_action( 'plugins_loaded', array( &$this, 'plugins_loaded' ) );
			
			// called only after woocommerce has finished loading
			add_action( 'woocommerce_init', array( &$this, 'woocommerce_loaded' ) );
			
			// indicates we are running the admin
			if ( is_admin() ) {
				add_action( 'admin_menu', array( $this, 'test_plugin_setup_menu' ) );
				
				add_action( 'admin_notices', array( $this, 'admin_notices' ), 15 );
			}
			
			// indicates we are being served over ssl
			if ( is_ssl() ) {
				// ...
			}
		}
		
		/**
		 * Load files only after woocommerce is loaded
		 */
		public function woocommerce_loaded() {
			include( 'includes/abstract/abstract-wc-rest-posts-controller.php' );
			include( 'includes/abstract/abstract-wc-rest-terms-controller.php' );
			
			include( 'includes/class-wc-api.php' );
			
			new I2CS_API();
		}
				
		/**
		 * Do environment and dependency checking
		 */
		public function plugins_loaded() {
			// check if woocommerce is activated
			if ( version_compare( WC_VERSION, 1, '<' )) {
				$woocommerceMissing = sprintf( '<a href="%s" class="%s" aria-label="%s" data-title="%s">%s</a>',
					esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=woocommerce' .
						'&TB_iframe=true&width=600&height=550' ) ),
					"thickbox open-plugin-details-modal",
					esc_attr( sprintf( __( 'More information about %s' ), "woocommerce" ) ),
					esc_attr( "woocommerce" ),
					__( 'View details' )
				);
				$this->add_admin_notice( 'no_woocommerce', 'error', "i2CSMobile API requires WooCommerce to be activated." . $woocommerceMissing );
			}
							
			// check if woocommerce is activated
			if ( ! class_exists( 'MetaSliderPlugin' ) ) {
				$metaSliderMissing = sprintf( '<a href="%s" class="%s" aria-label="%s" data-title="%s">%s</a>',
					esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=ml-slider' .
						'&TB_iframe=true&width=600&height=550' ) ),
					"thickbox open-plugin-details-modal",
					esc_attr( sprintf( __( 'More information about %s' ), "ml-slider" ) ),
					esc_attr( "ml-slider" ),
					__( 'View details' )
				);
				$this->add_admin_notice( 'no_ml_slider', 'notice notice-warning', "i2CSMobile API banners requires Meta Slider plugin to be activated. " . $metaSliderMissing );
			}
		}
		
		/**
		 * Allow this class and other classes to add slug keyed notices (to avoid duplication)
		 */
		public function add_admin_notice( $slug, $class, $message ) {
			$this->notices[ $slug ] = array(
				'class'   => $class,
				'message' => $message
			);
		}
		
		/**
		 * Display any notices we've collected thus far (e.g. for connection, disconnection)
		 */
		public function admin_notices() {
			foreach ( (array) $this->notices as $notice_key => $notice ) {
				echo "<div class='" . esc_attr( $notice['class'] ) . "'><p>";
				echo $notice['message'];
				echo "</p></div>";
			}
		}
		
		public function test_plugin_setup_menu(){
			add_menu_page( 'i2CSMobile Settings', 'i2CS Mobile', 'manage_options', 'i2csmobile', array( &$this, 'i2csmobile_settings_init' ) );
			add_action( 'admin_init', array( &$this, 'register_i2csmobile_plugin_settings' ) );
		}
				 
		public function i2csmobile_settings_init(){
			?>
			<div class="wrap">
			<h1>i2CS Mobile Settings</h1>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'i2csmobile-plugin-settings-group' ); ?>
				<?php do_settings_sections( 'i2csmobile-plugin-settings-group' ); ?>
				<table class="widefat">
					<thead><tr><th colspan="2">Facebook Auth</th></tr></thead>
					<tbody>
						<tr valign="top">
						<th scope="row">Facebook App ID</th>
						<td><input type="text" name="i2csmobile_facebook_app_id" value="<?php echo esc_attr( get_option('i2csmobile_facebook_app_id') ); ?>" /></td>
						</tr>
						 
						<tr valign="top">
						<th scope="row">Facebook App Secret</th>
						<td><input type="text" name="i2csmobile_facebook_app_secret" value="<?php echo esc_attr( get_option('i2csmobile_facebook_app_secret') ); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th colspan="2">Obtain above information from Facebook developers. <a href="https://developers.facebook.com/" target="_blank">Click here</a> for help</th>
						</tr>
					</tbody>

					<thead><tr><th colspan="2">Google Auth</th></tr></thead>
					<tbody>
						<tr valign="top">
						<th scope="row">Google App ID</th>
						<td><input type="text" name="i2csmobile_google_id" value="<?php echo esc_attr( get_option('i2csmobile_google_id') ); ?>" /></td>
						</tr>
						 
						<tr valign="top">
						<th scope="row">Google App Secret</th>
						<td><input type="text" name="i2csmobile_google_secret" value="<?php echo esc_attr( get_option('i2csmobile_google_secret') ); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th colspan="2">Obtain above information from Google developers console. <a href="https://developers.google.com/" target="_blank">Click here</a> for help</th>
						</tr>
					</tbody>

					<thead><tr><th colspan="2">Twitter Auth</th></tr></thead>
					<tbody>
						<tr valign="top">
						<th scope="row">Twitter App Key</th>
						<td><input type="text" name="i2csmobile_twitter_key" value="<?php echo esc_attr( get_option('i2csmobile_twitter_key') ); ?>" /></td>
						</tr>
						 
						<tr valign="top">
						<th scope="row">Twitter App Secret</th>
						<td><input type="text" name="i2csmobile_twitter_secret" value="<?php echo esc_attr( get_option('i2csmobile_twitter_secret') ); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th colspan="2"></th>
						</tr>
					</tbody>
				</table>
				
				<?php submit_button(); ?>

			</form>
			</div>
			<?php
		}
		
		public function register_i2csmobile_plugin_settings() {
			//register our settings
			register_setting( 'i2csmobile-plugin-settings-group', 'i2csmobile_facebook_app_id' );
			register_setting( 'i2csmobile-plugin-settings-group', 'i2csmobile_facebook_app_secret' );

			register_setting( 'i2csmobile-plugin-settings-group', 'i2csmobile_google_id' );
			register_setting( 'i2csmobile-plugin-settings-group', 'i2csmobile_google_secret' );

			register_setting( 'i2csmobile-plugin-settings-group', 'i2csmobile_twitter_key' );
			register_setting( 'i2csmobile-plugin-settings-group', 'i2csmobile_twitter_secret' );
		}
	}

	// finally instantiate our plugin class and add it to the set of globals
	$GLOBALS['wc_i2csmobile'] = new WC_I2CSMobile();
}