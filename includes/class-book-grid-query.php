<?php
/**
 * Book Grid Query
 *
 * Used in the `[book-grid]` shortcode.
 *
 * @package   book-database
 * @copyright Copyright (c) 2019, Ashley Gibson
 * @license   GPL2+
 */

namespace Book_Database;

/**
 * Class Book_Grid_Query
 * @package Book_Database
 */
class Book_Grid_Query extends Book_Reviews_Query {

	/**
	 * Parse the query args from the shortcode attributes.
	 *
	 * @param array $atts Shortcode attributes.
	 */
	protected function parse_args( $atts = array() ) {

		$this->args['number']         = $atts['per-page'] ?? 20;
		$this->args['offset']         = ( $this->current_page * $this->args['number'] ) - $this->args['number'];
		$this->per_page               = $this->args['number'];
		$this->args['include_rating'] = ! empty( $atts['show-ratings'] );
		$this->args['orderby']        = $atts['orderby'];
		$this->args['order']          = 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC';

		if ( ! empty( $atts['ids'] ) ) {
			$ids                      = array_map( 'trim', explode( ',', $atts['ids'] ) );
			$this->args['book_query'][] = array(
				'field'    => 'id',
				'value'    => $ids,
				'operator' => 'IN'
			);
		}

		if ( ! empty( $atts['author'] ) ) {
			$this->args['author_query'][] = array(
				'field' => 'name',
				'value' => $atts['author']
			);
		}

		if ( ! empty( $atts['series'] ) ) {
			$this->args['series_query'][] = array(
				'field' => 'name',
				'value' => $atts['series']
			);
		}

		if ( ! empty( $atts['rating'] ) ) {
			$this->args['reading_log_query'][] = array(
				'field' => 'rating',
				'value' => floatval( $atts['rating'] )
			);
		}

		if ( ! empty( $atts['pub-date-after'] ) || ! empty( $atts['pub-date-before'] ) || ! empty( $atts['pub-year'] ) ) {
			$date_query = array();

			if ( ! empty( $atts['pub-date-after'] ) ) {
				$date_query['after'] = date( 'Y-m-d 00:00:00', strtotime( $atts['pub-date-after'] ) );
			}
			if ( ! empty( $atts['pub-date-before'] ) ) {
				$date_query['before'] = date( 'Y-m-d 23:59:59', strtotime( $atts['pub-date-before'] ) );
			}
			if ( ! empty( $atts['pub-year'] ) ) {
				$date_query['year'] = absint( $atts['pub-year'] );
			}

			$this->args['book_query'][] = array(
				'field' => 'pub_date',
				'value' => $date_query
			);
		}

		// Read vs unread
		if ( ! empty( $atts['read-status'] ) ) {
			switch ( $atts['read-status'] ) {
				case 'reading' :
					$this->args['reading_log_query'][] = array(
						'field'    => 'date_started',
						'value'    => null,
						'operator' => 'IS NOT'
					);
					$this->args['reading_log_query'][] = array(
						'field'    => 'date_finished',
						'value'    => null,
						'operator' => 'IS'
					);
					break;

				case 'read' :
					$this->args['reading_log_query'][] = array(
						'field' => 'date_started',
						'value'    => null,
						'operator' => 'IS NOT'
					);
					$this->args['reading_log_query'][] = array(
						'field' => 'date_finished',
						'value'    => null,
						'operator' => 'IS NOT'
					);
					break;

				case 'unread' :
					$this->args['unread'] = true;
					break;
			}
		}

		// Reviews only.
		if ( ! empty( $atts['reviews-only'] ) ) {
			$this->args['review_query'][] = array(
				'field'    => 'date_published',
				'value'    => null,
				'operator' => 'IS NOT'
			);
		}

		// Include review link (left join).
		if ( ! empty( $atts['show-review-link'] ) ) {
			$this->args['include_review'] = true;
		}

		// Review dates.
		if ( ! empty( $atts['review-date-after'] ) || ! empty( $atts['review-date-before'] ) ) {
			$date_query = array();

			if ( ! empty( $atts['review-date-after'] ) ) {
				$date_query['after'] = date( 'Y-m-d 00:00:00', strtotime( $atts['review-date-after'] ) );
			}
			if ( ! empty( $atts['review-date-before'] ) ) {
				$date_query['before'] = date( 'Y-m-d 23:59:59', strtotime( $atts['review-date-before'] ) );
			}

			$this->args['review_query'][] = array(
				'field' => 'date_published',
				'value' => $date_query
			);
		}

		// Taxonomies
		foreach ( get_book_taxonomies( array( 'fields' => 'slug' ) ) as $taxonomy_slug ) {
			if ( ! empty( $atts[ $taxonomy_slug ] ) && 'any' !== $atts[ $taxonomy_slug ] ) {
				$this->args['tax_query'][] = array(
					'taxonomy' => sanitize_text_field( $taxonomy_slug ),
					'field'    => 'id',
					'terms'    => absint( $atts[ $taxonomy_slug ] )
				);
			}
		}

	}

	/**
	 * Get the results
	 *
	 * @return object[]
	 */
	public function get_results() {

		$query   = new Books_Query();
		$results = $query->get_books( $this->args );

		$count_args          = $this->args;
		$count_args['count'] = true;
		$this->total_results = $query->get_books( $count_args );

		return $results;

	}

}
