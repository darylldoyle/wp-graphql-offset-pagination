<?php
/**
 * Plugin Name: WP GraphQL Offset Pagination
 * Description: Offset Pagination support for the WPGraphQL plugin.
 * Author: Daryll Doyle
 * Author URI: https://www.enshrined.co.uk
 * Version: 0.0.1
 * Text Domain: wp-graphql-offset-pagination
 * Requires at least: 4.7.0
 * Tested up to: 4.7.1
 */

namespace Enshrined;

use Enshrined\OffsetPagination\OffsetPaginationType;
use WPGraphQL\Data\Connection\AbstractConnectionResolver;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


class OffsetPagination {

    /**
     * This holds the OffsetPaginationType array
     * @var $offset_pagination
     * @since 0.0.1
     */
    private static $offset_pagination;

    /**
     * OffsetPagination constructor.
     *
     * This hooks the plugin into the WPGraphQL Plugin
     *
     * @since 0.0.1
     */
    public function __construct() {

        /**
         * Setup plugin constants
         * @since 0.0.1
         */
        $this->setup_constants();

        /**
         * Included required files
         * @since 0.0.1
         */
        $this->includes();

        /**
         * Filter the query_args for the PostObjectQueryArgsType
         * @since 0.0.1
         */
        add_filter( 'graphql_input_fields', array( $this, 'add_input_fields' ), 10, 3 );

        /**
         * Filter the $allowed_custom_args for the PostObjectsConnectionResolver to map the
         * offsetPagination input to WP_Query terms
         * @since 0.0.1
         */
        add_filter( 'graphql_map_input_fields_to_wp_query', [ $this, 'map_input_fields' ], 10, 2 );

        /**
         * Filter the $allowed_custom_args for the UserConnectionResolver to map the
         * offsetPagination input to WP_User_Query terms
         * @since 0.0.1
         */
        add_filter( 'graphql_map_input_fields_to_wp_user_query', [ $this, 'map_input_fields' ], 10, 2 );

        /**
         * Filter the nodes so that we can return the correct number of items.
         * @since 0.0.1
         */
        add_filter( 'graphql_connection_nodes', [ $this, 'get_nodes' ], 10, 2 );

        /**
         * Register the extra pageInfo fields that will be used.
         * @since 0.0.1
         */
        add_filter( 'graphql_register_types', [ $this, 'register_page_info_fields' ], 10, 1 );

        /**
         * Filter the pageInfo that is returned to the connection.
         * @since 0.0.1
         */
        add_filter( 'graphql_connection_page_info', [ $this, 'get_page_info' ], 10, 2 );
    }

