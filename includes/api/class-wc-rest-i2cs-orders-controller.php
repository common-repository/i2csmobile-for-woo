<?php
/**
 * REST API Orders controller
 *
 * Handles requests to the /orders endpoint.
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
 * REST API Orders controller class.
 *
 * @package WooCommerce/API
 * @extends WC_REST_Posts_Controller
 */
class WC_I2cs_REST_Orders_Controller extends WC_I2cs_REST_Posts_Controller {

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
	protected $rest_base = 'orders';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';

	/**
	 * Initialize orders actions.
	 */
	public function __construct() {
		add_filter( "woocommerce_rest_{$this->post_type}_query", array( $this, 'query_args' ), 10, 2 );
		add_filter( 'rest_authentication_errors', array( $this, 'check_authentication_error' ) );
	}

	public function check_authentication_error(){
		$origin = get_http_origin();
		@header( 'Access-Control-Allow-Origin: ' .  $origin );
		@header( 'Access-Control-Allow-Credentials: true' );
		return true;
	}
	
	/**
	 * Register the routes for orders.
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_my_orders' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_item' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) ),
				),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}
	
	/**
	 * Returns current logged in customer's orders.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function get_my_orders( $request ) {
		$array = array();
		
		if(!is_user_logged_in()){
			return $array;
		}
		
		 $current_page = 1;
		 if(isset($request['page'])){
			$current_page = absint($request['page']);
		 }
		
		$customer_orders = wc_get_orders( apply_filters( 'woocommerce_my_account_my_orders_query', array( 'customer' => get_current_user_id(), 'page' => $current_page, 'paginate' => true ) ) );
		foreach($customer_orders->orders as $order ){
			$data = $this->prepare_item_for_response($order, $request);
			$array[] = $data->data;
		}
		
		return $array;
	}
	
	
	/**
	 * Prepare a single order output for response.
	 *
	 * @param WP_Post $post Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response $data
	 */
	public function prepare_item_for_response( $post, $request ) {
		global $wpdb;

		$order = wc_get_order( $post );
		$dp    = $request['dp'];

		$user = wp_get_current_user();
		if(!$user->exists() || $order->get_user_id() !== $user->ID)
			return;
		
		$data = array(
			'id'                   => $order->id,
			'parent_id'            => $post->post_parent,
			'status'               => $order->get_status(),
			'order_key'            => $order->order_key,
			'number'               => $order->get_order_number(),
			'currency'             => $order->get_order_currency(),
			'version'              => $order->order_version,
			'prices_include_tax'   => $order->prices_include_tax,
			'date_created'         => wc_rest_prepare_date_response( $post->post_date_gmt ),
			'date_modified'        => wc_rest_prepare_date_response( $post->post_modified_gmt ),
			'customer_id'          => $order->get_user_id(),
			'discount_total'       => wc_format_decimal( $order->get_total_discount(), $dp ),
			'discount_tax'         => wc_format_decimal( $order->cart_discount_tax, $dp ),
			'shipping_total'       => wc_format_decimal( $order->get_total_shipping(), $dp ),
			'shipping_tax'         => wc_format_decimal( $order->get_shipping_tax(), $dp ),
			'cart_tax'             => wc_format_decimal( $order->get_cart_tax(), $dp ),
			'total'                => wc_format_decimal( $order->get_total(), $dp ),
			'total_tax'            => wc_format_decimal( $order->get_total_tax(), $dp ),
			'billing'              => array(),
			'shipping'             => array(),
			'payment_method'       => $order->payment_method,
			'payment_method_title' => $order->payment_method_title,
			'transaction_id'       => $order->get_transaction_id(),
			'customer_ip_address'  => $order->customer_ip_address,
			'customer_user_agent'  => $order->customer_user_agent,
			'created_via'          => $order->created_via,
			'customer_note'        => $order->customer_note,
			'date_completed'       => wc_rest_prepare_date_response( $order->completed_date ),
			'date_paid'            => $order->paid_date,
			'cart_hash'            => $order->cart_hash,
			'line_items'           => array(),
			'tax_lines'            => array(),
			'shipping_lines'       => array(),
			'fee_lines'            => array(),
			'coupon_lines'         => array(),
			'refunds'              => array(),
		);

		// Add addresses.
		$data['billing']  = $order->get_address( 'billing' );
		$data['shipping'] = $order->get_address( 'shipping' );

		// Add line items.
		foreach ( $order->get_items() as $item_id => $item ) {
			$product      = $order->get_product_from_item( $item );
			$product_id   = 0;
			$variation_id = 0;
			$product_sku  = null;

			// Check if the product exists.
			if ( is_object( $product ) ) {
				$product_id   = $product->id;
				$variation_id = $product->variation_id;
				$product_sku  = $product->get_sku();
			}

			$meta = new WC_Order_Item_Meta( $item, $product );

			$item_meta = array();

			$hideprefix = 'true' === $request['all_item_meta'] ? null : '_';

			foreach ( $meta->get_formatted( $hideprefix ) as $meta_key => $formatted_meta ) {
				$item_meta[] = array(
					'key'   => $formatted_meta['key'],
					'label' => $formatted_meta['label'],
					'value' => $formatted_meta['value'],
				);
			}

			$line_item = array(
				'id'           => $item_id,
				'name'         => $item['name'],
				'sku'          => $product_sku,
				'product_id'   => (int) $product_id,
				'variation_id' => (int) $variation_id,
				'quantity'     => wc_stock_amount( $item['qty'] ),
				'tax_class'    => ! empty( $item['tax_class'] ) ? $item['tax_class'] : '',
				'price'        => wc_format_decimal( $order->get_item_total( $item, false, false ), $dp ),
				'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item, false, false ), $dp ),
				'subtotal_tax' => wc_format_decimal( $item['line_subtotal_tax'], $dp ),
				'total'        => wc_format_decimal( $order->get_line_total( $item, false, false ), $dp ),
				'total_tax'    => wc_format_decimal( $item['line_tax'], $dp ),
				'taxes'        => array(),
				'meta'         => $item_meta,
			);

			$item_line_taxes = maybe_unserialize( $item['line_tax_data'] );
			if ( isset( $item_line_taxes['total'] ) ) {
				$line_tax = array();

				foreach ( $item_line_taxes['total'] as $tax_rate_id => $tax ) {
					$line_tax[ $tax_rate_id ] = array(
						'id'       => $tax_rate_id,
						'total'    => $tax,
						'subtotal' => '',
					);
				}

				foreach ( $item_line_taxes['subtotal'] as $tax_rate_id => $tax ) {
					$line_tax[ $tax_rate_id ]['subtotal'] = $tax;
				}

				$line_item['taxes'] = array_values( $line_tax );
			}

			$data['line_items'][] = $line_item;
		}

		// Add taxes.
		foreach ( $order->get_items( 'tax' ) as $key => $tax ) {
			$tax_line = array(
				'id'                 => $key,
				'rate_code'          => $tax['name'],
				'rate_id'            => $tax['rate_id'],
				'label'              => isset( $tax['label'] ) ? $tax['label'] : $tax['name'],
				'compound'           => (bool) $tax['compound'],
				'tax_total'          => wc_format_decimal( $tax['tax_amount'], $dp ),
				'shipping_tax_total' => wc_format_decimal( $tax['shipping_tax_amount'], $dp ),
			);

			$data['tax_lines'][] = $tax_line;
		}

		// Add shipping.
		foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
			$shipping_line = array(
				'id'           => $shipping_item_id,
				'method_title' => $shipping_item['name'],
				'method_id'    => $shipping_item['method_id'],
				'total'        => wc_format_decimal( $shipping_item['cost'], $dp ),
				'total_tax'    => wc_format_decimal( '', $dp ),
				'taxes'        => array(),
			);

			$shipping_taxes = maybe_unserialize( $shipping_item['taxes'] );

			if ( ! empty( $shipping_taxes ) ) {
				$shipping_line['total_tax'] = wc_format_decimal( array_sum( $shipping_taxes ), $dp );

				foreach ( $shipping_taxes as $tax_rate_id => $tax ) {
					$shipping_line['taxes'][] = array(
						'id'       => $tax_rate_id,
						'total'    => $tax,
					);
				}
			}

			$data['shipping_lines'][] = $shipping_line;
		}

