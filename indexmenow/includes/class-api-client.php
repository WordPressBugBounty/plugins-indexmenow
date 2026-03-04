<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Api_Client {

    private string $api_key;

    public function __construct( string $api_key ) {
        $this->api_key = $api_key;
    }

    /**
     * Normalize a URL for comparison (lowercase host, remove trailing slash).
     *
     * @param string $url URL to normalize.
     * @return string Normalized URL.
     */
    public static function normalize_url( string $url ): string {
        $parsed = wp_parse_url( $url );
        if ( ! $parsed || ! isset( $parsed['host'] ) ) {
            return rtrim( strtolower( $url ), '/' );
        }

        $normalized = isset( $parsed['scheme'] ) ? strtolower( $parsed['scheme'] ) . '://' : 'https://';
        $normalized .= strtolower( $parsed['host'] );

        if ( isset( $parsed['port'] ) ) {
            $normalized .= ':' . $parsed['port'];
        }

        $path = isset( $parsed['path'] ) ? $parsed['path'] : '/';
        $normalized .= rtrim( $path, '/' );

        if ( isset( $parsed['query'] ) ) {
            $normalized .= '?' . $parsed['query'];
        }

        return $normalized;
    }

    public function validate_token(): bool {
        $credits = $this->get_credits();
        return false !== $credits;
    }

    /**
     * @return int|false
     */
    public function get_credits() {
        $response = $this->request( 'GET', '/user/credits' );
        if ( false === $response || ! isset( $response['credits'] ) ) {
            return false;
        }
        return (int) $response['credits'];
    }

    /**
     * @return array|false  List of projects or false on error.
     */
    public function list_projects() {
        $response = $this->request( 'GET', '/project/list' );
        if ( false === $response || ! isset( $response['projects'] ) ) {
            return false;
        }
        return $response['projects'];
    }

    /**
     * @return int|false  Project ID or false if not found.
     */
    public function find_project( string $name ) {
        $response = $this->request( 'POST', '/project/exists', array( 'project_name' => $name ) );
        if ( false === $response || ( isset( $response['status'] ) && 'KO' === $response['status'] ) ) {
            return false;
        }
        return isset( $response['project_id'] ) ? (int) $response['project_id'] : false;
    }

    /**
     * @return array|false  Response array on success, false on failure.
     */
    public function create_project( string $name, array $urls ) {
        $response = $this->request( 'POST', '/project/add', array(
            'project_name' => $name,
            'urls'         => $urls,
        ) );
        if ( false === $response || ( isset( $response['status'] ) && 'KO' === $response['status'] ) ) {
            return false;
        }
        return $response;
    }

    /**
     * @return array|false  Response array on success, false on failure.
     */
    public function add_urls( int $project_id, array $urls ) {
        $response = $this->request( 'POST', '/project/' . $project_id . '/addurls', array(
            'urls' => $urls,
        ) );
        if ( false === $response || ( isset( $response['status'] ) && 'KO' === $response['status'] ) ) {
            return false;
        }
        return $response;
    }

    /**
     * Check if a URL exists in a project and whether it is completed.
     *
     * @param int    $project_id Project ID.
     * @param string $url        URL to check.
     * @return array{found: bool, completed: bool}|false  Status or false on error.
     */
    public function get_url_status( int $project_id, string $url ) {
        $response = $this->request( 'POST', '/project/' . $project_id, array(
            'urls' => array( $url ),
        ) );
        if ( false === $response || ! isset( $response['urls'] ) || ! is_array( $response['urls'] ) ) {
            return false;
        }

        $normalized_url = self::normalize_url( $url );

        foreach ( $response['urls'] as $entry ) {
            if ( isset( $entry['url'] ) && self::normalize_url( $entry['url'] ) === $normalized_url ) {
                return array(
                    'found'     => true,
                    'completed' => ! empty( $entry['completed'] ),
                );
            }
        }

        return array(
            'found'     => false,
            'completed' => false,
        );
    }

    /**
     * @return array|false  Decoded JSON response or false on error.
     */
    private function request( string $method, string $endpoint, ?array $body = null ) {
        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ),
        );

        if ( null !== $body ) {
            $args['body'] = wp_json_encode( $body );
        }

        $response = wp_remote_request( IMN_W2_API_BASE . $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return false;
        }

        $decoded = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $decoded ) ) {
            return false;
        }

        return $decoded;
    }
}
