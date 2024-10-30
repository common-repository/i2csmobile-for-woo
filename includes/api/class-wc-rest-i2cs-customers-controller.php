<?php
/**
 * REST API Customers controller
 *
 * Handles requests to the /customers endpoint.
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Customers controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Controller
 */
class WC_I2cs_REST_Customers_Controller extends WC_REST_Controller {

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
	protected $rest_base = 'customers';

	/**
	 * Register the routes for customers.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'email' => array(
						'required' => true,
					),
					'username' => array(
						'required' => 'no' === get_option( 'woocommerce_registration_generate_username', 'yes' ),
					),
					'password' => array(
						'required' => 'no' === get_option( 'woocommerce_registration_generate_password', 'no' ),
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/login', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'login' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/auth/handler', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'auth' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		register_rest_route( $this->namespace, '/auth/handler/getuser', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'getuser' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/api2/auth/handler/success', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'print_success' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
			register_rest_route( $this->namespace, '/' . $this->rest_base . '/api2/auth/handler/error', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'print_error' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/logout', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'logout' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/geolocationaddress', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'geolocationaddress' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' )
			)
		) );
	}

	/**
	 * Check whether a given request has permission to read customers.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access create customers.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access to read a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		return true;
	}

	/**
	 * Check if a given request has access update a customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function update_item_permissions_check( $request ) {
		$id = (int) $request['id'];

		if ( ! wc_rest_check_user_permissions( 'edit', $id ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', __( 'Sorry, you are not allowed to edit this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}
	
	/**
	 * Method to send Get request to url.
	 *
	 * @param  String $url The url.
	 * @return data
	 */
	public function doCurl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = json_decode(curl_exec($ch), true);
		curl_close($ch);
		return $data;
	}
	
	/**
	 * Gets address from current GPS location.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return Address
	 */
	public function geolocationaddress( $request ) {
		
		$url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $request['lat'] . "," .  $request['lng'];
		$google_data = $this->doCurl($url);
		
		$address_line1 = array();
		$address_line2 = array();
		
		if(!empty($google_data) && $google_data['status'] == "OK"){
			$address_components = $google_data['results'][0]['address_components'];
			foreach($address_components as $address_component){
				if(in_array('postal_code', $address_component['types'])){
					$postal = $address_component['short_name'];
				} else if(in_array('country', $address_component['types'])){
					$country = $address_component['short_name'];
				}else if(in_array('locality', $address_component['types'])){
					$city = $address_component['long_name'];
				}else if(in_array('street_number', $address_component['types'])){
					$address_line1[] = $address_component['long_name'];
				}else if(in_array('route', $address_component['types'])){
					$address_line1[] = $address_component['long_name'];
				}else if(in_array('administrative_area_level_1', $address_component['types'])){
					$state = $address_component['long_name'];
				}else if(in_array('sublocality', $address_component['types'])){
					$address_line2[] = $address_component['long_name'];
				}
			}
		}
		
		$data = array(
					'address_line1' => join(', ', $address_line1),
					'address_line2' => join(', ', $address_line2),
					'city' => $city,
					'postalcode' => $postal,
					'country' => $country,
					'state' => $state
				);
		
		return $data;
	}

	/**
	 * Logs in a customer by validating by email and password.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return Customer
	 */
	public function login( $request ) {
		$data = wp_authenticate_email_password(null, $request['email'], $request['password']);
		
		if(isset($data->data)){
			$customer = get_user_by( 'id', $data->ID );
			$response = $this->prepare_item_for_response( $customer, $request );
			$response = rest_ensure_response( $response );
			
			wc_set_customer_auth_cookie( $data->ID );
			
			return $response;
		}
		
		return $data;
	}
	
	/**
	 * Gets customer details by id.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return Customer
	 */
	public function getuser( $request ) {
		$customer = get_user_by( 'id', $request['id'] );
		$response = $this->prepare_item_for_response( $customer, $request );
		$response = rest_ensure_response( $response );
		
		wc_set_customer_auth_cookie( $data->ID );
		
		return $response;
	}
	
	/**
	 * Prints success messages
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return Message
	 */
	public function print_success( $id ) {
		header("Content-Type: text/html");
		if(isset($id)){
			echo "<center><div style=\"font-family: 'Open Sans', sans-serif; font-size: 18px; color: #666666;\">Done</div></center>";
		}
		
		exit();
	}
	
	/**
	 * Prints error messages
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return Message
	 */
	public function print_error( $error ) {
		header("Content-Type: text/html");
		if(isset($error))
			echo "<center><div style=\"font-family: 'Open Sans', sans-serif; font-size: 18px; color: #666666;\">" . $_REQUEST['error'] . "</div></center>";
		else
			echo "<center><div style=\"font-family: 'Open Sans', sans-serif; font-size: 18px; color: #666666;\">Error</div></center>";
		
		exit();
	}

	/**
	 * Authenticate user by external provider.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return Redirection
	 */
	public function auth($request){
		header("Content-Type: text/html");
		require_once( "auth/hybridauth/hybridauth/Hybrid/Auth.php" );
		require_once( "auth/hybridauth/hybridauth/Hybrid/thirdparty/Facebook/autoload.php" );

		$fb_app_id = esc_attr( get_option('i2csmobile_facebook_app_id') );
		$fb_secret = esc_attr( get_option('i2csmobile_facebook_app_secret') );

		$google_app_id = esc_attr( get_option('i2csmobile_google_id') );
		$google_secret = esc_attr( get_option('i2csmobile_google_secret') );
		
		$twitter_app_id = esc_attr( get_option('i2csmobile_twitter_key') );
		$twitter_secret = esc_attr( get_option('i2csmobile_twitter_secret') );

		$base = get_site_url() . '/wp-content/plugins/i2csmobile-for-woo/includes/api/auth/hybridauth/hybridauth/';
		$config = array(
			"base_url" => $base,
			"providers" => array(
				"Google" => array(
					"enabled" => true,
					"keys" => array("id" => $google_app_id, "secret" => $google_secret),
				),
				"Facebook" => array(
					"enabled" => true,
					"keys" => array("id" => $fb_app_id, "secret" => $fb_secret),
					"trustForwarded" => false,
					"scope" => "email"
				),
				"Twitter" => array(
					"enabled" => true,
					"keys" => array("key" => $twitter_app_id, "secret" => $twitter_secret),
					"includeEmail" => true
				)
			),
			"debug_mode" => false,
			"debug_file" => "",
			);
		
 
		try{
			$hybridauth = new Hybrid_Auth( $config );
			
			if(isset($request['provider']) && isset($request['logout'])){
				$provider = $request['provider'];
				$auth = $hybridauth->authenticate($provider);
				$auth->logout();
			}else if(!isset($request['provider'])){
				$json['error'] = "No provider selected";
			}else{
				$provider = $request['provider'];
				$auth = $hybridauth->authenticate($provider);
				$user_profile = $auth->getUserProfile();
				
				#FIX
				$hybridauth_session_data = $hybridauth->getSessionData();

				$customer = get_user_by( 'email', $user_profile->email );
				if($customer){
					// login user
					wc_set_customer_auth_cookie( $customer->ID );
					$customer_id = $customer->ID;
				}else{
					// create new user and login
					$request['first_name'] = $user_profile->firstName;
					$request['last_name'] = $user_profile->lastName;
					$request['email'] = $user_profile->email;
					$request['username'] = $user_profile->email;
					
					// Sets the username.
					$request['username'] = ! empty( $request['username'] ) ? $request['username'] : '';

					// Sets the password.
					$request['password'] = md5(mt_rand());

					// Create customer.
					$customer_id = wc_create_new_customer( $request['email'], $request['username'], $request['password'] );
					
					if ( is_wp_error( $customer_id ) ) {
						return $customer_id;
					}

					$customer = get_user_by( 'id', $customer_id );

					$this->update_additional_fields_for_object( $customer, $request );

					// Add customer data.
					$this->update_customer_meta_fields( $customer, $request );

					/**
					 * Fires after a customer is created or updated via the REST API.
					 *
					 * @param WP_User         $customer  Data used to create the customer.
					 * @param WP_REST_Request $request   Request object.
					 * @param boolean         $creating  True when creating customer, false when updating customer.
					 */
					do_action( 'woocommerce_rest_insert_customer', $customer, $request, true );
					
					wc_set_customer_auth_cookie( $customer_id );
				}
			}
		}
		catch( Exception $e ){
		   $json['error'] = $e->getMessage();
		}
		
		if(empty($customer_id)){
			header( 'Location: ' . get_site_url() . '/wp-json/i2cs/v1/customers/api2/auth/handler/error?error=' . $json['error'], true, 301 );
		}else{
			header( 'Location: ' . get_site_url() . '/wp-json/i2cs/v1/customers/api2/auth/handler/success?id=' . $customer_id . '&s=' . $hybridauth_session_data, true, 301 );
		}
		exit();
	}
	
	/**
	 * Logs out and clears all session data related to customer.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true
	 */
	public function logout( $request ) {
		wp_logout();	
		return true;
	}
	
	/**
	 * Create a single customer.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'woocommerce_rest_customer_exists', __( 'Cannot create existing resource.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Sets the username.
		$request['username'] = ! empty( $request['username'] ) ? $request['username'] : '';

		// Sets the password.
		$request['password'] = ! empty( $request['password'] ) ? $request['password'] : '';

		// Create customer.
		$customer_id = wc_create_new_customer( $request['email'], $request['username'], $request['password'] );
		
		if ( is_wp_error( $customer_id ) ) {
			return $customer_id;
		}

		$customer = get_user_by( 'id', $customer_id );

		$this->update_additional_fields_for_object( $customer, $request );

		// Add customer data.
		$this->update_customer_meta_fields( $customer, $request );

		/**
		 * Fires after a customer is created or updated via the REST API.
		 *
		 * @param WP_User         $customer  Data used to create the customer.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating customer, false when updating customer.
		 */
		do_action( 'woocommerce_rest_insert_customer', $customer, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $customer, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $customer_id ) ) );

		return $response;
	}

	/**
	 * Update a single user.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$id       = (int) $request['id'];
		$customer = get_userdata( $id );

		if ( ! $customer ) {
			return new WP_Error( 'woocommerce_rest_invalid_id', __( 'Invalid resource id.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $request['email'] ) && email_exists( $request['email'] ) && $request['email'] !== $customer->user_email ) {
			return new WP_Error( 'woocommerce_rest_customer_invalid_email', __( 'Email address is invalid.', 'woocommerce' ), array( 'status' => 400 ) );
		}

		if ( ! empty( $request['username'] ) && $request['username'] !== $customer->user_login ) {
			return new WP_Error( 'woocommerce_rest_customer_invalid_argument', __( "Username isn't editable.", 'woocommerce' ), array( 'status' => 400 ) );
		}

		// Customer email.
		if ( isset( $request['email'] ) ) {
			wp_update_user( array( 'ID' => $customer->ID, 'user_email' => sanitize_email( $request['email'] ) ) );
		}

		// Customer password.
		if ( isset( $request['password'] ) ) {
			wp_update_user( array( 'ID' => $customer->ID, 'user_pass' => wc_clean( $request['password'] ) ) );
		}

		$this->update_additional_fields_for_object( $customer, $request );

		// Update customer data.
		$this->update_customer_meta_fields( $customer, $request );

		/**
		 * Fires after a customer is created or updated via the REST API.
		 *
		 * @param WP_User         $customer  Data used to create the customer.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating customer, false when updating customer.
		 */
		do_action( 'woocommerce_rest_insert_customer', $customer, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $customer, $request );
		$response = rest_ensure_response( $response );
		return $response;
	}

	/**
	 * Prepare a single customer output for response.
	 *
	 * @param WP_User $customer Customer object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $response Response data.
	 */
	public function prepare_item_for_response( $customer, $request ) {
		$last_order = wc_get_customer_last_order( $customer->ID );

		$data = array(
			'id'               => $customer->ID,
			'date_created'     => wc_rest_prepare_date_response( $customer->user_registered ),
			'date_modified'    => $customer->last_update ? wc_rest_prepare_date_response( date( 'Y-m-d H:i:s', $customer->last_update ) ) : null,
			'email'            => $customer->user_email,
			'first_name'       => $customer->first_name,
			'last_name'        => $customer->last_name,
			'username'         => $customer->user_login,
			'last_order'       => array(
				'id'   => is_object( $last_order ) ? $last_order->id : null,
				'date' => is_object( $last_order ) ? wc_rest_prepare_date_response( $last_order->post->post_date_gmt ) : null
			),
			'orders_count'     => wc_get_customer_order_count( $customer->ID ),
			'total_spent'      => wc_format_decimal( wc_get_customer_total_spent( $customer->ID ), 2 ),
			'avatar_url'       => wc_get_customer_avatar_url( $customer->customer_email ),
			'billing'          => array(
				'first_name' => $customer->billing_first_name,
				'last_name'  => $customer->billing_last_name,
				'company'    => $customer->billing_company,
				'address_1'  => $customer->billing_address_1,
				'address_2'  => $customer->billing_address_2,
				'city'       => $customer->billing_city,
				'state'      => $customer->billing_state,
				'postcode'   => $customer->billing_postcode,
				'country'    => $customer->billing_country,
				'email'      => $customer->billing_email,
				'phone'      => $customer->billing_phone,
			),
			'shipping'         => array(
				'first_name' => $customer->shipping_first_name,
				'last_name'  => $customer->shipping_last_name,
				'company'    => $customer->shipping_company,
				'address_1'  => $customer->shipping_address_1,
				'address_2'  => $customer->shipping_address_2,
				'city'       => $customer->shipping_city,
				'state'      => $customer->shipping_state,
				'postcode'   => $customer->shipping_postcode,
				'country'    => $customer->shipping_country,
			),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $customer ) );

		/**
		 * Filter customer data returned from the REST API.
		 *
		 * @param WP_REST_Response $response  The response object.
		 * @param WP_User          $customer  User object used to create response.
		 * @param WP_REST_Request  $request   Request object.
		 */
		return apply_filters( 'woocommerce_rest_prepare_customer', $response, $customer, $request );
	}

	/**
	 * Update customer meta fields.
	 *
	 * @param WP_User $customer
	 * @param WP_REST_Request $request
	 */
	protected function update_customer_meta_fields( $customer, $request ) {
		$schema = $this->get_item_schema();

		// Customer first name.
		if ( isset( $request['first_name'] ) ) {
			update_user_meta( $customer->ID, 'first_name', wc_clean( $request['first_name'] ) );
		}

		// Customer last name.
		if ( isset( $request['last_name'] ) ) {
			update_user_meta( $customer->ID, 'last_name', wc_clean( $request['last_name'] ) );
		}

		// Customer billing address.
		if ( isset( $request['billing'] ) ) {
			foreach ( array_keys( $schema['properties']['billing']['properties'] ) as $address ) {
				if ( isset( $request['billing'][ $address ] ) ) {
					update_user_meta( $customer->ID, 'billing_' . $address, wc_clean( $request['billing'][ $address ] ) );
				}
			}
		}

		// Customer shipping address.
		if ( isset( $request['shipping'] ) ) {
			foreach ( array_keys( $schema['properties']['shipping']['properties'] ) as $address ) {
				if ( isset( $request['shipping'][ $address ] ) ) {
					update_user_meta( $customer->ID, 'shipping_' . $address, wc_clean( $request['shipping'][ $address ] ) );
				}
			}
		}
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_User $customer Customer object.
	 * @return array Links for the given customer.
	 */
	protected function prepare_links( $customer ) {
		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $customer->ID ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		return $links;
	}

	/**
	 * Get the Customer's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'customer',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the customer was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the customer was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'email' => array(
					'description' => __( 'The email address for the customer.', 'woocommerce' ),
					'type'        => 'string',
					'format'      => 'email',
					'context'     => array( 'view', 'edit' ),
				),
				'first_name' => array(
					'description' => __( 'Customer first name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'last_name' => array(
					'description' => __( 'Customer last name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'username' => array(
					'description' => __( 'Customer login name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_user',
					),
				),
				'password' => array(
					'description' => __( 'Customer password.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
				'last_order' => array(
					'description' => __( 'Last order data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id' => array(
							'description' => __( 'Last order ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'date' => array(
							'description' => __( 'UTC DateTime of the customer last order.', 'woocommerce' ),
							'type'        => 'date-time',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'orders_count' => array(
					'description' => __( 'Quantity of orders made by the customer.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_spent' => array(
					'description' => __( 'Total amount spent.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'avatar_url' => array(
					'description' => __( 'Avatar URL.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing' => array(
					'description' => __( 'List of billing address data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties' => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address line 1.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address line 2.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'ISO code of the country.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'email' => array(
							'description' => __( 'Email address.', 'woocommerce' ),
							'type'        => 'string',
							'format'      => 'email',
							'context'     => array( 'view', 'edit' ),
						),
						'phone' => array(
							'description' => __( 'Phone number.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'shipping' => array(
					'description' => __( 'List of shipping address data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties' => array(
						'first_name' => array(
							'description' => __( 'First name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'last_name' => array(
							'description' => __( 'Last name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'company' => array(
							'description' => __( 'Company name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_1' => array(
							'description' => __( 'Address line 1.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'address_2' => array(
							'description' => __( 'Address line 2.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'city' => array(
							'description' => __( 'City name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'state' => array(
							'description' => __( 'ISO code or name of the state, province or district.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'postcode' => array(
							'description' => __( 'Postal code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'country' => array(
							'description' => __( 'ISO code of the country.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get role names.
	 *
	 * @return array
	 */
	protected function get_role_names() {
		global $wp_roles;

		return array_keys( $wp_roles->role_names );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['context']['default'] = 'view';

		$params['exclude'] = array(
			'description'        => __( 'Ensure result set excludes specific ids.', 'woocommerce' ),
			'type'               => 'array',
			'default'            => array(),
			'sanitize_callback'  => 'wp_parse_id_list',
		);
		$params['include'] = array(
			'description'        => __( 'Limit result set to specific ids.', 'woocommerce' ),
			'type'               => 'array',
			'default'            => array(),
			'sanitize_callback'  => 'wp_parse_id_list',
		);
		$params['offset'] = array(
			'description'        => __( 'Offset the result set by a specific number of items.', 'woocommerce' ),
			'type'               => 'integer',
			'sanitize_callback'  => 'absint',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['order'] = array(
			'default'            => 'asc',
			'description'        => __( 'Order sort attribute ascending or descending.', 'woocommerce' ),
			'enum'               => array( 'asc', 'desc' ),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['orderby'] = array(
			'default'            => 'name',
			'description'        => __( 'Sort collection by object attribute.', 'woocommerce' ),
			'enum'               => array(
				'id',
				'include',
				'name',
				'registered_date',
			),
			'sanitize_callback'  => 'sanitize_key',
			'type'               => 'string',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['email'] = array(
			'description'        => __( 'Limit result set to resources with a specific email.', 'woocommerce' ),
			'type'               => 'string',
			'format'             => 'email',
			'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['role'] = array(
			'description'        => __( 'Limit result set to resources with a specific role.', 'woocommerce' ),
			'type'               => 'string',
			'default'            => 'customer',
			'enum'               => array_merge( array( 'all' ), $this->get_role_names() ),
			'validate_callback'  => 'rest_validate_request_arg',
		);
		return $params;
	}
}
