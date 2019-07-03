<?php namespace DataSync;

use DataSync\Controllers\Log as Log;

/**
 * Outputs HTML for settings page
 */
function data_sync_options_page() {
	?>
	<div id="feedback"></div>
	<div class="wrap">
		<h2>Data Sync Settings</h2>
		<form method="POST" action="options.php">
			<?php
			settings_fields( 'data_sync_options' );
			do_settings_sections( 'data-sync-options' );
			submit_button();
			if ( get_option( 'source_site' ) ) {
				?>
				<h2>Error Log</h2>
				<div id="error_log">
					<?php echo Log::get_log() ?>
				</div>
				<?php
			}
			?>
		</form>
	</div>
	<?php
}
