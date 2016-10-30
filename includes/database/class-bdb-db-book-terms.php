<?php

/**
 * Book Terms DB Class
 *
 * This class is for interacting with the book terms database table.
 *
 * @package   book-database
 * @copyright Copyright (c) 2016, Ashley Gibson
 * @license   GPL2+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BDB_DB_Book_Terms extends BDB_DB {

	/**
	 * BDB_DB_Book_Terms constructor.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function __construct() {

		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'bdb_book_terms';
		$this->primary_key = 'term_id';
		$this->version     = '1.0';
	}

	/**
	 * Get columns and formats.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array
	 */
	public function get_columns() {
		return array(
			'term_id'     => '%d',
			'type'        => '%s',
			'name'        => '%s',
			'description' => '%s',
			'image'       => '%s',
			'links'       => '%s',
			'count'       => '%d',
		);
	}

	/**
	 * Get default column values.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array
	 */
	public function get_column_defaults() {
		return array(
			'type'        => '',
			'name'        => '',
			'description' => '',
			'image'       => '',
			'links'       => '',
			'count'       => 0
		);
	}

	/**
	 * Add a Term
	 *
	 * @param array $data Term data.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return int Term ID.
	 */
	public function add( $data = array() ) {

		$defaults = array();

		$args = wp_parse_args( $data, $defaults );

		$term = ( array_key_exists( 'term_id', $args ) ) ? $this->get_term_by( 'term_id', $args['term_id'] ) : false;

		if ( $term ) {

			// Updating an existing term.
			$this->update( $term->term_id, $args );

			return $term->term_id;

		} else {

			// Adding a new term.
			return $this->insert( $args, 'term' );

		}

	}

	/**
	 * Delete a Term
	 *
	 * @param bool $id ID of the term to delete.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return bool|int False on failure.
	 */
	public function delete( $id = false ) {

		if ( empty( $id ) ) {
			return false;
		}

		$term = $this->get_term_by( 'term_id', $id );

		if ( $term->term_id > 0 ) {

			global $wpdb;

			return $wpdb->delete( $this->table_name, array( 'term_id' => $term->term_id ), array( '%d' ) );

		} else {
			return false;
		}

	}

	/**
	 * Check if a term exists.
	 *
	 * @param string $value Value of the column.
	 * @param string $field Which field to check.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return bool
	 */
	public function exists( $value = '', $field = 'term_id' ) {

		$columns = $this->get_columns();
		if ( ! array_key_exists( $field, $columns ) ) {
			return false;
		}

		return (bool) $this->get_column_by( 'term_id', $field, $value );

	}

	/**
	 * Retrieves a single term from the database.
	 *
	 * @param string $field The column to search.
	 * @param int    $value The value to check against the column.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return object|false Upon success, an object of the term. Upon failure, false.
	 */
	public function get_term_by( $field = 'ID', $value = 0 ) {

		global $wpdb;

		if ( empty( $field ) || empty( $value ) ) {
			return false;
		}

		if ( $field == 'ID' ) {
			if ( ! is_numeric( $value ) ) {
				return false;
			}

			$value = intval( $value );

			if ( $value < 1 ) {
				return false;
			}
		}

		if ( ! $value ) {
			return false;
		}

		switch ( $field ) {

			case 'ID' :
				$db_field = 'term_id';
				break;

			case 'name' :
				$db_field = 'name';
				$value    = wp_strip_all_tags( $value );
				break;

			default :
				return false;

		}

		if ( ! $term = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $db_field = %s LIMIT 1", $value ) ) ) {

			return false;

		}

		return $term;

	}

	/**
	 * Retrieve terms from the database.
	 *
	 * @param array $args Query arguments.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array Array of objects.
	 */
	public function get_terms( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'term_id' => false,
			'number'  => 20,
			'offset'  => 0,
			'name'    => false,
			'type'    => false,
			'count'   => false,
			'orderby' => 'ID',
			'order'   => 'DESC'
		);

		$args = wp_parse_args( $args, $defaults );

		// Big ass number to get them all.
		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific terms.
		if ( ! empty( $args['term_id'] ) ) {
			if ( is_array( $args['term_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['term_id'] ) );
			} else {
				$ids = intval( $args['term_id'] );
			}
			$where .= " AND `term_id` IN( {$ids} ) ";
		}

		// Terms with a specific name.
		if ( ! empty( $args['name'] ) ) {
			$where .= $wpdb->prepare( " AND `name` LIKE '%%%%" . '%s' . "%%%%' ", wp_strip_all_tags( $args['name'] ) );
		}

		// Terms with a specific type.
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( " AND `type` LIKE '%s' ", wp_strip_all_tags( $args['type'] ) );
		}

		// Terms with a specific count.
		if ( $args['count'] !== false ) {
			if ( is_numeric( $args['count'] ) ) {
				$where .= $wpdb->prepare( " AND `count` LIKE '%d' ", absint( $args['count'] ) );
			} elseif ( is_array( $args['count'] ) ) {
				if ( array_key_exists( 'greater_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` > '%d' ", absint( $args['count'] ) );
				}

				if ( array_key_exists( 'less_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` < '%d' ", absint( $args['count'] ) );
				}
			}
		}

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'term_id' : $args['orderby'];

		$cache_key = md5( 'bdb_book_terms_' . serialize( $args ) );

		$terms = wp_cache_get( $cache_key, 'book_terms' );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( $terms === false ) {
			$query = $wpdb->prepare( "SELECT * FROM  $this->table_name $join $where GROUP BY $this->primary_key ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) );
			$terms = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $terms, 'book_terms', 3600 );
		}

		return $terms;

	}

	/**
	 * Count the total number of terms in the database.
	 *
	 * @param array $args Query arguments.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return array Array of objects.
	 */
	public function count( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'term_id' => false,
			'number'  => 20,
			'offset'  => 0,
			'name'    => false,
			'type'    => false,
			'count'   => false
		);

		$args = wp_parse_args( $args, $defaults );

		// Big ass number to get them all.
		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$join  = '';
		$where = ' WHERE 1=1 ';

		// Specific terms.
		if ( ! empty( $args['term_id'] ) ) {
			if ( is_array( $args['term_id'] ) ) {
				$ids = implode( ',', array_map( 'intval', $args['term_id'] ) );
			} else {
				$ids = intval( $args['term_id'] );
			}
			$where .= " AND `term_id` IN( {$ids} ) ";
		}

		// Terms with a specific name.
		if ( ! empty( $args['name'] ) ) {
			$where .= $wpdb->prepare( " AND `name` LIKE '%%%%" . '%s' . "%%%%' ", wp_strip_all_tags( $args['name'] ) );
		}

		// Terms with a specific type.
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( " AND `type` LIKE '%s' ", wp_strip_all_tags( $args['type'] ) );
		}

		// Terms with a specific count.
		if ( $args['count'] !== false ) {
			if ( is_numeric( $args['count'] ) ) {
				$where .= $wpdb->prepare( " AND `count` LIKE '%d' ", absint( $args['count'] ) );
			} elseif ( is_array( $args['count'] ) ) {
				if ( array_key_exists( 'greater_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` > '%d' ", absint( $args['count'] ) );
				}

				if ( array_key_exists( 'less_than', $args['count'] ) ) {
					$where .= $wpdb->prepare( " AND `count` < '%d' ", absint( $args['count'] ) );
				}
			}
		}

		$cache_key = md5( 'bdb_book_terms_count_' . serialize( $args ) );

		$terms = wp_cache_get( $cache_key, 'book_terms' );

		if ( $terms === false ) {
			$query = "SELECT COUNT($this->primary_key) FROM " . $this->table_name . "{$join} {$where};";
			$terms = $wpdb->get_results( $query );
			wp_cache_set( $cache_key, $terms, 'book_terms', 3600 );
		}

		return $terms;

	}

	/**
	 * Create the table.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function create_table() {

		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		term_id bigint(20) NOT NULL AUTO_INCREMENT,
		type varchar(32) NOT NULL,
		name varchar(200) NOT NULL,
		description longtext NOT NULL,
		image longtext NOT NULL,
		links longtext NOT NULL,
		count bigint(20) NOT NULL,
		PRIMARY KEY  (term_id),
		INDEX type (type),
		INDEX name (name)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );

	}

}