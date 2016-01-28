<?php

class CCCS_User_Credits {

	public $user_id;

	public $level_id;

	public static $_infinite = 999999;

	private static $_credits_used_key = 'cccs_used_credits';

	public function __construct( $user_id = null ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$this->user_id  = $user_id;
		$this->level_id = rcp_get_subscription_id( $user_id );

		rcp_get_members();
	}

	/**
	 * Get the total number of credits of this user
	 *
	 * @return int
	 */
	public function get_total_credits() {
		if ( ! $this->level_id ) {
			return 0;
		}

		if ( ! rcp_is_active( $this->user_id ) ) {
			return 0;
		}

		return cc_get_credits_per_level( $this->level_id );
	}

	/**
	 * Get available credits for the user
	 *
	 * @param bool $ignore_cart - Whether or not to factor the items in the cart
	 *
	 * @return bool|int|mixed
	 */
	public function get_available_credits( $ignore_cart = false ) {
		$credits = $this->get_total_credits();

		if ( ! $used_credits = get_user_meta( $this->user_id, self::$_credits_used_key, true ) ) {
			$used_credits = array();
		}

		$credits -= count( $used_credits );

		// don't count credits in the cart
		if ( ! $ignore_cart ) {
			$credits -= count( cccs_credits_in_cart() );
		}

		return $credits;
	}

	/**
	 * Save the credit args to usermeta and return the use ID
	 *
	 * @param $credits
	 *
	 * @return string
	 */
	public function use_credit( $credits ) {
		$credits_used = get_user_meta( $this->user_id, self::$_credits_used_key, true );

		if ( ! $credits_used ) {
			$credits_used = array();
		}

		$credits_used = array_merge( $credits_used, $credits );
		update_user_meta( $this->user_id, self::$_credits_used_key, $credits_used );
	}

}

/**
 * Total credits available
 *
 * @param null $user_id
 * @param bool $ignore_cart
 * @param null $item_id
 *
 * @return int
 */
function cccs_user_credits_available( $user_id = null, $ignore_cart = false, $item_id = null ) {
	$credits = new CCCS_User_Credits( $user_id );

	// we can't access the cart if the user is the current user
	if ( $credits->user_id !== get_current_user_id() ) {
		$ignore_cart = true;
	}

	return $credits->get_available_credits( $ignore_cart, $item_id );
}

/**
 * Does the user have credits for another item?
 *
 * @param null $item_id
 *
 * @return bool
 */
function cccs_user_has_credits( $item_id = null ) {
	$credits = cccs_user_credits_available( null, false, $item_id );

	// check if credits are available
	if ( (bool) $credits ) {
		return true;
	}

	// check if the item is already using a credit in the cart
	$credits_in_cart = wp_list_pluck( cccs_credits_in_cart(), 'id' );
	return in_array( $item_id, $credits_in_cart );
}

/**
 * Get the items in the cart that are using a credit
 *
 * @return array
 */
function cccs_credits_in_cart() {
	if ( ! $cart = edd_get_cart_contents() ) {
		$cart = array();
	}

	$credits = array();;
	foreach( $cart as $item ) {
		if ( cccs_get_cart_item_credits( $item['id'] ) ) {
			$credits[] = $item;
		}
	}

	return $credits;
}

function cccs_user_credits_total( $user_id = null ) {
	$credits = new CCCS_User_Credits( $user_id );
	return $credits->get_total_credits();
}

/**
 * Use credit
 *
 * @param      $credits
 * @param null $user_id
 *
 * @return string
 */
function cccs_use_credit( $credits, $user_id = null ) {
	$user_credits = new CCCS_User_Credits( $user_id );
	return $user_credits->use_credit( $credits );
}