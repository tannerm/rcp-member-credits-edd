<?php

CCCS_Settings::get_instance();
class CCCS_Settings {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var string
	 */
	public static $_credit_map_key = 'cc_credit_map';

	/**
	 * Only make one instance of the CCCS_Settings
	 *
	 * @return CCCS_Settings
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof CCCS_Settings ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->hooks();
	}

	/**
	 * Actions and Filters
	 */
	protected function hooks() {
		add_action( 'rcp_levels_page_table_header', array( $this, 'credit_count_label' ) );
		add_action( 'rcp_levels_page_table_footer', array( $this, 'credit_count_label' ) );
		add_action( 'rcp_levels_page_table_column', array( $this, 'credit_count'       ) );

		add_action( 'rcp_add_subscription_form',   array( $this, 'subscription_credit_count' ) );
		add_action( 'rcp_edit_subscription_form',  array( $this, 'subscription_credit_count' ) );

		add_action( 'rcp_edit_subscription_level', array( $this, 'subscription_credit_count_save' ), 10, 2 );
		add_action( 'rcp_add_subscription',        array( $this, 'subscription_credit_count_save' ), 10, 2 );
	}

	public function reset_user_credits() {
		$day = gmdate( 'j' );
		get_users( array(
			'date_query' => array(
				'day' => $day,
			),
		) );
	}

	/**
	 * Table header/footer
	 */
	public function credit_count_label() {
		printf( '<th class="rcp-sub-credit-col">%s</th>', __( 'Credits', 'cc' ) );
	}

	/**
	 * Table child count
	 *
	 * @param $subscription_id
	 */
	public function credit_count( $subscription_id ) {
		printf( '<td>%s</td>', cc_get_credits_per_level( $subscription_id ) );
	}

	public function subscription_credit_count( $level = null ) {

		$credit_count = ( empty( $level->id ) ) ? 0 : cc_get_credits_per_level( $level->id ); ?>

		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="rcp-credit-count"><?php _e( 'Credits', 'cc' ); ?></label>
			</th>
			<td>
				<input id="rcp-credit-count" type="number" min="0" name="credit-count" value="<?php echo absint( $credit_count ); ?>" style="width: 40px;"/>
				<p class="description"><?php _e( 'The number of child accounts for this subscription level.', 'cc' ); ?></p>
			</td>
		</tr>

	<?php
	}

	/**
	 * Save the member type for this subscription
	 *
	 * @param $subscription_id
	 * @param $args
	 */
	public function subscription_credit_count_save( $subscription_id, $args ) {

		// make sure the member type is set
		if ( ! isset( $_POST['credit-count'] ) ) {
			return;
		}

		$map = cc_get_credit_map();
		$map[ $subscription_id ] = absint( $_POST['credit-count'] );
		update_option( self::$_credit_map_key, $map );
	}

}

/**
 * Return the mapping of credits per user role
 *
 * @return mixed|void
 */
function cc_get_credit_map() {
	return get_option( CCCS_Settings::$_credit_map_key, array() );
}

/**
 * Get number of credits for this subscription
 *
 * @return mixed|string|void
 */
function cc_get_credits_per_level( $subscription_id ) {
	$map  = cc_get_credit_map();

	if ( empty( $map[ $subscription_id ] ) ) {
		return 0;
	}


	return absint( $map[ $subscription_id ] );
}