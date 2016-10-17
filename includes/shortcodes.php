<?php

function cccs_credits_total_shortcode() {
	$month = date( 'n', strtotime( cccs_user_credit_renewal_date() ) );

	$user_credits = new CCCS_User_Credits();
	$used_credits = $user_credits->get_used_credits();
	$exp_credits  = cccs_get_rollover_credits( null, $month );

	$exp_credits = max( 0, $exp_credits - $used_credits );

	ob_start(); ?>

	<table class="cccs-credits">
		<thead>
			<tr>
				<th>Credits Per Month</th>
				<th>Credits Remaining</th>
				<th>Credits Expiring</th>
				<th>Credits Renewal Date</th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td><?php echo absint( cc_get_credits_per_level( rcp_get_subscription_id() ) ); ?></td>
				<td><?php echo absint( cccs_user_credits_available() ); ?></td>
				<td><?php echo $exp_credits; ?></td>
				<td><?php echo cccs_user_credit_renewal_date(); ?></td>
			</tr>
		</tbody>
	</table>
	<?php

	return ob_get_clean();
}
add_shortcode( 'user_credits', 'cccs_credits_total_shortcode' );

function cccs_credits_available_shortcode() {
	$credits = cccs_user_credits_available();

	if ( CCCS_User_Credits::$_infinite == $credits ) {
		$credits = "unlimited";
	}

	return $credits;
}
add_shortcode( 'cccs_credits_available', 'cccs_credits_available_shortcode' );