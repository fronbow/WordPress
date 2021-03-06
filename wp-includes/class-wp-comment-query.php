<?php
/**
 * Comments API: WP_Comment_Query class
 *
 * @package WordPress
 * @subpackage Comment
 * @since 4.4.0
 */

/**
 * Core class used for querying comments.
 *
 * @since 3.1.0
 *
 * @see WP_Comment_Query::__construct() for accepted arguments.
 */
class WP_Comment_Query {

	/**
	 * SQL for database query.
	 *
	 * @since 4.0.1
	 * @access public
	 * @var string
	 */
	public $request;

	/**
	 * Metadata query container
	 *
	 * @since 3.5.0
	 * @access public
	 * @var object WP_Meta_Query
	 */
	public $meta_query = false;

	/**
	 * Date query container
	 *
	 * @since 3.7.0
	 * @access public
	 * @var object WP_Date_Query
	 */
	public $date_query = false;

	/**
	 * Query vars set by the user.
	 *
	 * @since 3.1.0
	 * @access public
	 * @var array
	 */
	public $query_vars;

	/**
	 * Default values for query vars.
	 *
	 * @since 4.2.0
	 * @access public
	 * @var array
	 */
	public $query_var_defaults;

	/**
	 * List of comments located by the query.
	 *
	 * @since 4.0.0
	 * @access public
	 * @var array
	 */
	public $comments;

	/**
	 * Make private/protected methods readable for backwards compatibility.
	 *
	 * @since 4.0.0
	 * @access public
	 *
	 * @param callable $name      Method to call.
	 * @param array    $arguments Arguments to pass when calling.
	 * @return mixed|false Return value of the callback, false otherwise.
	 */
	public function __call( $name, $arguments ) {
		if ( 'get_search_sql' === $name ) {
			return call_user_func_array( array( $this, $name ), $arguments );
		}
		return false;
	}

