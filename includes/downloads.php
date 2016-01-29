<?php

CCCS_Downloads::get_instance();
class CCCS_Downloads {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the CCCS_Downloads
	 *
	 * @return CCCS_Downloads
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof CCCS_Downloads ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {

		// Let EDD know we are here
		add_filter( 'edd_template_paths', array( $this, 'template' ) );

		// use credit for purchase
		add_action( 'edd_checkout_before_gateway', array( $this, 'use_credit' ), 10, 2 );

		// customize button text for credit purchases
		add_filter( 'edd_purchase_link_args',    array( $this, 'maybe_modify_button' ) );

		// add/remove from cart
		add_action( 'edd_post_remove_from_cart', array( $this, 'recalculate_credits' ) );
		add_filter( 'edd_add_to_cart_item',      array( $this, 'item_credit_details' ) );

		// calculate price for cart items
		add_filter( 'edd_cart_item_price',   array( $this, 'item_credit_reset' ), 10, 2 );
		add_filter( 'edd_get_cart_item_tax', array( $this, 'item_credit_reset' ), 10, 2 );

		// pricing labels
		add_filter( 'edd_cart_total',             array( $this, 'cart_total_label'   ) );
		add_filter( 'edd_ajax_discount_response', array( $this, 'discount_cart_total_label' ) );
		add_filter( 'edd_receipt_item_price',     array( $this, 'receipt_item_label' ), 10,  2 );
		add_filter( 'edd_cart_item',              array( $this, 'cart_item_label'    ), 10,  2 );
		add_action( 'edd_payment_receipt_after',  array( $this, 'credit_receipt'     ), 10,  2 );
		add_filter( 'edd_cart_item_price_label',  array( $this, 'credit_price_label' ), 100, 3 );
		add_filter( 'edd_download_price',         array( $this, 'credit_price_label' ), 100, 3 );

		// custom calculation during ajax calls
		add_action( 'wp_ajax_edd_add_to_cart',             array( $this, 'ajax_add_to_cart'      ), 9 );
		add_action( 'wp_ajax_nopriv_edd_add_to_cart',      array( $this, 'ajax_add_to_cart'      ), 9 );
		add_action( 'wp_ajax_edd_remove_from_cart',        array( $this, 'ajax_remove_from_cart' ), 9 );
		add_action( 'wp_ajax_nopriv_edd_remove_from_cart', array( $this, 'ajax_remove_from_cart' ), 9 );
		add_action( 'wp_ajax_edd_remove_discount',         array( $this, 'ajax_remove_discount'  ), 9 );
		add_action( 'wp_ajax_nopriv_edd_remove_discount',  array( $this, 'ajax_remove_discount'  ), 9 );

	}

	/**
	 * General label for credit price
	 *
	 * @param $credit_count
	 *
	 * @return string
	 */
	protected function get_credit_price_label( $credit_count ) {
		return ( $credit_count > 1 ) ? $credit_count . " " . __( 'Credits', 'cccs' ) : "1 " . __( 'Credit', 'cccs' );
	}

	/**
	 * Return the post credit price of the item in the cart
	 *
	 * @param $price
	 * @param $download_id
	 *
	 * @return bool|int
	 */
	public function item_credit_reset( $price, $download_id ) {
		$credit_price = cccs_get_cart_item_credit_price( $download_id );

		if ( ( ! cccs_get_cart_item_credits( $download_id ) ) || $credit_price === false ) {
			return $price;
		}

		return $credit_price;
	}

	/**
	 * Get the label for the price in credits
	 *
	 * @param $price
	 * @param $download_id
	 * @param $price_id
	 *
	 * @return string
	 */
	public function credit_price_label( $price, $download_id, $price_id ) {

		if ( ! cccs_user_has_credits( $download_id ) ) {
			return $price;
		}

		return $this->get_credit_price_label( 1 );
	}

	/**
	 * Update button text if a member discount is available
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function maybe_modify_button( $args ) {

		if ( ! cccs_user_has_credits( $args['download_id'] ) ) {
			return $args;
		}

		$price     = edd_get_download_price( $args['download_id'] );
		$old_price = edd_currency_filter( edd_format_amount( $price ) );

		// replace the current price with the new price
		$args['text'] = str_replace( $old_price, $this->get_credit_price_label( 1 ), $args['text'] );

		return $args;
	}

	/**
	 * Label for item in receipt
	 *
	 * @param $template
	 * @param $item
	 *
	 * @return mixed
	 */
	public function receipt_item_label( $template, $item ) {

		if ( empty( $item['item_number']['options']['credits'] ) ) {
			return $template;
		}

		return $this->get_credit_price_label( $item['item_number']['options']['credits'] );
	}