    /**
     * Setup plugin constants.
     *
     * @access private
     * @return void
     * @since  0.0.1
     */
    private function setup_constants() {

        // Plugin version.
        if ( ! defined( 'WPGRAPHQL_OFFSET_PAGINATION_VERSION' ) ) {
            define( 'WPGRAPHQL_OFFSET_PAGINATION_VERSION', '0.0.1' );
        }

        // Plugin Folder Path.
        if ( ! defined( 'WPGRAPHQL_OFFSET_PAGINATION_PLUGIN_DIR' ) ) {
            define( 'WPGRAPHQL_OFFSET_PAGINATION_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        }

        // Plugin Folder URL.
        if ( ! defined( 'WPGRAPHQL_OFFSET_PAGINATION_PLUGIN_URL' ) ) {
            define( 'WPGRAPHQL_OFFSET_PAGINATION_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        }

        // Plugin Root File.
        if ( ! defined( 'WPGRAPHQL_OFFSET_PAGINATION_PLUGIN_FILE' ) ) {
            define( 'WPGRAPHQL_OFFSET_PAGINATION_PLUGIN_FILE', __FILE__ );
        }

    }

    /**
     * Include required files.
     *
     * Uses composer's autoload
     *
     * @access private
     * @return void
     * @since  0.0.1
     */
    private function includes() {
        // Autoload Required Classes
        require_once( WPGRAPHQL_OFFSET_PAGINATION_PLUGIN_DIR . 'vendor/autoload.php' );
    }

    /**
     * add_input_fields
     *
     * This adds the OffsetPagination input fields
     *
     * @param array  $fields
     * @param string $type_name
     * @param array  $config
     *
     * @return mixed
     * @since 0.0.1
     */
    public function add_input_fields( $fields, $type_name, $config ) {
        // Hook into Root User queries.
        if ( 'RootQueryToUserConnectionWhereArgs' === $type_name ) {
            $fields['offsetPagination'] = self::offset_pagination( $type_name );
        }

        // Hook into post/page queries.
        if ( isset( $config['queryClass'] ) && 'WP_Query' === $config['queryClass'] ) {
            $fields['offsetPagination'] = self::offset_pagination( $type_name );
        }

        return $fields;
    }

    /**
     * This returns the definition for the OffsetPagination
     *
     * @param string $type_name
     *
     * @return array
     * @since 0.0.1
     */
    public static function offset_pagination( $type_name ) {
        if ( empty( self::$offset_pagination[ $type_name ] ) ) {
            self::$offset_pagination[ $type_name ] = new OffsetPaginationType( $type_name );
        }

        return ! empty( self::$offset_pagination[ $type_name ] ) ? self::$offset_pagination[ $type_name ] : null;
    }

    /**
     * map_input_fields
     *
     * This maps the offsetPagination input fields to the WP_Query
     *
     * @param $query_args
     * @param $input_args
     *
     * @return mixed
     * @since 0.0.1
     */
    public function map_input_fields( $query_args, $input_args ) {
        /**
         * OffsetPaginationQuery $args
         * This maps the GraphQL offsetPagination input to the WP_Query to handle pagination
         * @since 0.0.1
         */
        $offset_pagination = null;
        if ( ! empty( $input_args['offsetPagination'] ) ) {
            // Get the offsetPagination input
            $offset_pagination = $input_args['offsetPagination'];

            // If per_page is set and is numeric, we can use that. Otherwise default to 10.
            if ( isset( $offset_pagination['per_page'] ) && is_numeric( $offset_pagination['per_page'] ) ) {
                $query_args['number'] = $query_args['posts_per_page'] = intval( $offset_pagination['per_page'] );
            } else {
                $query_args['number'] = $query_args['posts_per_page'] = 10;
            }

            if ( isset( $offset_pagination['page'] ) && is_numeric( $offset_pagination['page'] ) ) {
                $query_args['offset'] = ( intval( $offset_pagination['page'] ) - 1 ) * intval( $query_args['number'] );
            } else {
                $query_args['offset'] = 0;
            }

            $query_args['number'] = $query_args['number'] + 1;

        }

        unset( $query_args['offsetPagination'] );

        /**
         * Retrun the $query_args
         * @since 0.0.1
         */
        return $query_args;
    }



    /**
     * Get the nodes from the query.
     *
     * We slice the array to match the amount of items that was asked for, as we over-fetched
     * by 1 item to calculate pageInfo.
     *
     * @param                            $nodes
     * @param AbstractConnectionResolver $resolver
     *
     * @return array
     * @since 0.0.1
     */
    public function get_nodes( $nodes, AbstractConnectionResolver $resolver ) {
        /**
         * We must use $resolver->get_items() rather than $nodes as $nodes has already
         * been sliced by the AbstractConnectionResolver
         */
        return array_slice( $resolver->get_items(), 0, $resolver->get_query_args()['posts_per_page'] );
    }

    /**
     * Register the new fields that will be used for pagination within WPPageInfo.
     *
     * @return void
     * @since 0.0.1
     */
    public function register_page_info_fields() {
        register_graphql_field( 'WPPageInfo', 'previousPage', [
            'type' => 'Int',
        ] );

        register_graphql_field( 'WPPageInfo', 'nextPage', [
            'type' => 'Int',
        ] );

        register_graphql_field( 'WPPageInfo', 'totalPages', [
            'type' => 'Int',
        ] );
    }

    /**
     * Filter the pageInfo that is returned to the connection.
     *
     * This allows us to interject with our own logic when using offsetPagination.
     *
     * @param array                      $page_info
     * @param AbstractConnectionResolver $resolver
     *
     * @return array
     * @since 0.0.1
     */
    function get_page_info( $page_info, AbstractConnectionResolver $resolver ) {
        // Get the resolver args.
        $args = $resolver->getArgs();

        $page_info['previousPage'] = null;
        $page_info['nextPage']     = null;
        $page_info['totalPages']   = null;

        // If we're querying with an offset pagination query then use this logic.
        if ( isset( $args['where'] ) && isset( $args['where']['offsetPagination'] ) ) {
            $query_args = $resolver->get_query_args();
            $items      = $resolver->get_items();

            // If we're requesting any offset, then there must be a prev page.
            $page_info['hasPreviousPage'] = $query_args['offset'] > 0 ? true : false;

            // If we've retrieved more items that we requested (because of the +1) there must be a next page.
            $page_info['hasNextPage'] = count( $items ) > $query_args['posts_per_page'] ? true : false;

            // Get the current page that we're on. (Need to +1 otherwise page 1 would show as 0)
            $current_page = $query_args['offset'] / $query_args['posts_per_page'] + 1;

            // If we have a next page, add it to pageInfo.
            $page_info['nextPage']     = $page_info['hasNextPage'] ? $current_page + 1 : null;

            // If we have a prev page, add it to pageInfo.
            $page_info['previousPage'] = $page_info['hasPreviousPage'] ? $current_page - 1 : null;

            /**
             * Calculate the total number of pages available on this query.
             *
             * The calculation is ceil( number_of_posts / items_per_page ).
             */
            if ( $resolver->get_query() instanceof \WP_Query ) {
                if ( isset( $resolver->get_query()->found_posts ) ) {
                    $page_info['totalPages'] = ceil( intval( $resolver->get_query()->found_posts ) / $query_args['posts_per_page'] );
                }
            } elseif ( $resolver->get_query() instanceof \WP_User_Query ) {
                if ( isset( $resolver->get_query()->total_users ) ) {
                    $page_info['totalPages'] = ceil( intval( $resolver->get_query()->total_users ) / $query_args['posts_per_page'] );
                }
            }

            // Kill off the start and end cursor as they make no sense now we're not using cursors.
            $page_info['startCursor'] = $page_info['endCursor'] = null;
        }

        return $page_info;
    }
}

/**
 * Instantiate the OffsetPagination class on graphql_init
 * @return OffsetPagination
 */
function graphql_init_offset_pagination() {
    return new \Enshrined\OffsetPagination();
}

add_action( 'graphql_init', '\Enshrined\graphql_init_offset_pagination' );