	/**
	 * Constructor.
	 *
	 * Sets up the comment query, based on the query vars passed.
	 *
	 * @since 4.2.0
	 * @since 4.4.0 `$parent__in` and `$parent__not_in` were added.
	 * @since 4.4.0 Order by `comment__in` was added.
	 * @access public
	 *
	 * @param string|array $query {
	 *     Optional. Array or query string of comment query parameters. Default empty.
	 *
	 *     @type string       $author_email        Comment author email address. Default empty.
	 *     @type array        $author__in          Array of author IDs to include comments for. Default empty.
	 *     @type array        $author__not_in      Array of author IDs to exclude comments for. Default empty.
	 *     @type array        $comment__in         Array of comment IDs to include. Default empty.
	 *     @type array        $comment__not_in     Array of comment IDs to exclude. Default empty.
	 *     @type bool         $count               Whether to return a comment count (true) or array of comment
	 *                                             objects (false). Default false.
	 *     @type array        $date_query          Date query clauses to limit comments by. See WP_Date_Query.
	 *                                             Default null.
	 *     @type string       $fields              Comment fields to return. Accepts 'ids' for comment IDs only or
	 *                                             empty for all fields. Default empty.
	 *     @type int          $ID                  Currently unused.
	 *     @type array        $include_unapproved  Array of IDs or email addresses of users whose unapproved comments
	 *                                             will be returned by the query regardless of `$status`. Default empty.
	 *     @type int          $karma               Karma score to retrieve matching comments for. Default empty.
	 *     @type string       $meta_key            Include comments with a matching comment meta key. Default empty.
	 *     @type string       $meta_value          Include comments with a matching comment meta value. Requires
	 *                                             `$meta_key` to be set. Default empty.
	 *     @type array        $meta_query          Meta query clauses to limit retrieved comments by.
	 *                                             See WP_Meta_Query. Default empty.
	 *     @type int          $number              Maximum number of comments to retrieve. Default null (no limit).
	 *     @type int          $offset              Number of comments to offset the query. Used to build LIMIT clause.
	 *                                             Default 0.
	 *     @type string|array $orderby             Comment status or array of statuses. To use 'meta_value' or
	 *                                             'meta_value_num', `$meta_key` must also be defined. To sort by
	 *                                             a specific `$meta_query` clause, use that clause's array key.
	 *                                             Accepts 'comment_agent', 'comment_approved', 'comment_author',
	 *                                             'comment_author_email', 'comment_author_IP',
	 *                                             'comment_author_url', 'comment_content', 'comment_date',
	 *                                             'comment_date_gmt', 'comment_ID', 'comment_karma',
	 *                                             'comment_parent', 'comment_post_ID', 'comment_type', 'user_id',
	 *                                             'comment__in', 'meta_value', 'meta_value_num', the value of $meta_key, and the
	 *                                             array keys of `$meta_query`. Also accepts false, an empty array,
	 *                                             or 'none' to disable `ORDER BY` clause.
	 *                                             Default: 'comment_date_gmt'.
	 *     @type string       $order               How to order retrieved comments. Accepts 'ASC', 'DESC'.
	 *                                             Default: 'DESC'.
	 *     @type int          $parent              Parent ID of comment to retrieve children of. Default empty.
	 *     @type array        $parent__in          Array of parent IDs of comments to retrieve children for. Default empty.
	 *     @type array        $parent__not_in      Array of parent IDs of comments *not* to retrieve children for. Default empty.
	 *     @type array        $post_author__in     Array of author IDs to retrieve comments for. Default empty.
	 *     @type array        $post_author__not_in Array of author IDs *not* to retrieve comments for. Default empty.
	 *     @type int          $post_ID             Currently unused.
	 *     @type int          $post_id             Limit results to those affiliated with a given post ID. Default 0.
	 *     @type array        $post__in            Array of post IDs to include affiliated comments for. Default empty.
	 *     @type array        $post__not_in        Array of post IDs to exclude affiliated comments for. Default empty.
	 *     @type int          $post_author         Comment author ID to limit results by. Default empty.
	 *     @type string       $post_status         Post status to retrieve affiliated comments for. Default empty.
	 *     @type string       $post_type           Post type to retrieve affiliated comments for. Default empty.
	 *     @type string       $post_name           Post name to retrieve affiliated comments for. Default empty.
	 *     @type int          $post_parent         Post parent ID to retrieve affiliated comments for. Default empty.
	 *     @type string       $search              Search term(s) to retrieve matching comments for. Default empty.
	 *     @type string       $status              Comment status to limit results by. Accepts 'hold'
	 *                                             (`comment_status=0`), 'approve' (`comment_status=1`), 'all', or a
	 *                                             custom comment status. Default 'all'.
	 *     @type string|array $type                Include comments of a given type, or array of types. Accepts
	 *                                             'comment', 'pings' (includes 'pingback' and 'trackback'), or any
	 *                                             custom type string. Default empty.
	 *     @type array        $type__in            Include comments from a given array of comment types. Default empty.
	 *     @type array        $type__not_in        Exclude comments from a given array of comment types. Default empty.
	 *     @type int          $user_id             Include comments for a specific user ID. Default empty.
	 * }
	 */
	public function __construct( $query = '' ) {
		$this->query_var_defaults = array(
			'author_email' => '',
			'author__in' => '',
			'author__not_in' => '',
			'include_unapproved' => '',
			'fields' => '',
			'ID' => '',
			'comment__in' => '',
			'comment__not_in' => '',
			'karma' => '',
			'number' => '',
			'offset' => '',
			'orderby' => '',
			'order' => 'DESC',
			'parent' => '',
			'post_author__in' => '',
			'post_author__not_in' => '',
			'post_ID' => '',
			'post_id' => 0,
			'post__in' => '',
			'post__not_in' => '',
			'post_author' => '',
			'post_name' => '',
			'post_parent' => '',
			'post_status' => '',
			'post_type' => '',
			'status' => 'all',
			'type' => '',
			'type__in' => '',
			'type__not_in' => '',
			'user_id' => '',
			'search' => '',
			'count' => false,
			'meta_key' => '',
			'meta_value' => '',
			'meta_query' => '',
			'date_query' => null, // See WP_Date_Query
		);

		if ( ! empty( $query ) ) {
			$this->query( $query );
		}
	}

