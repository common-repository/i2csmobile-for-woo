<?php
/**
 * REST API Store controller
 *
 * Handles requests to the /store endpoint.
 *
 * @author   i2CSSolutions
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Store controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_I2cs_REST_Store_Controller extends WC_I2cs_REST_Posts_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'i2cs/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'store';

	
	/**
	 * Initialize store actions.
	 */
	public function __construct() {
		
	}

	/**
	 * Register the routes for store.
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/countries', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_countries' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			)
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/currency', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_base_currency' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			)
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/banner', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_banner' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			)
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/states', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_states_by_country_code' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			)
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/language', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_language' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			)
		) );
	}
	
	/**
	 * Check whether a given request has permission to read order notes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}
	
	/**
	 * Get all allowed countries or shipping countries.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_countries( $request ) {

		$data = new WC_Countries();
		if(isset($request['shipping']) && $request['shipping'] == "true"){
			$data = $data->get_shipping_countries();
		}else{
			$data = $data->get_allowed_countries();
		}
		
		return $data;
	}
	
	/**
	 * Get states by country code.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_states_by_country_code( $request ) {
		$data = array();
		if(isset($request['cc'])){
			$data = new WC_Countries();
			$data = $data->get_states($request['cc']);
		}
		
		return $data;
	}

	/**
	 * Get base currency.
	 *
	 * @param WP_REST_Request $request
	 * @return string
	 */
	public function get_base_currency( $request ) {

		$data = get_woocommerce_currency_symbol();
		
		return $data;
	}
	
	/**
	 * Get banner details by id.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function get_banner($request){
		$slides = array();
		
		if(!isset($_REQUEST['id'])){
			return $slides;
		}
		
		$temp_id = absint( $_REQUEST['id'] );
		
		if ( class_exists( 'MetaSliderPlugin' ) ) {
			$slider = new MetaSlider($temp_id, array());
			$query = $slider->get_slides();
		
			while ( $query->have_posts() ) {
				$query->next_post();
				$imageHelper = new MetaSliderImageHelper( $query->post->ID, $slider->settings['width'], $slider->settings['height'], 'disabled' );
				$tmp_obj = array();
				$tmp_obj['title'] = $query->post->post_name;
				$tmp_obj['link'] = __( get_post_meta( $query->post->ID, 'ml-slider_url', true ) );
				$tmp_obj['image'] = $imageHelper->get_image_url();
				
				$slides[] = $tmp_obj;
			}
		}

		return $slides;
	}
	
	/**
	 * Set store language.
	 *
	 * @param WP_REST_Request $request
	 * @return array
	 */
	public function set_language( $request ) {
		if(isset($request['language'])){
			// needs additional 3rd party plugin here. Set the two word language code here. ex: 'en' 
 		}
		
		return true;
	}
}