	/**
	 * Label for item in cart
	 *
	 * @param $template
	 * @param $item_id
	 *
	 * @return mixed
	 */
	public function cart_item_label( $template, $item_id ) {

		if ( ! $credits = cccs_get_cart_item_credits( $item_id ) ) {
			return $template;
		}

		$price     = edd_get_cart_item_price( $item_id );
		$price_str = edd_currency_filter( edd_format_amount( $price ) );
		$template  = str_replace( $price_str, $this->get_credit_price_label( $credits ), $template );

		return $template;

	}


	/**
	 * Total Price label for cart
	 *
	 * @param $total
	 *
	 * @return string
	 */
	public function cart_total_label( $total ) {
		$cart = edd_get_cart_contents();
		$credits_in_cart = cccs_credits_in_cart();

		if ( empty( $credits_in_cart ) ) {
			return $total;
		}

		if ( $credits_in_cart == $cart ) {

			return $this->get_credit_price_label( count( $credits_in_cart ) );
		}

		return $total . ' + ' . $this->get_credit_price_label( count( $credits_in_cart ) );
	}

	public function discount_cart_total_label( $return ) {
		$return['total'] = $this->cart_total_label( $return['total'] );
		return $return;
	}

	/**
	 * Purchase completed, now use the credits for the items in the cart
	 *
	 * @param $_post
	 * @param $user_info
	 */
	public function use_credit( $_post, $user_info ) {
		$credits = cccs_credits_in_cart();

		if ( empty( $credits ) || empty( $user_info['id'] ) ) {
			return;
		}

		$credits = wp_list_pluck( $credits, 'id' );
		cccs_use_credit( $credits, $user_info['id'] );
	}

	/**
	 * Add credit details to cart item.
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public function item_credit_details( $item ) {
		if ( ! cccs_user_has_credits( $item['id'] ) ) {
			return $item;
		}

		$item['options']['credit_price'] = 0;
		$item['options']['credits'] = 1;

		return $item;
	}

	/**
	 * Recalculate items in cart after item is removed
	 */
	public function recalculate_credits() {

		if ( ! $cart = edd_get_cart_contents() ) {
			return;
		}

		foreach( $cart as &$item ) {
			if ( empty( $item['id'] ) ) {
				continue;
			}

			if ( cccs_get_cart_item_credits( $item['id'] ) ) {
				continue;
			}

			$item = $this->item_credit_details( $item );
		}

		EDD()->session->set( 'edd_cart', $cart );

	}