		// Add fees.
		foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
			$fee_line = array(
				'id'         => $fee_item_id,
				'name'       => $fee_item['name'],
				'tax_class'  => ! empty( $fee_item['tax_class'] ) ? $fee_item['tax_class'] : '',
				'tax_status' => 'taxable',
				'total'      => wc_format_decimal( $order->get_line_total( $fee_item ), $dp ),
				'total_tax'  => wc_format_decimal( $order->get_line_tax( $fee_item ), $dp ),
				'taxes'      => array(),
			);

			$fee_line_taxes = maybe_unserialize( $fee_item['line_tax_data'] );
			if ( isset( $fee_line_taxes['total'] ) ) {
				$fee_tax = array();

				foreach ( $fee_line_taxes['total'] as $tax_rate_id => $tax ) {
					$fee_tax[ $tax_rate_id ] = array(
						'id'       => $tax_rate_id,
						'total'    => $tax,
						'subtotal' => '',
					);
				}

				if ( isset( $fee_line_taxes['subtotal'] ) ) {
					foreach ( $fee_line_taxes['subtotal'] as $tax_rate_id => $tax ) {
						$fee_tax[ $tax_rate_id ]['subtotal'] = $tax;
					}
				}

				$fee_line['taxes'] = array_values( $fee_tax );
			}

