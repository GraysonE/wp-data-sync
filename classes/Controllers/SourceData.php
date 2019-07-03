<?php


namespace DataSync\Controllers;

use DataSync\Controllers\Options;
use DataSync\Controllers\ConnectedSites;
use WP_REST_Request;
use WP_REST_Server;
use ACF_Admin_Tool_Export;
use DataSync\Controllers\Log;
use stdClass;

class SourceData {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes() {
		$registered = register_rest_route(
			DATA_SYNC_API_BASE_URL,
			'/source_data/push',
			array(
				array(
					'methods'  => WP_REST_Server::READABLE,
					'callback' => array( $this, 'push' ),
				),
			)
		);
	}

	public function push() {

		new Log( '---------------------------------------------------------- STARTING NEW PUSH ----------------------------------------------------------' );

		$source_data     = $this->consolidate();
		$connected_sites = $source_data->connected_sites;

		foreach ( $connected_sites as $site ) {

			new Log( 'STATUS: Beginning push to ' . $site->url );

			$source_data->receiver_site_id = (int) $site->id;
			$auth                          = new Auth();
			$json                          = $auth->prepare( $source_data, $site->secret_key );
			$url                           = trailingslashit( $site->url ) . 'wp-json/' . DATA_SYNC_API_BASE_URL . '/receive';
			$response                      = wp_remote_post( $url, [ 'body' => $json ] );
			$body                          = wp_remote_retrieve_body( $response );
			var_dump( $body );

			new Log( 'STATUS: Finished push to ' . $site->url );
		}

	}

	private function consolidate() {

		new Log( 'STATUS: Beginning consolidation.' );

		$options = Options::source()->get_data();

		$source_data                    = new stdClass();
		$source_data->options           = (array) $options;
		$source_data->acf               = (array) Posts::get_acf_fields(); // use acf_add_local_field_group() to install this array.
		$source_data->custom_taxonomies = cptui_get_taxonomy_data();
		$source_data->url               = (string) get_site_url();
		$source_data->connected_sites   = (array) ConnectedSites::get_all()->get_data();
		$source_data->nonce             = (string) wp_create_nonce( 'data_push' );
		$source_data->posts             = (object) Posts::get( array_keys( $options->push_enabled_post_types ) );

		new Log( 'STATUS: Finished consolidation.' );

		return $this->validate( $source_data );

	}

	private function validate( object $source_data ) {

		new Log( 'STATUS: Beginning post validation.' );

		foreach ( $source_data->posts as $post_type => $post_data ) {

			foreach ( $post_data as $key => $post ) {

				if ( ! isset( $post->post_meta['_canonical_site'] ) ) {
					unset( $source_data->posts->$post_type[ $key ] );
					new Log( 'SKIPPING: Canonical site not set in post: ' . $post->post_title );
				}

				if ( ! isset( $post->post_meta['_excluded_sites'] ) ) {
					unset( $source_data->posts->$post_type[ $key ] );
					new Log( 'SKIPPING: Excluded sites not set in post: ' . $post->post_title );
				}
			}
		}

		new Log( 'STATUS: Finished post validation.' );

		return $source_data;
	}

}