<?php

function cccs_credits_used_shortcode() {
	return cccs_user_credits_used();
}
add_shortcode( 'cccs_credits_used', 'cccs_credits_used_shortcode' );

function cccs_credits_total_shortcode() {
	$credits = cccs_user_credits_total();

	if ( -1 == $credits ) {
		$credits = "unlimited";
	}

	ob_start(); ?>

	<table class="cccs-credits">
		<thead>
			<tr>
				<th>Credits Per Month</th>
				<th>Credits Remaining</th>
			</tr>
		</thead>

		<tbody>
			<tr>
				<td><?php echo absint( cccs_user_credits_total() ); ?></td>
				<td><?php echo absint( cccs_user_credits_available() ); ?></td>
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

function cccs_credits_usage_shortcode() {
	if ( ! $credits = cccs_user_credits() ) {
		return '';
	}

	$components = array();
	foreach( $credits as $credit ) {
		if ( empty( $components[ $credit['component'] ] ) ) {
			$components[ $credit['component'] ] = 0;
		}

		$components[ $credit['component'] ] += ( ! isset( $credit['credits'] ) ) ? 1 : (int) $credit['credits'];
	}

	ob_start(); ?>

	<table class="credit-usage">
		<thead>
			<tr>
				<th>Component</th>
				<th>Credits Used</th>
			</tr>
		</thead>

		<tbody>

			<?php foreach( $components as $component => $count ) :
				if ( isset( buddypress()->{$component}->name ) ) {
					$component = buddypress()->{$component}->name;
				}
				?>
				<tr>
					<td><?php echo ucfirst( $component ); ?></td>
					<td><?php echo $count; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>

		<tfoot>
			<tr>
				<th>Total</th>
				<th><?php echo cccs_user_credits_used(); ?></th>
			</tr>
		</tfoot>

	</table>

	<?php
	return ob_get_clean();
}
add_shortcode( 'cccs_credits_usage', 'cccs_credits_usage_shortcode' );

