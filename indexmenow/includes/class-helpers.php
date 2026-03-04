<?php
/**
 * Helper functions and cached getters for improved performance.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Helpers {

    /**
     * Cached allowed post types.
     *
     * @var array|null
     */
    private static $post_types_cache = null;

    /**
     * Cached allowed categories.
     *
     * @var array|null
     */
    private static $categories_cache = null;

    /**
     * Get allowed post types with caching.
     *
     * @return array Allowed post types.
     */
    public static function get_allowed_post_types(): array {
        if ( null === self::$post_types_cache ) {
            self::$post_types_cache = get_option( 'imn_w2_post_types', array( 'post', 'page' ) );
            if ( ! is_array( self::$post_types_cache ) ) {
                self::$post_types_cache = array( 'post', 'page' );
            }
        }
        return self::$post_types_cache;
    }

    /**
     * Get allowed categories with caching.
     *
     * @return array Allowed categories.
     */
    public static function get_allowed_categories(): array {
        if ( null === self::$categories_cache ) {
            self::$categories_cache = get_option( 'imn_w2_categories', array() );
            if ( ! is_array( self::$categories_cache ) ) {
                self::$categories_cache = array();
            }
        }
        return self::$categories_cache;
    }

    /**
     * Clear all caches.
     * Should be called when settings are updated.
     */
    public static function clear_cache(): void {
        self::$post_types_cache = null;
        self::$categories_cache = null;
    }

    /**
     * Check if post passes category filter.
     *
     * @param WP_Post $post Post object.
     * @return bool True if passes filter or no filter set.
     */
    public static function passes_category_filter( WP_Post $post ): bool {
        $allowed_categories = self::get_allowed_categories();

        // No filter set = allow all.
        if ( empty( $allowed_categories ) ) {
            return true;
        }

        // Only filter posts that support categories.
        if ( ! is_object_in_taxonomy( $post->post_type, 'category' ) ) {
            return true;
        }

        $post_categories = wp_get_post_categories( $post->ID );
        if ( empty( $post_categories ) ) {
            return false;
        }

        // Check if any post category is in allowed categories.
        $intersection = array_intersect( $post_categories, array_map( 'intval', $allowed_categories ) );
        return ! empty( $intersection );
    }
}
