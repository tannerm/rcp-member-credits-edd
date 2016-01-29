<?php

function cccs_credits_total_shortcode() {
	ob_start(); ?>

	<table class="cccs-credits">
		<thead>
			<tr>
				<th>Credits Per Month</th>
				<th>Credits Remaining</th>
				<th>Credits Renewal Date</th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td><?php echo absint( cccs_user_credits_total() ); ?></td>
				<td><?php echo absint( cccs_user_credits_available() ); ?></td>
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