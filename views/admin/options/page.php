<?php namespace DataSync;

use DataSync\Controllers\Logs;

/**
 * Outputs HTML for settings page
 */
function data_sync_options_page() {

	require_once DATA_SYNC_PATH . 'views/admin/options/status-dashboard.php';
	require_once DATA_SYNC_PATH . 'views/admin/options/enabled-post-types-dashboard.php';

	?>
    <div id="feedback"></div>
    <div class="wrap">
        <h2>DATA SYNC</h2>
        <div id="data_sync_tabs">
			<?php if ( '1' === get_option( 'source_site' ) ) { ?>
                <ul>
                    <li><a href="#syndicated_posts">Syndicated Posts</a></li>
                    <li><a href="#connected_sites">Connected Sites</a></li>
                    <li><a href="#enabled_post_types">Enabled Post Types</a></li>
                    <li><a href="#debug_log">Log</a></li>
                    <li><a href="#settings">Settings</a></li>
                </ul>
			<?php } else { ?>
                <h3>Settings</h3>
			<?php } ?>
			<?php if ( '1' === get_option( 'source_site' ) ) { ?>
                <div id="syndicated_posts">
                    <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
                    <div id="syndicated_posts_wrap"></div>
                </div>
                <div id="connected_sites">
                    <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
                    <div id="connected_sites_wrap"></div>
                </div>
                <div id="enabled_post_types">
                    <span class="loading_spinner"><i class="dashicons dashicons-update"></i> Loading. . .</span>
                    <div id="enabled_post_types_wrap"></div>
                </div>
                <div id="debug_log">
					<?php
					if ( get_option( 'source_site' ) ) {
						?>
                        <h2>Log</h2>
						<?php
						if ( '1' === get_option( 'debug' ) ) {
							?>
                            <span id="refresh_error_log">Refresh log</span>
                            <div id="error_log">
								<?php include_once 'log.php'; ?>
								<?php echo display_log(); ?>
                            </div>
							<?php
						} else {
							?><span>Please enable debugging on the settings page to view error log. Debugging decreases this settings page performance so it's best to leave it disabled.</span><?php
						}

					}
					?>
                </div>

			<?php } ?>
            <div id="settings">
                <form method="POST" action="options.php">
					<?php
					settings_fields( 'data_sync_options' );
					do_settings_sections( 'data-sync-options' );
					submit_button();
					?>
                </form>
            </div>
        </div>

    </div>
	<?php
}