	/**
	 * Parse arguments passed to the comment query with default query parameters.
	 *
	 * @since  4.2.0 Extracted from WP_Comment_Query::query().
	 *
	 * @access public
	 *
	 * @param string|array $query WP_Comment_Query arguments. See WP_Comment_Query::__construct()
	 */
	public function parse_query( $query = '' ) {
		if ( empty( $query ) ) {
			$query = $this->query_vars;
		}

		$this->query_vars = wp_parse_args( $query, $this->query_var_defaults );
		do_action_ref_array( 'parse_comment_query', array( &$this ) );
	}

	/**
	 * Sets up the WordPress query for retrieving comments.
	 *
	 * @since 3.1.0
	 * @since 4.1.0 Introduced 'comment__in', 'comment__not_in', 'post_author__in',
	 *              'post_author__not_in', 'author__in', 'author__not_in', 'post__in',
	 *              'post__not_in', 'include_unapproved', 'type__in', and 'type__not_in'
	 *              arguments to $query_vars.
	 * @since 4.2.0 Moved parsing to WP_Comment_Query::parse_query().
	 * @access public
	 *
	 * @param string|array $query Array or URL query string of parameters.
	 * @return array|int List of comments, or number of comments when 'count' is passed as a query var.
	 */
	public function query( $query ) {
		$this->query_vars = wp_parse_args( $query );
		return $this->get_comments();
	}

	/**
	 * Get a list of comments matching the query vars.
	 *
	 * @since 4.2.0
	 * @access public
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @return int|array The list of comments.
	 */
	public function get_comments() {
		global $wpdb;

		$groupby = '';

		$this->parse_query();

		// Parse meta query
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->query_vars );

		/**
		 * Fires before comments are retrieved.
		 *
		 * @since 3.1.0
		 *
		 * @param WP_Comment_Query &$this Current instance of WP_Comment_Query, passed by reference.
		 */
		do_action_ref_array( 'pre_get_comments', array( &$this ) );

		// Reparse query vars, in case they were modified in a 'pre_get_comments' callback.
		$this->meta_query->parse_query_vars( $this->query_vars );
		if ( ! empty( $this->meta_query->queries ) ) {
			$meta_query_clauses = $this->meta_query->get_sql( 'comment', $wpdb->comments, 'comment_ID', $this );
		}

