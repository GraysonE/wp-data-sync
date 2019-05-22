<?php namespace DataSync;

if ( file_exists( DATA_SYNC_PATH . 'vendor/autoload.php' ) ) {
	require_once DATA_SYNC_PATH . 'vendor/autoload.php';
}

require_once 'enqueue.php';
require_once DATA_SYNC_PATH . 'admin/admin-require.php';


add_action( 'admin_init', __NAMESPACE__ . '\ensure_admin_functionality' );
new API();
function ensure_admin_functionality() {
	new API();
}

