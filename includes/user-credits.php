<?php

class CCCS_User_Credits {

	public $user_id;

	public $level_id;

	public static $_infinite = 999999;

	private static $_credits_used_key = 'cccs_used_credits';
	private static $_credits_rollover_key = 'cccs_rollover_credits';

	public function __construct( $user_id = null ) {

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$this->user_id  = $user_id;
		$this->level_id = rcp_get_subscription_id( $user_id );

		rcp_get_members();
	}

	/**
	 * The dates the credits renew
	 *
	 * @return bool|string
	 */
	public function get_credit_renewal_date() {
		$user = get_userdata( $this->user_id );

		$current_month = date( 'n', current_time( 'timestamp' ) );
		$current_year  = date( 'Y', current_time( 'timestamp' ) );
		$current_day   = date( 'j', current_time( 'timestamp' ) );
		$day           = date( 'j', strtotime( $user->user_registered ) );

		// handle all renewals after the 28th on the 1st
		if ( $day > 28 ) {
			$day = 1;
		}

		if ( $current_day >= $day ) {
			if ( ++ $current_month > 12 ) {
				$current_month = 1;
				$current_year ++;
			}
		}

		$date = mktime( 01, 00, 00, $current_month, $day, $current_year );
		return date( get_option( 'date_format', 'F j, Y' ), $date );
	}

	/**
	 * Get the total number of credits of this user for the current credit/month period
	 *
	 * @return int
	 */
	public function get_total_credits( $ignore_rollover = false ) {
		if ( ! $this->level_id ) {
			return 0;
		}

		if ( ! rcp_is_active( $this->user_id ) ) {
			return 0;
		}

		$credits = cc_get_credits_per_level( $this->level_id );

		if ( ! $ignore_rollover ) {
			if ( $rollover_credits = $this->get_rollover_credits() ) {
				$credits += array_sum( (array) $rollover_credits );
			}
		}

		return absint( $credits );
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

		$credits -= $this->get_used_credits( $ignore_cart );

		return $credits;
	}

	/**
	 * Get the number of credits this user has used for the current period
	 * @param bool $ignore_cart
	 *
	 * @return array|bool|int|mixed
	 */
	public function get_used_credits( $ignore_cart = false ) {

		if ( ! $used_credits = get_user_meta( $this->user_id, self::$_credits_used_key, true ) ) {
			$used_credits = array();;
		}

		if ( ! is_array( $used_credits ) ) {
			$used_credits = array();
		}

		$used_credits = count ( $used_credits );

		// don't count credits in the cart
		if ( ! $ignore_cart ) {
			$used_credits += count( cccs_credits_in_cart() );
		}

		return $used_credits;
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

	/**
	 * Get the rollover credits for this user
	 *
	 * @param null $month
	 *
	 * @return array|bool|int|mixed
	 */
	public function  get_rollover_credits( $month = null ) {
		$rollover_credits = get_user_meta( $this->user_id, self::$_credits_rollover_key, true );

		// make sure we have an array
		if ( ! is_array( $rollover_credits ) ) {
			return array();
		}

		// check if we are looking for a month
		if ( empty( $month ) ) {
			// remove empty entries
			return array_filter( $rollover_credits );
		}

		if ( empty( $rollover_credits[ $month ] ) ) {
			return 0;
		}

		return $rollover_credits[ $month ];

	}

	/**
	 * remove used credits from user to renew the user's credit count
	 */
	public function renew_credits() {

		$current_month    = date( 'n', current_time( 'timestamp' ) );

		$rollover_credits = $this->get_rollover_credits();
		$used_credits     = $this->get_used_credits( true );
		$monthly_credits  = $this->get_total_credits( true );

		// Count used credits against rollover first, then monthly credits
		if ( $used_credits ) {
			$month = $current_month;

			// step through each month, but don't use $i, we need to calculate from oldest to newest
			for ( $i = 1; $i <= 12; $i ++ ) {

				if ( ! empty( $rollover_credits[ $month ] ) ) {

					// get the number of credits we can use from this month and apply toward the used credits
					$credits = min( $used_credits, $rollover_credits[ $month ] );

					$rollover_credits[ $month ] -= $credits;
					$used_credits -= $credits;

				}

				if ( empty( $used_credits ) ) {
					break;
				}

				// calculate for end of year.
				if ( ++ $month > 12 ) {
					$month = 1;
				}

			}

		}

		// Update rollover credits for this month with any unused monthly credits,
		// overwriting last year's value
		$rollover_credits[ $current_month ] = max( 0, $monthly_credits - $used_credits );

		update_user_meta( $this->user_id, self::$_credits_rollover_key, array_filter( $rollover_credits ) );
		delete_user_meta( $this->user_id, self::$_credits_used_key );
	}

}

/**
 * Get the rollover credits for this user for the specified month
 *
 * @param null $user_id
 * @param null $month
 *
 * @return array|bool|int|mixed
 */
function cccs_get_rollover_credits( $user_id = null, $month = null ) {
	$credits = new CCCS_User_Credits( $user_id );
	return $credits->get_rollover_credits( $month );
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

function cccs_user_credit_renewal_date( $user_id = null ) {
	$user_credits = new CCCS_User_Credits( $user_id );
	return $user_credits->get_credit_renewal_date();
}

/**
 * Reset user credit count
 *
 * @param null $user_id
 */
function cccs_user_credits_renew( $user_id = null ) {
	$user_credits = new CCCS_User_Credits( $user_id );
	$user_credits->renew_credits();
}

/**
 * Renew user credits each month based on registration date.
 *
 * Runs on cron
 */
function cccs_renew_user_credits() {

	$date = date( 'j', current_time( 'timestamp' ) );
	$day  = array();

	// don't renew on the 29, 30, or 31
	if ( $date > 28 ) {
		return;
	}

	if ( 1 == $date ) {
		// process 29, 30, 31, and 1 on the 1
		foreach( array( 29, 30, 31, 1 ) as $extra_day ) {
			$day[] = array( 'day' => $extra_day );
		}
	} else {
		$day[] = array( 'day' => $date );
	}

	$day['relation'] = 'OR';

	$users = get_users( array(
		'fields'     => 'ids',
		'date_query' => array( $day ),
	) );

	foreach( $users as $user_id ) {
		cccs_user_credits_renew( $user_id );
	}

}
add_action( 'cccs_renew_credits', 'cccs_renew_user_credits' );
//add_action( 'wp', 'cccs_renew_user_credits' );

/**
 * Setup cron for credit renew
 */
function cccs_setup_credit_renew_cron() {

	if ( wp_next_scheduled( 'cccs_renew_credits' ) ) {
		return;
	}

	$timezone = get_option( 'timezone_string', 'America/Denver' );
	$time = new DateTime( 'today 3:00:00', new DateTimeZone( $timezone ) );

	wp_schedule_event( $time->getTimestamp(), 'daily', 'cccs_renew_credits' );
}
add_action( 'wp', 'cccs_setup_credit_renew_cron' );