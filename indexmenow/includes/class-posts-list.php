<?php
/**
 * Posts list integration - row actions and bulk actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Posts_List {

    public function __construct() {
        add_action( 'admin_init', array( $this, 'register_hooks' ) );
        add_action( 'wp_ajax_imn_w2_bulk_push', array( $this, 'handle_bulk_ajax' ) );
    }

    /**
     * Register hooks for each allowed post type.
     */
    public function register_hooks(): void {
        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        $post_types = IMN_W2_Helpers::get_allowed_post_types();

        foreach ( $post_types as $post_type ) {
            // Row actions: use correct hook for each post type.
            if ( 'page' === $post_type ) {
                add_filter( 'page_row_actions', array( $this, 'add_row_action' ), 10, 2 );
            } else {
                add_filter( "{$post_type}_row_actions", array( $this, 'add_row_action' ), 10, 2 );
            }

            // Bulk actions.
            add_filter( "bulk_actions-edit-{$post_type}", array( $this, 'add_bulk_action' ) );
            add_filter( "handle_bulk_actions-edit-{$post_type}", array( $this, 'handle_bulk_action' ), 10, 3 );
        }

        // Admin notices for bulk action results.
        add_action( 'admin_notices', array( $this, 'bulk_action_admin_notice' ) );

        // Enqueue scripts on edit screens.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    /**
     * Add row action to push single post.
     *
     * @param array   $actions Existing actions.
     * @param WP_Post $post    Post object.
     * @return array Modified actions.
     */
    public function add_row_action( array $actions, WP_Post $post ): array {
        $allowed_types = IMN_W2_Helpers::get_allowed_post_types();
        if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
            return $actions;
        }

        if ( 'publish' !== $post->post_status ) {
            return $actions;
        }

        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $actions;
        }

        $nonce = wp_create_nonce( 'imn_w2_row_push_' . $post->ID );
        $actions['imn_w2_push'] = sprintf(
            '<a href="#" class="imn-w2-row-push" data-post-id="%d" data-nonce="%s">%s</a>',
            $post->ID,
            esc_attr( $nonce ),
            esc_html__( 'Push to IndexMeNow', 'indexmenow' )
        );

        return $actions;
    }

    /**
     * Add bulk action option.
     *
     * @param array $actions Existing bulk actions.
     * @return array Modified bulk actions.
     */
    public function add_bulk_action( array $actions ): array {
        $actions['imn_w2_bulk_push'] = __( 'Push to IndexMeNow', 'indexmenow' );
        return $actions;
    }

    /**
     * Handle bulk action.
     *
     * @param string $redirect_to Redirect URL.
     * @param string $doaction    Action name.
     * @param array  $post_ids    Selected post IDs.
     * @return string Modified redirect URL.
     */
    public function handle_bulk_action( string $redirect_to, string $doaction, array $post_ids ): string {
        if ( 'imn_w2_bulk_push' !== $doaction ) {
            return $redirect_to;
        }

        $urls_to_push = array();
        $skipped      = 0;

        // Collect valid URLs.
        foreach ( $post_ids as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post || 'publish' !== $post->post_status ) {
                $skipped++;
                continue;
            }

            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                $skipped++;
                continue;
            }

            $permalink = get_permalink( $post_id );
            if ( ! $permalink ) {
                $skipped++;
                continue;
            }

            $urls_to_push[] = $permalink;
        }

        $pushed = 0;
        $errors = 0;

        if ( ! empty( $urls_to_push ) ) {
            $pusher = new IMN_W2_Url_Pusher();
            $result = $pusher->push_urls( $urls_to_push, 'bulk' );

            $pushed = $result['pushed'];
            $errors = $result['errors'];
            $skipped += $result['skipped'];
        }

        $redirect_to = add_query_arg( array(
            'imn_w2_bulk_pushed'  => $pushed,
            'imn_w2_bulk_skipped' => $skipped,
            'imn_w2_bulk_errors'  => $errors,
        ), $redirect_to );

        return $redirect_to;
    }

    /**
     * Display admin notice after bulk action.
     */
    public function bulk_action_admin_notice(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by WordPress bulk action handler.
        if ( ! isset( $_GET['imn_w2_bulk_pushed'] ) ) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by WordPress bulk action handler.
        $pushed  = (int) $_GET['imn_w2_bulk_pushed'];
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by WordPress bulk action handler.
        $skipped = isset( $_GET['imn_w2_bulk_skipped'] ) ? (int) $_GET['imn_w2_bulk_skipped'] : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified by WordPress bulk action handler.
        $errors  = isset( $_GET['imn_w2_bulk_errors'] ) ? (int) $_GET['imn_w2_bulk_errors'] : 0;

        $messages = array();

        if ( $pushed > 0 ) {
            $messages[] = sprintf(
                /* translators: %d: number of URLs pushed */
                _n( '%d URL pushed to IndexMeNow.', '%d URLs pushed to IndexMeNow.', $pushed, 'indexmenow' ),
                $pushed
            );
        }

        if ( $skipped > 0 ) {
            $messages[] = sprintf(
                /* translators: %d: number of URLs skipped */
                _n( '%d URL skipped.', '%d URLs skipped.', $skipped, 'indexmenow' ),
                $skipped
            );
        }

        if ( $errors > 0 ) {
            $messages[] = sprintf(
                /* translators: %d: number of errors */
                _n( '%d error.', '%d errors.', $errors, 'indexmenow' ),
                $errors
            );
        }

        if ( ! empty( $messages ) ) {
            $class = $errors > 0 ? 'notice-warning' : 'notice-success';
            printf(
                '<div class="notice %s is-dismissible"><p><strong>IndexMeNow:</strong> %s</p></div>',
                esc_attr( $class ),
                esc_html( implode( ' ', $messages ) )
            );
        }
    }

    /**
     * Enqueue assets for posts list.
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_assets( string $hook ): void {
        if ( 'edit.php' !== $hook ) {
            return;
        }

        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        wp_enqueue_style( 'imn-w2-admin', IMN_W2_PLUGIN_URL . 'assets/css/admin.css', array(), IMN_W2_VERSION );
        wp_enqueue_script( 'imn-w2-posts-list', IMN_W2_PLUGIN_URL . 'assets/js/posts-list.js', array(), IMN_W2_VERSION, true );
        wp_localize_script( 'imn-w2-posts-list', 'imn_w2_posts', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'imn_w2_bulk_push' ),
            'i18n'     => array(
                'pushing'       => __( 'Pushing...', 'indexmenow' ),
                'pushed'        => __( 'Pushed!', 'indexmenow' ),
                'error'         => __( 'Error', 'indexmenow' ),
                'error_generic' => __( 'An error occurred.', 'indexmenow' ),
            ),
        ) );
    }

    /**
     * Handle AJAX push from row action.
     */
    public function handle_bulk_ajax(): void {
        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

        if ( $post_id < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'indexmenow' ) ) );
        }

        // Verify nonce for specific post.
        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'imn_w2_row_push_' . $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        $permalink = get_permalink( $post_id );
        if ( ! $permalink ) {
            wp_send_json_error( array( 'message' => __( 'Could not get permalink.', 'indexmenow' ) ) );
        }

        $pusher = new IMN_W2_Url_Pusher();
        $result = $pusher->push_url( $permalink, array(
            'post_id' => $post_id,
            'trigger' => 'manual',
        ) );

        if ( $result['success'] ) {
            update_post_meta( $post_id, '_imn_w2_pushed', current_time( 'mysql' ) );
            wp_send_json_success( array( 'message' => $result['message'] ) );
        } else {
            wp_send_json_error( array( 'message' => $result['message'] ) );
        }
    }
}
