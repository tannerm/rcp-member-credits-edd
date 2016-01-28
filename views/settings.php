<?php $map = get_option( CCCS_Settings::$_credit_map_key ); ?>
<div class="wrap">
	<h2>CreativeChurch Credit Mapping</h2>

	<form method="post" action="">

		<div class="metabox-holder has-right-sidebar">
			<div class="inner-sidebar">

				<div class="postbox">
					<h3><span>Index</span></h3>

					<div class="inside">
						<ul>
							<li><a href="#credits-per-role">Credits Per Role</a></li>
							<li><a href="#credits-per-component">Credits Per Component</a></li>
							<li><a href="#notifications">Notifications</a></li>
							<li><a href="#shortcode-ref">Short Codes</a></li>
						</ul>
					</div>

					<div id="major-publishing-actions">
						<?php wp_nonce_field( 'cccs_credit_save', 'cccs_credit_nonce' ); ?>
						<?php submit_button( 'Save Changes', 'primary', 'submit', false ); ?>
					</div>
				</div>

			</div>

			<div id="post-body">
				<div id="post-body-content">

					<div class="postbox" id="credits-per-role">
						<h3>Credits per user role</h3>

						<div class="inside">
							<p class="description">Use -1 for unlimitted credits.</p>
							<table>
								<?php foreach ( rcp_get_subscription_levels() as $level ) : ?>
									<tr>
										<td><?php echo $level->name; ?></td>
										<td>
											<input type="number" name="cccs[levels][<?php echo $level->id; ?>]" value="<?php echo cccs_get_setting( 'levels', $level->id ); ?>" min="-1" max="99999999" />
										</td>
									</tr>
								<?php endforeach; ?>
							</table>
						</div>
						<!-- .inside -->
					</div>
					<!--end postbox-->

					<div class="postbox" id="credits-per-component">
						<h3>Credits used per component</h3>

						<div class="inside">
							<table>
								<tr>
									<td>Trackbar</td>
									<td>
										<input type="number" name="cccs[components][pbtrack][credits]" value="<?php echo cccs_get_setting( 'components', 'pbtrack', 'credits' ); ?>" min="0" max="20" />
									</td>
								</tr>
								<tr>
									<td>Group Member</td>
									<td>
										<input type="number" name="cccs[components][member][credits]" value="<?php echo cccs_get_setting( 'components', 'pbtrack', 'credits' ); ?>" min="0" max="20" />
									</td>
								</tr>
								<tr>
									<td>BuddyDrive</td>
									<td>
										<input type="number" name="cccs[components][buddydrive][credits]" value="<?php echo cccs_get_setting( 'components', 'buddydrive', 'credits' ); ?>" min="0" max="20" /> per 100MB
									</td>
								</tr>
							</table>
						</div>
						<!-- .inside -->
					</div>

					<div class="postbox" id="notifications">
						<h3>Notifications</h3>

						<div class="inside">
							<h4>Need more credits</h4>
							<?php wp_editor( cccs_get_setting( 'notifications', 'need-more-credits' ), 'need-more-credits', array(
								'textarea_name' => 'cccs[notifications][need-more-credits]',
								'textarea_rows' => '8',
								'teeny'         => true,
								'media_buttons' => false,
							) ); ?>
							<p class="description">The message that will be displayed when the user does not have enough credits to enable component.</p>
						</div>
						<!-- .inside -->
					</div>

					<div class="postbox" id="shortcode-ref">
						<h3><span>Short Code Reference</span></h3>

						<div class="inside">

							<h4>[cccs_credits_used]</h4>
							<p>This short code will display the number of credits the current user has used.</p>

							<hr />

							<h4>[cccs_credits_total]</h4>
							<p>This short code will display the total number of credits the current user has (based on subscription level). If the level is unlimitted, "unlimited" will be displayed.</p>

							<hr />

							<h4>[cccs_credits_available]</h4>
							<p>This short code will display the number of credits the current user has available(total credits - used credits). If the level is unlimitted, "unlimited" will be displayed.</p>

							<hr />

							<h4>[cccs_credits_usage]</h4>
							<p>This short code will display the credit usage of the current user grouped by component.</p>

						</div>
						<!-- .inside -->
					</div>
					<!--end postbox-->

				</div>
				<!-- #post-body-content -->

			</div>
		</div>

	</form>

</div>