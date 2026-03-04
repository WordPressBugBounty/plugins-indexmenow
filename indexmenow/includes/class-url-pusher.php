<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Url_Pusher {

    /**
     * Push a URL to IndexMeNow.
     *
     * @param string $url     The URL to push.
     * @param array  $context {
     *     Optional push context.
     *     @type int    $post_id            Post ID for history logging.
     *     @type string $trigger            'manual', 'auto_publish', or 'auto_update'.
     *     @type bool   $skip_status_check  Skip the URL status check (e.g. first publish).
     * }
     * @return array{success: bool, message: string}
     */
    public function push_url( string $url, array $context = array() ): array {
        $post_id           = isset( $context['post_id'] ) ? (int) $context['post_id'] : 0;
        $trigger           = isset( $context['trigger'] ) ? $context['trigger'] : 'manual';
        $skip_status_check = ! empty( $context['skip_status_check'] );

        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            $this->log_history( $post_id, $url, 0, 'error', __( 'API key not configured.', 'indexmenow' ), $trigger );
            return array(
                'success' => false,
                'message' => __( 'API key not configured.', 'indexmenow' ),
            );
        }

        $client = new IMN_W2_Api_Client( $api_key );

        $credits = $client->get_credits();
        if ( false === $credits ) {
            $this->log_history( $post_id, $url, 0, 'error', __( 'Could not retrieve credits. Check your API key.', 'indexmenow' ), $trigger );
            return array(
                'success' => false,
                'message' => __( 'Could not retrieve credits. Check your API key.', 'indexmenow' ),
            );
        }
        if ( $credits < 1 ) {
            $this->log_history( $post_id, $url, 0, 'error', __( 'Insufficient credits.', 'indexmenow' ), $trigger );
            return array(
                'success' => false,
                'message' => __( 'Insufficient credits.', 'indexmenow' ),
            );
        }

        update_option( 'imn_w2_credits', $credits, false );

        $project_id   = (int) get_option( 'imn_w2_project_id', 0 );
        $project_mode = get_option( 'imn_w2_project_mode', 'auto' );

        // If user chose "existing project" mode.
        if ( 'existing' === $project_mode ) {
            if ( $project_id < 1 ) {
                $this->log_history( $post_id, $url, 0, 'error', __( 'No project selected. Please select a project in Settings > IndexMeNow.', 'indexmenow' ), $trigger );
                return array(
                    'success' => false,
                    'message' => __( 'No project selected. Please select a project in Settings > IndexMeNow.', 'indexmenow' ),
                );
            }

            // Check URL status before pushing.
            if ( ! $skip_status_check ) {
                $skip = $this->should_skip_url( $client, $project_id, $url );
                if ( $skip ) {
                    $this->log_history( $post_id, $url, $project_id, 'skipped', $skip, $trigger );
                    return array(
                        'success' => false,
                        'message' => $skip,
                    );
                }
            }

            $result = $client->add_urls( $project_id, array( $url ) );
            if ( false !== $result ) {
                $this->update_credits_from_response( $result );
                $this->log_history( $post_id, $url, $project_id, 'success', __( 'URL pushed successfully.', 'indexmenow' ), $trigger );
                return array(
                    'success' => true,
                    'message' => __( 'URL pushed successfully.', 'indexmenow' ),
                );
            }
            $this->log_history( $post_id, $url, $project_id, 'error', __( 'Failed to add URL to project. The project may have been deleted.', 'indexmenow' ), $trigger );
            return array(
                'success' => false,
                'message' => __( 'Failed to add URL to project. The project may have been deleted.', 'indexmenow' ),
            );
        }

        // Auto mode: find or create project by domain name.
        $domain       = wp_parse_url( home_url(), PHP_URL_HOST );
        $project_name = $domain . ' (wordpress plugin)';

        // Try cached project ID first.
        if ( $project_id > 0 ) {
            // Check URL status before pushing.
            if ( ! $skip_status_check ) {
                $skip = $this->should_skip_url( $client, $project_id, $url );
                if ( $skip ) {
                    $this->log_history( $post_id, $url, $project_id, 'skipped', $skip, $trigger );
                    return array(
                        'success' => false,
                        'message' => $skip,
                    );
                }
            }

            $result = $client->add_urls( $project_id, array( $url ) );
            if ( false !== $result ) {
                $this->update_credits_from_response( $result );
                $this->log_history( $post_id, $url, $project_id, 'success', __( 'URL pushed successfully.', 'indexmenow' ), $trigger );
                return array(
                    'success' => true,
                    'message' => __( 'URL pushed successfully.', 'indexmenow' ),
                );
            }
            // Cached project ID may be stale, try to find/create below.
        }

        // Try to find existing project.
        $found_id = $client->find_project( $project_name );
        if ( false !== $found_id ) {
            update_option( 'imn_w2_project_id', $found_id, false );

            // Check URL status before pushing.
            if ( ! $skip_status_check ) {
                $skip = $this->should_skip_url( $client, $found_id, $url );
                if ( $skip ) {
                    $this->log_history( $post_id, $url, $found_id, 'skipped', $skip, $trigger );
                    return array(
                        'success' => false,
                        'message' => $skip,
                    );
                }
            }

            $result = $client->add_urls( $found_id, array( $url ) );
            if ( false !== $result ) {
                $this->update_credits_from_response( $result );
                $this->log_history( $post_id, $url, $found_id, 'success', __( 'URL pushed successfully.', 'indexmenow' ), $trigger );
                return array(
                    'success' => true,
                    'message' => __( 'URL pushed successfully.', 'indexmenow' ),
                );
            }
            $this->log_history( $post_id, $url, $found_id, 'error', __( 'Failed to add URL to project.', 'indexmenow' ), $trigger );
            return array(
                'success' => false,
                'message' => __( 'Failed to add URL to project.', 'indexmenow' ),
            );
        }

        // Create new project with the URL (no status check needed for new project).
        $result = $client->create_project( $project_name, array( $url ) );
        if ( false === $result ) {
            $this->log_history( $post_id, $url, 0, 'error', __( 'Failed to create project.', 'indexmenow' ), $trigger );
            return array(
                'success' => false,
                'message' => __( 'Failed to create project.', 'indexmenow' ),
            );
        }

        $new_project_id = 0;
        if ( isset( $result['project_id'] ) ) {
            $new_project_id = (int) $result['project_id'];
            update_option( 'imn_w2_project_id', $new_project_id, false );
        }
        $this->update_credits_from_response( $result );
        $this->log_history( $post_id, $url, $new_project_id, 'success', __( 'URL pushed successfully (new project created).', 'indexmenow' ), $trigger );

        return array(
            'success' => true,
            'message' => __( 'URL pushed successfully (new project created).', 'indexmenow' ),
        );
    }

    /**
     * Check if a URL should be skipped because it is already being indexed.
     *
     * @param IMN_W2_Api_Client $client     API client.
     * @param int               $project_id Project ID.
     * @param string            $url        URL to check.
     * @return string|false Skip reason message, or false if push is allowed.
     */
    private function should_skip_url( IMN_W2_Api_Client $client, int $project_id, string $url ) {
        if ( $project_id < 1 ) {
            return false;
        }

        // Check transient cache first (use normalized URL for consistent cache keys).
        $normalized_url = IMN_W2_Api_Client::normalize_url( $url );
        $cache_key      = 'imn_w2_url_status_' . md5( $project_id . '|' . $normalized_url );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            if ( ! empty( $cached['found'] ) && empty( $cached['completed'] ) ) {
                return __( 'URL is already being indexed. Please wait for completion before re-pushing.', 'indexmenow' );
            }
            return false;
        }

        $status = $client->get_url_status( $project_id, $url );

        // Fail-open: if API check fails, allow the push.
        if ( false === $status ) {
            return false;
        }

        // Cache result.
        set_transient( $cache_key, $status, IMN_W2_CACHE_DURATION );

        // URL found and not completed → block.
        if ( $status['found'] && ! $status['completed'] ) {
            return __( 'URL is already being indexed. Please wait for completion before re-pushing.', 'indexmenow' );
        }

        return false;
    }

    /**
     * Log a push history entry.
     */
    private function log_history( int $post_id, string $url, int $project_id, string $status, string $message, string $trigger ): void {
        IMN_W2_Push_History::log( array(
            'post_id'      => $post_id,
            'url'          => $url,
            'project_id'   => $project_id,
            'status'       => $status,
            'message'      => $message,
            'push_trigger' => $trigger,
        ) );
    }

    /**
     * Update cached credits from API response.
     *
     * @param array $response API response.
     */
    private function update_credits_from_response( array $response ): void {
        if ( isset( $response['credits_debt'] ) ) {
            $current = (int) get_option( 'imn_w2_credits', 0 );
            update_option( 'imn_w2_credits', max( 0, $current - (int) $response['credits_debt'] ), false );
        }
    }

    /**
     * Push multiple URLs to IndexMeNow in a single API call.
     *
     * @param array  $urls    Array of URLs to push.
     * @param string $trigger Push trigger ('bulk', 'sitemap', etc.).
     * @return array{pushed: int, skipped: int, errors: int, message: string}
     */
    public function push_urls( array $urls, string $trigger = 'bulk' ): array {
        $result = array(
            'pushed'  => 0,
            'skipped' => 0,
            'errors'  => 0,
            'message' => '',
        );

        if ( empty( $urls ) ) {
            $result['message'] = __( 'No URLs to push.', 'indexmenow' );
            return $result;
        }

        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            $result['errors']  = count( $urls );
            $result['message'] = __( 'API key not configured.', 'indexmenow' );
            return $result;
        }

        $client = new IMN_W2_Api_Client( $api_key );

        $credits = $client->get_credits();
        if ( false === $credits ) {
            $result['errors']  = count( $urls );
            $result['message'] = __( 'Could not retrieve credits. Check your API key.', 'indexmenow' );
            return $result;
        }
        if ( $credits < 1 ) {
            $result['errors']  = count( $urls );
            $result['message'] = __( 'Insufficient credits.', 'indexmenow' );
            return $result;
        }

        update_option( 'imn_w2_credits', $credits, false );

        $project_id   = (int) get_option( 'imn_w2_project_id', 0 );
        $project_mode = get_option( 'imn_w2_project_mode', 'auto' );

        // Resolve project ID.
        if ( 'existing' === $project_mode ) {
            if ( $project_id < 1 ) {
                $result['errors']  = count( $urls );
                $result['message'] = __( 'No project selected. Please select a project in Settings > IndexMeNow.', 'indexmenow' );
                return $result;
            }
        } else {
            // Auto mode: find or create project.
            if ( $project_id < 1 ) {
                $domain       = wp_parse_url( home_url(), PHP_URL_HOST );
                $project_name = $domain . ' (wp-w2)';

                $found_id = $client->find_project( $project_name );
                if ( false !== $found_id ) {
                    $project_id = $found_id;
                    update_option( 'imn_w2_project_id', $project_id, false );
                } else {
                    // Create project with first URL, then add remaining.
                    $first_url    = array_shift( $urls );
                    $first_post_id = url_to_postid( $first_url );
                    $create_result = $client->create_project( $project_name, array( $first_url ) );

                    if ( false === $create_result ) {
                        $this->log_history( $first_post_id > 0 ? $first_post_id : 0, $first_url, 0, 'error', __( 'Failed to create project.', 'indexmenow' ), $trigger );
                        $result['errors'] = count( $urls ) + 1;
                        $result['message'] = __( 'Failed to create project.', 'indexmenow' );
                        return $result;
                    }

                    $project_id = isset( $create_result['project_id'] ) ? (int) $create_result['project_id'] : 0;
                    update_option( 'imn_w2_project_id', $project_id, false );
                    $this->update_credits_from_response( $create_result );
                    $this->log_history( $first_post_id > 0 ? $first_post_id : 0, $first_url, $project_id, 'success', __( 'URL pushed successfully (new project created).', 'indexmenow' ), $trigger );
                    $result['pushed']++;

                    if ( empty( $urls ) ) {
                        $result['message'] = sprintf(
                            /* translators: 1: number of URLs pushed, 2: number skipped, 3: number of errors */
                            __( '%1$d pushed, %2$d skipped, %3$d errors.', 'indexmenow' ),
                            $result['pushed'],
                            $result['skipped'],
                            $result['errors']
                        );
                        return $result;
                    }
                }
            }
        }

        // Push all URLs in a single API call.
        $api_result = $client->add_urls( $project_id, $urls );

        if ( false === $api_result ) {
            // Log each URL as error.
            foreach ( $urls as $url ) {
                $post_id = url_to_postid( $url );
                $this->log_history( $post_id > 0 ? $post_id : 0, $url, $project_id, 'error', __( 'Failed to add URL to project.', 'indexmenow' ), $trigger );
                $result['errors']++;
            }
            $result['message'] = __( 'Failed to add URL to project.', 'indexmenow' );
            return $result;
        }

        $this->update_credits_from_response( $api_result );

        // Log each URL as success.
        foreach ( $urls as $url ) {
            $post_id = url_to_postid( $url );
            $this->log_history( $post_id > 0 ? $post_id : 0, $url, $project_id, 'success', __( 'URL pushed successfully.', 'indexmenow' ), $trigger );
            $result['pushed']++;

            if ( $post_id > 0 ) {
                update_post_meta( $post_id, '_imn_w2_pushed', current_time( 'mysql' ) );
            }
        }

        $result['message'] = sprintf(
            /* translators: 1: number of URLs pushed, 2: number skipped, 3: number of errors */
            __( '%1$d pushed, %2$d skipped, %3$d errors.', 'indexmenow' ),
            $result['pushed'],
            $result['skipped'],
            $result['errors']
        );

        return $result;
    }
}
