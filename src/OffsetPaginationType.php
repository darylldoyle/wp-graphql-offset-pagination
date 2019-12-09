<?php

namespace Enshrined\OffsetPagination;

use WPGraphQL\Type\WPInputObjectType;
use WPGraphQL\Types;

class OffsetPaginationType extends WPInputObjectType {
    protected static $fields;

    /**
     * OffsetPaginationType constructor.
     */
    public function __construct( $type_name ) {
        $config = [
            'name'        => $type_name . 'OffsetPagination',
            'description' => __( 'Use standard offset pagination rather than cursor based pagination',
                'wp-graphql-offset-pagination' ),
            'fields'      => self::fields( $type_name ),
        ];
        parent::__construct( $config );
    }

    /**
     * @return array|null
     */
    protected static function fields( $type_name ) {
        if ( empty( self::$fields[ $type_name ] ) ) :
            self::$fields[ $type_name ] = array(
                'page'     => array(
                    'type' => Types::int(),
                ),
                'per_page' => array(
                    'type' => Types::int(),
                )
            );
        endif;

        return ! empty( self::$fields[ $type_name ] ) ? self::$fields[ $type_name ] : null;
    }
}
