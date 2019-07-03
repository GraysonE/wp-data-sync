<?php


namespace DataSync\Models;


use DataSync\Controllers\Log;
use DataSync\Helpers;

/**
 * Class DB
 * @package DataSync\Models
 */
class DB {

	/**
	 * @var string
	 */
	public $table_name;

	/**
	 * DB constructor.
	 *
	 * @param $table_name
	 */
	public function __construct( $table_name ) {
		global $wpdb;
		$this->table_name = $wpdb->prefix . $table_name;
	}

	/**
	 * @param int $id
	 *
	 * @return mixed
	 */
	public function get( int $id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $this->table_name . ' WHERE id = %d', $id ) );
	}


	public function get_all() {
		global $wpdb;

		return $wpdb->get_results( 'SELECT * FROM ' . $this->table_name );
	}

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function get_where( array $args ) {
		global $wpdb;

		$query     = 'SELECT * FROM ' . $this->table_name . ' WHERE';
		$arg_count = count( $args );
		$i         = 1;

		foreach ( $args as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$filtered_value = filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT );
			} else {
				$filtered_value = filter_var( $value, FILTER_SANITIZE_STRING );
			}
			$query .= ' `' . $key . '` = \'' . $filtered_value . '\'';
			if ( $i < $arg_count ) {
				$query .= ' AND';
			}
			$i ++;
		}

		return $wpdb->get_results( $query );

	}

	/**
	 * @param $args
	 * @param $sprintf
	 *
	 * @return WP_Error
	 */
	public function create( $args, $sprintf ) {
		global $wpdb;

		$created = $wpdb->insert(
			$this->table_name,
			$args,
			$sprintf
		);

		if ( false === $created ) {
			$error_msg = 'Database failed to create: ' . $wpdb->last_error;
			$error_msg .= '<br>' . $wpdb->last_query;
			new Log( 'ERROR: ' . $error_msg, true );

			return new WP_Error( 503, __( $error_msg, 'data-sync' ) );
		} else {
			return $created;
		}
	}

	/**
	 * @param $args
	 * @param $where
	 *
	 * @return WP_Error
	 */
	public function update( $args, $where ) {
		global $wpdb;

		$updated = $wpdb->update( $this->table_name, $args, $where );

		if ( false === $updated ) {
			$error_msg = 'Database failed to update: ' . $wpdb->last_error;
			$error_msg .= '<br>' . $wpdb->last_query;
			new Log( 'ERROR: ' . $error_msg, true );

			return new WP_Error( 503, __( $error_msg, 'data-sync' ) );
		} else {
			return $updated;
		}
	}

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
	public function delete( $id ) {
		global $wpdb;
		$result = $wpdb->delete(
			$this->table_name,
			array(
				'id' => $id,
			),
			array(
				'%d',
			)
		);

		return $result;

	}


}