		// $args can include anything. Only use the args defined in the query_var_defaults to compute the key.
		$key = md5( serialize( wp_array_slice_assoc( $this->query_vars, array_keys( $this->query_var_defaults ) ) ) );
		$last_changed = wp_cache_get( 'last_changed', 'comment' );
		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, 'comment' );
		}
		$cache_key = "get_comments:$key:$last_changed";

		if ( $cache = wp_cache_get( $cache_key, 'comment' ) ) {
			$this->comments = $cache;
			return $this->comments;
		}

		$where = array();

		// Assemble clauses related to 'comment_approved'.
		$approved_clauses = array();

		// 'status' accepts an array or a comma-separated string.
		$status_clauses = array();
		$statuses = $this->query_vars['status'];
		if ( ! is_array( $statuses ) ) {
			$statuses = preg_split( '/[\s,]+/', $statuses );
		}

		// 'any' overrides other statuses.
		if ( ! in_array( 'any', $statuses ) ) {
			foreach ( $statuses as $status ) {
				switch ( $status ) {
					case 'hold' :
						$status_clauses[] = "comment_approved = '0'";
						break;

					case 'approve' :
						$status_clauses[] = "comment_approved = '1'";
						break;

					case 'all' :
					case '' :
						$status_clauses[] = "( comment_approved = '0' OR comment_approved = '1' )";
						break;

					default :
						$status_clauses[] = $wpdb->prepare( "comment_approved = %s", $status );
						break;
				}
			}

			if ( ! empty( $status_clauses ) ) {
				$approved_clauses[] = '( ' . implode( ' OR ', $status_clauses ) . ' )';
			}
		}

		// User IDs or emails whose unapproved comments are included, regardless of $status.
		if ( ! empty( $this->query_vars['include_unapproved'] ) ) {
			$include_unapproved = $this->query_vars['include_unapproved'];

			// Accepts arrays or comma-separated strings.
			if ( ! is_array( $include_unapproved ) ) {
				$include_unapproved = preg_split( '/[\s,]+/', $include_unapproved );
			}

			$unapproved_ids = $unapproved_emails = array();
			foreach ( $include_unapproved as $unapproved_identifier ) {
				// Numeric values are assumed to be user ids.
				if ( is_numeric( $unapproved_identifier ) ) {
					$approved_clauses[] = $wpdb->prepare( "( user_id = %d AND comment_approved = '0' )", $unapproved_identifier );

				// Otherwise we match against email addresses.
				} else {
					$approved_clauses[] = $wpdb->prepare( "( comment_author_email = %s AND comment_approved = '0' )", $unapproved_identifier );
				}
			}
		}

		// Collapse comment_approved clauses into a single OR-separated clause.
		if ( ! empty( $approved_clauses ) ) {
			if ( 1 === count( $approved_clauses ) ) {
				$where[] = $approved_clauses[0];
			} else {
				$where[] = '( ' . implode( ' OR ', $approved_clauses ) . ' )';
			}
		}

		$order = ( 'ASC' == strtoupper( $this->query_vars['order'] ) ) ? 'ASC' : 'DESC';

		// Disable ORDER BY with 'none', an empty array, or boolean false.
		if ( in_array( $this->query_vars['orderby'], array( 'none', array(), false ), true ) ) {
			$orderby = '';
		} elseif ( ! empty( $this->query_vars['orderby'] ) ) {
			$ordersby = is_array( $this->query_vars['orderby'] ) ?
				$this->query_vars['orderby'] :
				preg_split( '/[,\s]/', $this->query_vars['orderby'] );

			$orderby_array = array();
			$found_orderby_comment_ID = false;
			foreach ( $ordersby as $_key => $_value ) {
				if ( ! $_value ) {
					continue;
				}

				if ( is_int( $_key ) ) {
					$_orderby = $_value;
					$_order = $order;
				} else {
					$_orderby = $_key;
					$_order = $_value;
				}

				if ( ! $found_orderby_comment_ID && in_array( $_orderby, array( 'comment_ID', 'comment__in' ) ) ) {
					$found_orderby_comment_ID = true;
				}

				$parsed = $this->parse_orderby( $_orderby );

				if ( ! $parsed ) {
					continue;
				}

				if ( 'comment__in' === $_orderby ) {
					$orderby_array[] = $parsed;
					continue;
				}

				$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
			}

			// If no valid clauses were found, order by comment_date_gmt.
			if ( empty( $orderby_array ) ) {
				$orderby_array[] = "$wpdb->comments.comment_date_gmt $order";
			}

			// To ensure determinate sorting, always include a comment_ID clause.
			if ( ! $found_orderby_comment_ID ) {
				$comment_ID_order = '';

				// Inherit order from comment_date or comment_date_gmt, if available.
				foreach ( $orderby_array as $orderby_clause ) {
					if ( preg_match( '/comment_date(?:_gmt)*\ (ASC|DESC)/', $orderby_clause, $match ) ) {
						$comment_ID_order = $match[1];
						break;
					}
				}

				// If no date-related order is available, use the date from the first available clause.
				if ( ! $comment_ID_order ) {
					foreach ( $orderby_array as $orderby_clause ) {
						if ( false !== strpos( 'ASC', $orderby_clause ) ) {
							$comment_ID_order = 'ASC';
						} else {
							$comment_ID_order = 'DESC';
						}

						break;
					}
				}

				// Default to DESC.
				if ( ! $comment_ID_order ) {
					$comment_ID_order = 'DESC';
				}

				$orderby_array[] = "$wpdb->comments.comment_ID $comment_ID_order";
			}

			$orderby = implode( ', ', $orderby_array );
		} else {
			$orderby = "$wpdb->comments.comment_date_gmt $order";
		}

		$number = absint( $this->query_vars['number'] );
		$offset = absint( $this->query_vars['offset'] );

		if ( ! empty( $number ) ) {
			if ( $offset ) {
				$limits = 'LIMIT ' . $offset . ',' . $number;
			} else {
				$limits = 'LIMIT ' . $number;
			}
		} else {
			$limits = '';
		}

		if ( $this->query_vars['count'] ) {
			$fields = 'COUNT(*)';
		} else {
			switch ( strtolower( $this->query_vars['fields'] ) ) {
				case 'ids':
					$fields = "$wpdb->comments.comment_ID";
					break;
				default:
					$fields = "*";
					break;
			}
		}

		$join = '';

		$post_id = absint( $this->query_vars['post_id'] );
		if ( ! empty( $post_id ) ) {
			$where[] = $wpdb->prepare( 'comment_post_ID = %d', $post_id );
		}

		// Parse comment IDs for an IN clause.
		if ( ! empty( $this->query_vars['comment__in'] ) ) {
			$where[] = "$wpdb->comments.comment_ID IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['comment__in'] ) ) . ' )';
		}

		// Parse comment IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['comment__not_in'] ) ) {
			$where[] = "$wpdb->comments.comment_ID NOT IN ( " . implode( ',', wp_parse_id_list( $this->query_vars['comment__not_in'] ) ) . ' )';
		}

		// Parse comment parent IDs for an IN clause.
		if ( ! empty( $this->query_vars['parent__in'] ) ) {
			$where[] = 'comment_parent IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['parent__in'] ) ) . ' )';
		}

		// Parse comment parent IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['parent__not_in'] ) ) {
			$where[] = 'comment_parent NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['parent__not_in'] ) ) . ' )';
		}

		// Parse comment post IDs for an IN clause.
		if ( ! empty( $this->query_vars['post__in'] ) ) {
			$where[] = 'comment_post_ID IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post__in'] ) ) . ' )';
		}

		// Parse comment post IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['post__not_in'] ) ) {
			$where[] = 'comment_post_ID NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post__not_in'] ) ) . ' )';
		}

		if ( '' !== $this->query_vars['author_email'] ) {
			$where[] = $wpdb->prepare( 'comment_author_email = %s', $this->query_vars['author_email'] );
		}

		if ( '' !== $this->query_vars['karma'] ) {
			$where[] = $wpdb->prepare( 'comment_karma = %d', $this->query_vars['karma'] );
		}

		// Filtering by comment_type: 'type', 'type__in', 'type__not_in'.
		$raw_types = array(
			'IN' => array_merge( (array) $this->query_vars['type'], (array) $this->query_vars['type__in'] ),
			'NOT IN' => (array) $this->query_vars['type__not_in'],
		);

		$comment_types = array();
		foreach ( $raw_types as $operator => $_raw_types ) {
			$_raw_types = array_unique( $_raw_types );

			foreach ( $_raw_types as $type ) {
				switch ( $type ) {
					// An empty translates to 'all', for backward compatibility
					case '':
					case 'all' :
						break;

					case 'comment':
					case 'comments':
						$comment_types[ $operator ][] = "''";
						break;

					case 'pings':
						$comment_types[ $operator ][] = "'pingback'";
						$comment_types[ $operator ][] = "'trackback'";
						break;

					default:
						$comment_types[ $operator ][] = $wpdb->prepare( '%s', $type );
						break;
				}
			}

			if ( ! empty( $comment_types[ $operator ] ) ) {
				$types_sql = implode( ', ', $comment_types[ $operator ] );
				$where[] = "comment_type $operator ($types_sql)";
			}
		}

		if ( '' !== $this->query_vars['parent'] ) {
			$where[] = $wpdb->prepare( 'comment_parent = %d', $this->query_vars['parent'] );
		}

		if ( is_array( $this->query_vars['user_id'] ) ) {
			$where[] = 'user_id IN (' . implode( ',', array_map( 'absint', $this->query_vars['user_id'] ) ) . ')';
		} elseif ( '' !== $this->query_vars['user_id'] ) {
			$where[] = $wpdb->prepare( 'user_id = %d', $this->query_vars['user_id'] );
		}

		if ( '' !== $this->query_vars['search'] ) {
			$search_sql = $this->get_search_sql(
				$this->query_vars['search'],
				array( 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_author_IP', 'comment_content' )
			);

			// Strip leading 'AND'.
			$where[] = preg_replace( '/^\s*AND\s*/', '', $search_sql );
		}

		// If any post-related query vars are passed, join the posts table.
		$join_posts_table = false;
		$plucked = wp_array_slice_assoc( $this->query_vars, array( 'post_author', 'post_name', 'post_parent', 'post_status', 'post_type' ) );
		$post_fields = array_filter( $plucked );

		if ( ! empty( $post_fields ) ) {
			$join_posts_table = true;
			foreach ( $post_fields as $field_name => $field_value ) {
				// $field_value may be an array.
				$esses = array_fill( 0, count( (array) $field_value ), '%s' );
				$where[] = $wpdb->prepare( " {$wpdb->posts}.{$field_name} IN (" . implode( ',', $esses ) . ')', $field_value );
			}
		}

		// Comment author IDs for an IN clause.
		if ( ! empty( $this->query_vars['author__in'] ) ) {
			$where[] = 'user_id IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['author__in'] ) ) . ' )';
		}

		// Comment author IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['author__not_in'] ) ) {
			$where[] = 'user_id NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['author__not_in'] ) ) . ' )';
		}

		// Post author IDs for an IN clause.
		if ( ! empty( $this->query_vars['post_author__in'] ) ) {
			$join_posts_table = true;
			$where[] = 'post_author IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post_author__in'] ) ) . ' )';
		}

		// Post author IDs for a NOT IN clause.
		if ( ! empty( $this->query_vars['post_author__not_in'] ) ) {
			$join_posts_table = true;
			$where[] = 'post_author NOT IN ( ' . implode( ',', wp_parse_id_list( $this->query_vars['post_author__not_in'] ) ) . ' )';
		}

		if ( $join_posts_table ) {
			$join = "JOIN $wpdb->posts ON $wpdb->posts.ID = $wpdb->comments.comment_post_ID";
		}

		if ( ! empty( $meta_query_clauses ) ) {
			$join .= $meta_query_clauses['join'];

			// Strip leading 'AND'.
			$where[] = preg_replace( '/^\s*AND\s*/', '', $meta_query_clauses['where'] );

			if ( ! $this->query_vars['count'] ) {
				$groupby = "{$wpdb->comments}.comment_ID";
			}
		}

		$date_query = $this->query_vars['date_query'];
		if ( ! empty( $date_query ) && is_array( $date_query ) ) {
			$date_query_object = new WP_Date_Query( $date_query, 'comment_date' );
			$where[] = preg_replace( '/^\s*AND\s*/', '', $date_query_object->get_sql() );
		}

		$where = implode( ' AND ', $where );

		$pieces = array( 'fields', 'join', 'where', 'orderby', 'limits', 'groupby' );
		/**
		 * Filter the comment query clauses.
		 *
		 * @since 3.1.0
		 *
		 * @param array            $pieces A compacted array of comment query clauses.
		 * @param WP_Comment_Query &$this  Current instance of WP_Comment_Query, passed by reference.
		 */
		$clauses = apply_filters_ref_array( 'comments_clauses', array( compact( $pieces ), &$this ) );

		$fields = isset( $clauses[ 'fields' ] ) ? $clauses[ 'fields' ] : '';
		$join = isset( $clauses[ 'join' ] ) ? $clauses[ 'join' ] : '';
		$where = isset( $clauses[ 'where' ] ) ? $clauses[ 'where' ] : '';
		$orderby = isset( $clauses[ 'orderby' ] ) ? $clauses[ 'orderby' ] : '';
		$limits = isset( $clauses[ 'limits' ] ) ? $clauses[ 'limits' ] : '';
		$groupby = isset( $clauses[ 'groupby' ] ) ? $clauses[ 'groupby' ] : '';

		if ( $where ) {
			$where = 'WHERE ' . $where;
		}

		if ( $groupby ) {
			$groupby = 'GROUP BY ' . $groupby;
		}

		if ( $orderby ) {
			$orderby = "ORDER BY $orderby";
		}

		$this->request = "SELECT $fields FROM $wpdb->comments $join $where $groupby $orderby $limits";

		if ( $this->query_vars['count'] ) {
			return $wpdb->get_var( $this->request );
		}

		if ( 'ids' == $this->query_vars['fields'] ) {
			$this->comments = $wpdb->get_col( $this->request );
			return array_map( 'intval', $this->comments );
		}

		$results = $wpdb->get_results( $this->request );
		/**
		 * Filter the comment query results.
		 *
		 * @since 3.1.0
		 *
		 * @param array            $results  An array of comments.
		 * @param WP_Comment_Query &$this    Current instance of WP_Comment_Query, passed by reference.
		 */
		$_comments = apply_filters_ref_array( 'the_comments', array( $results, &$this ) );

		// Convert to WP_Comment instances
		$comments = array_map( 'get_comment', $_comments );

		wp_cache_add( $cache_key, $comments, 'comment' );
		if ( '*' === $fields ) {
			update_comment_cache( $comments );
		}

		$this->comments = $comments;
		return $this->comments;
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @global wpdb $wpdb
	 *
	 * @param string $string
	 * @param array $cols
	 * @return string
	 */
	protected function get_search_sql( $string, $cols ) {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $string ) . '%';

		$searches = array();
		foreach ( $cols as $col ) {
			$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
		}

		return ' AND (' . implode(' OR ', $searches) . ')';
	}

	/**
	 * Parse and sanitize 'orderby' keys passed to the comment query.
	 *
	 * @since 4.2.0
	 * @access protected
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $orderby Alias for the field to order by.
	 * @return string|false Value to used in the ORDER clause. False otherwise.
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		$allowed_keys = array(
			'comment_agent',
			'comment_approved',
			'comment_author',
			'comment_author_email',
			'comment_author_IP',
			'comment_author_url',
			'comment_content',
			'comment_date',
			'comment_date_gmt',
			'comment_ID',
			'comment_karma',
			'comment_parent',
			'comment_post_ID',
			'comment_type',
			'user_id',
		);

		if ( ! empty( $this->query_vars['meta_key'] ) ) {
			$allowed_keys[] = $this->query_vars['meta_key'];
			$allowed_keys[] = 'meta_value';
			$allowed_keys[] = 'meta_value_num';
		}

		$meta_query_clauses = $this->meta_query->get_clauses();
		if ( $meta_query_clauses ) {
			$allowed_keys = array_merge( $allowed_keys, array_keys( $meta_query_clauses ) );
		}

		$parsed = false;
		if ( $orderby == $this->query_vars['meta_key'] || $orderby == 'meta_value' ) {
			$parsed = "$wpdb->commentmeta.meta_value";
		} elseif ( $orderby == 'meta_value_num' ) {
			$parsed = "$wpdb->commentmeta.meta_value+0";
		} elseif ( $orderby == 'comment__in' ) {
			$comment__in = implode( ',', array_map( 'absint', $this->query_vars['comment__in'] ) );
			$parsed = "FIELD( {$wpdb->comments}.comment_ID, $comment__in )";
		} elseif ( in_array( $orderby, $allowed_keys ) ) {

			if ( isset( $meta_query_clauses[ $orderby ] ) ) {
				$meta_clause = $meta_query_clauses[ $orderby ];
				$parsed = sprintf( "CAST(%s.meta_value AS %s)", esc_sql( $meta_clause['alias'] ), esc_sql( $meta_clause['cast'] ) );
			} else {
				$parsed = "$wpdb->comments.$orderby";
			}
		}

		return $parsed;
	}

	/**
	 * Parse an 'order' query variable and cast it to ASC or DESC as necessary.
	 *
	 * @since 4.2.0
	 * @access protected
	 *
	 * @param string $order The 'order' query variable.
	 * @return string The sanitized 'order' query variable.
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
}
