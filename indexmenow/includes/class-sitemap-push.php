<?php
/**
 * Sitemap URL push functionality.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Sitemap_Push {

    public function __construct() {
        add_action( 'wp_ajax_imn_w2_push_sitemap', array( $this, 'handle_ajax' ) );
        add_action( 'wp_ajax_imn_w2_get_sitemap_urls', array( $this, 'get_sitemap_urls' ) );
    }

    /**
     * Get URLs from sitemap.
     *
     * @param string $sitemap_url Sitemap URL.
     * @param int    $depth       Current recursion depth (internal use).
     * @return array Array of URLs or empty array on failure.
     */
    public static function parse_sitemap( string $sitemap_url, int $depth = 0 ): array {
        // Prevent infinite recursion and DoS attacks.
        if ( $depth > IMN_W2_SITEMAP_MAX_DEPTH ) {
            return array();
        }

        $urls = array();

        /**
         * Filter to disable SSL verification for sitemap fetching.
         * Should only be used for testing/debugging purposes.
         *
         * @param bool $sslverify Whether to verify SSL certificates. Default true.
         */
        $sslverify = apply_filters( 'imn_w2_sitemap_sslverify', true );

        $response = wp_remote_get( $sitemap_url, array(
            'timeout'   => 30,
            'sslverify' => $sslverify,
        ) );

        if ( is_wp_error( $response ) ) {
            return $urls;
        }

        $body = wp_remote_retrieve_body( $response );
        if ( empty( $body ) ) {
            return $urls;
        }

        // Suppress XML errors.
        libxml_use_internal_errors( true );
        $xml = simplexml_load_string( $body );
        libxml_clear_errors();

        if ( false === $xml ) {
            return $urls;
        }

        // Check if it's a sitemap index.
        if ( isset( $xml->sitemap ) ) {
            foreach ( $xml->sitemap as $sitemap ) {
                if ( isset( $sitemap->loc ) ) {
                    $child_urls = self::parse_sitemap( (string) $sitemap->loc, $depth + 1 );
                    $urls       = array_merge( $urls, $child_urls );

                    // Global limit to prevent memory exhaustion.
                    if ( count( $urls ) >= IMN_W2_SITEMAP_MAX_URLS * 2 ) {
                        break;
                    }
                }
            }
        }

        // Check if it's a regular sitemap.
        if ( isset( $xml->url ) ) {
            foreach ( $xml->url as $url ) {
                if ( isset( $url->loc ) ) {
                    $urls[] = (string) $url->loc;
                }
            }
        }

        return $urls;
    }

    /**
     * Get sitemap URLs via AJAX.
     */
    public function get_sitemap_urls(): void {
        check_ajax_referer( 'imn_w2_sitemap_push', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        $sitemap_url = home_url( '/sitemap.xml' );

        // Check for common sitemap plugins.
        if ( class_exists( 'WPSEO_Sitemaps' ) ) {
            $sitemap_url = home_url( '/sitemap_index.xml' );
        } elseif ( defined( 'JETPACK__VERSION' ) ) {
            $sitemap_url = home_url( '/sitemap.xml' );
        } elseif ( function_exists( 'aioseo' ) ) {
            $sitemap_url = home_url( '/sitemap.xml' );
        }

        // WordPress core sitemap (5.5+).
        if ( function_exists( 'wp_sitemaps_get_server' ) ) {
            $sitemap_url = home_url( '/wp-sitemap.xml' );
        }

        /**
         * Filter the sitemap URL before parsing.
         * Allows users to specify a custom sitemap location.
         *
         * @param string $sitemap_url Detected sitemap URL.
         */
        $sitemap_url = apply_filters( 'imn_w2_sitemap_url', $sitemap_url );

        $urls = self::parse_sitemap( $sitemap_url );

        if ( empty( $urls ) ) {
            wp_send_json_error( array(
                'message' => __( 'No URLs found in sitemap. Make sure your sitemap is accessible.', 'indexmenow' ),
            ) );
        }

        // Limit to prevent overload.
        $urls = array_slice( $urls, 0, IMN_W2_SITEMAP_MAX_URLS );

        wp_send_json_success( array(
            'urls'        => $urls,
            'total'       => count( $urls ),
            'sitemap_url' => $sitemap_url,
        ) );
    }

    /**
     * Handle AJAX sitemap push.
     */
    public function handle_ajax(): void {
        check_ajax_referer( 'imn_w2_sitemap_push', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        // Sanitize URLs array safely.
        $urls = array();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Each URL is unslashed and sanitized in the loop below.
        if ( isset( $_POST['urls'] ) && is_array( $_POST['urls'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Each URL is unslashed with wp_unslash() and sanitized with esc_url_raw() below.
            foreach ( $_POST['urls'] as $url ) {
                if ( is_string( $url ) ) {
                    $sanitized = esc_url_raw( wp_unslash( $url ) );
                    if ( ! empty( $sanitized ) ) {
                        $urls[] = $sanitized;
                    }
                }
            }
        }

        if ( empty( $urls ) ) {
            wp_send_json_error( array( 'message' => __( 'No URLs to push.', 'indexmenow' ) ) );
        }

        // Limit batch size.
        $urls = array_slice( $urls, 0, IMN_W2_SITEMAP_MAX_URLS );

        $pusher = new IMN_W2_Url_Pusher();
        $result = $pusher->push_urls( $urls, 'sitemap' );

        wp_send_json_success( array(
            'pushed'  => $result['pushed'],
            'skipped' => $result['skipped'],
            'errors'  => $result['errors'],
            'message' => $result['message'],
        ) );
    }
}