	/**
	 * Add line for Credit in receipt
	 *
	 * @param $payment
	 * @param $edd_receipt_args
	 */
	public function credit_receipt( $payment, $edd_receipt_args ) {
		$meta = edd_get_payment_meta( $payment->ID );

		if ( empty( $meta['downloads'] ) ) {
			return;
		}

		$credits = 0;
		foreach( (array) $meta['downloads'] as $download ) {
			if ( empty( $download['options']['credits'] ) ) {
				continue;
			}
			$credits += $download['options']['credits'];
		}

		if ( ! $credits ) {
			return;
		}

		?>
		<tr>
			<td><strong><?php _e( 'Credits Used', 'cccs' ); ?>:</strong></td>
			<td><?php echo $this->get_credit_price_label( $credits ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Make EDD aware of our template directory
	 *
	 * @param $file_paths
	 *
	 * @return mixed
	 */
	public function template( $file_paths ) {
		// random filepath inex
		$file_paths[27] = CCCS_PATH . '/templates';
		return $file_paths;
	}

	/**
	 * Adds item to the cart via AJAX
	 * Based on edd_ajax_add_to_cart
	 *
	 * updated Total field
	 */
	public function ajax_add_to_cart() {
		if ( isset( $_POST['download_id'] ) ) {
			$to_add = array();

			if ( isset( $_POST['price_ids'] ) && is_array( $_POST['price_ids'] ) ) {
				foreach ( $_POST['price_ids'] as $price ) {
					$to_add[] = array( 'price_id' => $price );
				}
			}

			$items = '';

			foreach ( $to_add as $options ) {

				if( $_POST['download_id'] == $options['price_id'] ) {
					$options = array();
				}

				parse_str( $_POST['post_data'], $post_data );

				if( isset( $options['price_id'] ) && isset( $post_data['edd_download_quantity_' . $options['price_id'] ] ) ) {

					$options['quantity'] = absint( $post_data['edd_download_quantity_' . $options['price_id'] ] );

				} else {

					$options['quantity'] = isset( $post_data['edd_download_quantity'] ) ? absint( $post_data['edd_download_quantity'] ) : 1;

				}

				$key = edd_add_to_cart( $_POST['download_id'], $options );

				$item = array(
					'id'      => $_POST['download_id'],
					'options' => $options
				);

				$item   = apply_filters( 'edd_ajax_pre_cart_item_template', $item );
				$items .= html_entity_decode( edd_get_cart_item_template( $key, $item, true ), ENT_COMPAT, 'UTF-8' );

			}

			$return = array(
				'subtotal'      => html_entity_decode( edd_currency_filter( edd_format_amount( edd_get_cart_subtotal() ) ), ENT_COMPAT, 'UTF-8' ),
				'total'         => html_entity_decode( edd_cart_total( false ), ENT_COMPAT, 'UTF-8' ),
				'cart_item'     => $items,
				'cart_quantity' => html_entity_decode( edd_get_cart_quantity() )
			);

			if ( edd_use_taxes() ) {
				$cart_tax = (float) edd_get_cart_tax();
				$return['tax'] = html_entity_decode( edd_currency_filter( edd_format_amount( $cart_tax ) ), ENT_COMPAT, 'UTF-8' );
			}

			echo json_encode( $return );
		}
		edd_die();
	}

	/**
	 * Removes item from cart via AJAX.
	 *
	 * @since 1.0
	 * @return void
	 */
	function ajax_remove_from_cart() {
		if ( isset( $_POST['cart_item'] ) ) {

			edd_remove_from_cart( $_POST['cart_item'] );

			$return = array(
				'removed'       => 1,
				'subtotal'      => html_entity_decode( edd_currency_filter( edd_format_amount( edd_get_cart_subtotal() ) ), ENT_COMPAT, 'UTF-8' ),
				'total'         => html_entity_decode( edd_cart_total( false ), ENT_COMPAT, 'UTF-8' ),
				'cart_quantity' => html_entity_decode( edd_get_cart_quantity() ),
			);

			if ( edd_use_taxes() ) {
				$cart_tax = (float) edd_get_cart_tax();
				$return['tax'] = html_entity_decode( edd_currency_filter( edd_format_amount( $cart_tax ) ), ENT_COMPAT, 'UTF-8' );
			}

			echo json_encode( $return );

		}
		edd_die();
	}

	/**
	 * Removes a discount code from the cart via ajax
	 *
	 * See EDD -> ajax-functions.php line 347
	 * @return void
	 */
	function ajax_remove_discount() {
		if ( isset( $_POST['code'] ) ) {

			edd_unset_cart_discount( urldecode( $_POST['code'] ) );

			$return = array(
				'total'     => html_entity_decode( edd_cart_total( false ), ENT_COMPAT, 'UTF-8' ),
				'code'      => $_POST['code'],
				'discounts' => edd_get_cart_discounts(),
				'html'      => edd_get_cart_discounts_html()
			);

			echo json_encode( $return );
		}
		edd_die();
	}

}

/**
 * Get cart item details
 *
 * @param $item_id
 *
 * @return bool
 */
function cccs_get_cart_item_details( $item_id ) {
	if ( ! $cart = edd_get_cart_contents() ) {
		return false;
	}

	foreach( $cart as $item ) {
		if ( $item_id == $item['id'] ) {
			return $item;
		}
	}

	return false;
}

/**
 * Return the number of credits an item in the cart costs
 *
 * @param $item_id
 *
 * @return bool|int
 */
function cccs_get_cart_item_credits( $item_id ) {
	if ( ! $item = cccs_get_cart_item_details( $item_id ) ) {
		return false;
	}

	if ( empty( $item['options']['credits'] ) ) {
		return false;
	}

	return absint( $item['options']['credits'] );
}

/**
 * Return post credit price of item in cart
 *
 * @param $item_id
 *
 * @return bool|int
 */
function cccs_get_cart_item_credit_price( $item_id ) {
	if ( ! $item = cccs_get_cart_item_details( $item_id ) ) {
		return false;
	}

	if ( ! isset( $item['options']['credit_price'] ) ) {
		return false;
	}

	return absint( $item['options']['credit_price'] );
}