			$data['fee_lines'][] = $fee_line;
		}

		// Add coupons.
		foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
			$coupon_line = array(
				'id'           => $coupon_item_id,
				'code'         => $coupon_item['name'],
				'discount'     => wc_format_decimal( $coupon_item['discount_amount'], $dp ),
				'discount_tax' => wc_format_decimal( $coupon_item['discount_amount_tax'], $dp ),
			);

			$data['coupon_lines'][] = $coupon_line;
		}

		// Add refunds.
		foreach ( $order->get_refunds() as $refund ) {
			$data['refunds'][] = array(
				'id'     => $refund->id,
				'refund' => $refund->get_refund_reason() ? $refund->get_refund_reason() : '',
				'total'  => '-' . wc_format_decimal( $refund->get_refund_amount(), $dp ),
			);
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type, refers to post_type of the post being
		 * prepared for the response.
		 *
		 * @param WP_REST_Response   $response   The response object.
		 * @param WP_Post            $post       Post object.
		 * @param WP_REST_Request    $request    Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}", $response, $post, $request );
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		
		if ( ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
			define( 'WOOCOMMERCE_CHECKOUT', true );
		}
		
		WC()->checkout()->process_checkout();

	}

	/**
	 * Get order statuses.
	 *
	 * @return array
	 */
	protected function get_order_statuses() {
		$order_statuses = array();

		foreach ( array_keys( wc_get_order_statuses() ) as $status ) {
			$order_statuses[] = str_replace( 'wc-', '', $status );
		}

		return $order_statuses;
	}

	/**
	 * Get the Order's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'parent_id' => array(
					'description' => __( 'Parent order ID.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'status' => array(
					'description' => __( 'Order status.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'pending',
					'enum'        => $this->get_order_statuses(),
					'context'     => array( 'view', 'edit' ),
				),
				'order_key' => array(
					'description' => __( 'Order key.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'number' => array(
					'description' => __( 'Order number.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'currency' => array(
					'description' => __( 'Currency the order was created with, in ISO format.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => get_woocommerce_currency(),
					'enum'        => array_keys( get_woocommerce_currencies() ),
					'context'     => array( 'view', 'edit' ),
				),
				'version' => array(
					'description' => __( 'Version of WooCommerce when the order was made.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'prices_include_tax' => array(
					'description' => __( 'Shows if the prices included tax during checkout.', 'woocommerce' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created' => array(
					'description' => __( "The date the order was created, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified' => array(
					'description' => __( "The date the order was last modified, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_id' => array(
					'description' => __( 'User ID who owns the order. 0 for guests.', 'woocommerce' ),
					'type'        => 'integer',
					'default'     => 0,
					'context'     => array( 'view', 'edit' ),
				),
				'discount_total' => array(
					'description' => __( 'Total discount amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'discount_tax' => array(
					'description' => __( 'Total discount tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_total' => array(
					'description' => __( 'Total shipping amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'shipping_tax' => array(
					'description' => __( 'Total shipping tax amount for the order.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cart_tax' => array(
					'description' => __( 'Sum of line item taxes only.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total' => array(
					'description' => __( 'Grand total.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_tax' => array(
					'description' => __( 'Sum of all taxes.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'billing' => array(
					'description' => __( 'Billing address.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
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
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
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
					'description' => __( 'Shipping address.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
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
							'description' => __( 'Country code in ISO 3166-1 alpha-2 format.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'payment_method' => array(
					'description' => __( 'Payment method ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'payment_method_title' => array(
					'description' => __( 'Payment method title.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'set_paid' => array(
					'description' => __( 'Define if the order is paid. It will set the status to processing and reduce stock items.', 'woocommerce' ),
					'type'        => 'boolean',
					'default'     => false,
					'context'     => array( 'edit' ),
				),
				'transaction_id' => array(
					'description' => __( 'Unique transaction ID.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'customer_ip_address' => array(
					'description' => __( "Customer's IP address.", 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_user_agent' => array(
					'description' => __( 'User agent of the customer.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'created_via' => array(
					'description' => __( 'Shows where the order was created.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'customer_note' => array(
					'description' => __( 'Note left by customer during checkout.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'date_completed' => array(
					'description' => __( "The date the order was completed, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_paid' => array(
					'description' => __( "The date the order has been paid, in the site's timezone.", 'woocommerce' ),
					'type'        => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'cart_hash' => array(
					'description' => __( 'MD5 hash of cart items to ensure orders are not modified.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'line_items' => array(
					'description' => __( 'Line items data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name' => array(
							'description' => __( 'Product name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'sku' => array(
							'description' => __( 'Product SKU.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'product_id' => array(
							'description' => __( 'Product ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'variation_id' => array(
							'description' => __( 'Variation ID, if applicable.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'quantity' => array(
							'description' => __( 'Quantity ordered.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_class' => array(
							'description' => __( 'Tax class of product.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'price' => array(
							'description' => __( 'Product price.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'subtotal' => array(
							'description' => __( 'Line subtotal (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'subtotal_tax' => array(
							'description' => __( 'Line subtotal tax (before discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total' => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax' => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'taxes' => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'id' => array(
									'description' => __( 'Tax rate ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'total' => array(
									'description' => __( 'Tax total.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'subtotal' => array(
									'description' => __( 'Tax subtotal.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
						'meta' => array(
							'description' => __( 'Line item meta data.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'key' => array(
									'description' => __( 'Meta key.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'label' => array(
									'description' => __( 'Meta label.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'value' => array(
									'description' => __( 'Meta value.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
				'tax_lines' => array(
					'description' => __( 'Tax lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'rate_code' => array(
							'description' => __( 'Tax rate code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'rate_id' => array(
							'description' => __( 'Tax rate ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'label' => array(
							'description' => __( 'Tax rate label.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'compound' => array(
							'description' => __( 'Show if is a compound tax rate.', 'woocommerce' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'tax_total' => array(
							'description' => __( 'Tax total (not including shipping taxes).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'shipping_tax_total' => array(
							'description' => __( 'Shipping tax total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'shipping_lines' => array(
					'description' => __( 'Shipping lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'method_title' => array(
							'description' => __( 'Shipping method name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'method_id' => array(
							'description' => __( 'Shipping method ID.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total' => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax' => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'taxes' => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'id' => array(
									'description' => __( 'Tax rate ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'total' => array(
									'description' => __( 'Tax total.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
				'fee_lines' => array(
					'description' => __( 'Fee lines data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'name' => array(
							'description' => __( 'Fee name.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_class' => array(
							'description' => __( 'Tax class of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'tax_status' => array(
							'description' => __( 'Tax status of fee.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total' => array(
							'description' => __( 'Line total (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'total_tax' => array(
							'description' => __( 'Line total tax (after discounts).', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'taxes' => array(
							'description' => __( 'Line taxes.', 'woocommerce' ),
							'type'        => 'array',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
							'properties'  => array(
								'id' => array(
									'description' => __( 'Tax rate ID.', 'woocommerce' ),
									'type'        => 'integer',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'total' => array(
									'description' => __( 'Tax total.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
								'subtotal' => array(
									'description' => __( 'Tax subtotal.', 'woocommerce' ),
									'type'        => 'string',
									'context'     => array( 'view', 'edit' ),
									'readonly'    => true,
								),
							),
						),
					),
				),
				'coupon_lines' => array(
					'description' => __( 'Coupons line data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'id' => array(
							'description' => __( 'Item ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'code' => array(
							'description' => __( 'Coupon code.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'discount' => array(
							'description' => __( 'Discount total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'discount_tax' => array(
							'description' => __( 'Discount total tax.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'refunds' => array(
					'description' => __( 'List of refunds.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'id' => array(
							'description' => __( 'Refund ID.', 'woocommerce' ),
							'type'        => 'integer',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'reason' => array(
							'description' => __( 'Refund reason.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
						'total' => array(
							'description' => __( 'Refund total.', 'woocommerce' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['status'] = array(
			'default'           => 'any',
			'description'       => __( 'Limit result set to orders assigned a specific status.', 'woocommerce' ),
			'type'              => 'string',
			'enum'              => array_merge( array( 'any' ), $this->get_order_statuses() ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['product'] = array(
			'description'       => __( 'Limit result set to orders assigned a specific product.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['dp'] = array(
			'default'           => 2,
			'description'       => __( 'Number of decimal points to use in each resource.', 'woocommerce' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
