<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Manual_Push {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'wp_ajax_imn_w2_manual_push', array( $this, 'handle_ajax' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_button' ), 100 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_bar_assets' ) );
    }

    public function add_meta_box(): void {
        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        $post_types = get_option( 'imn_w2_post_types', array( 'post', 'page' ) );
        if ( ! is_array( $post_types ) ) {
            return;
        }
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'imn_w2_manual_push',
                'IndexMeNow',
                array( $this, 'render_meta_box' ),
                $post_type,
                'side',
                'default'
            );
        }
    }

    public function render_meta_box( WP_Post $post ): void {
        $permalink = get_permalink( $post->ID );
        $pushed_at = get_post_meta( $post->ID, '_imn_w2_pushed', true );
        $nonce     = wp_create_nonce( 'imn_w2_manual_push' );
        $history   = IMN_W2_Push_History::get_by_post( $post->ID, 5 );

        include IMN_W2_PLUGIN_DIR . 'views/manual-push-metabox.php';
    }

    public function enqueue_assets( string $hook ): void {
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        wp_enqueue_style( 'imn-w2-admin', IMN_W2_PLUGIN_URL . 'assets/css/admin.css', array(), IMN_W2_VERSION );
        wp_enqueue_script( 'imn-w2-manual-push', IMN_W2_PLUGIN_URL . 'assets/js/manual-push.js', array(), IMN_W2_VERSION, true );
        wp_localize_script( 'imn-w2-manual-push', 'imn_w2', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'i18n'     => array(
                'error_generic' => __( 'An error occurred.', 'indexmenow' ),
                'error_network' => __( 'Network error. Please try again.', 'indexmenow' ),
            ),
        ) );
    }

    /**
     * Add IndexMeNow button to the admin bar.
     *
     * @param WP_Admin_Bar $admin_bar Admin bar instance.
     */
    public function add_admin_bar_button( WP_Admin_Bar $admin_bar ): void {
        if ( ! is_singular() || ! is_user_logged_in() ) {
            return;
        }

        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        $post = get_queried_object();
        if ( ! $post instanceof WP_Post ) {
            return;
        }

        // Check if user can edit this specific post.
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return;
        }

        $allowed_types = get_option( 'imn_w2_post_types', array( 'post', 'page' ) );
        if ( ! is_array( $allowed_types ) || ! in_array( $post->post_type, $allowed_types, true ) ) {
            return;
        }

        if ( 'publish' !== $post->post_status ) {
            return;
        }

        $admin_bar->add_node( array(
            'id'    => 'imn-w2-push',
            'title' => '<span class="ab-icon dashicons dashicons-upload"></span><span class="ab-label">' . esc_html__( 'IndexMeNow', 'indexmenow' ) . '</span>',
            'href'  => '#',
            'meta'  => array(
                'class' => 'imn-w2-admin-bar-push',
                'title' => __( 'Push to IndexMeNow', 'indexmenow' ),
            ),
        ) );
    }

    /**
     * Enqueue assets on the frontend for admin bar.
     */
    public function enqueue_frontend_assets(): void {
        if ( ! is_admin_bar_showing() || ! is_singular() ) {
            return;
        }

        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        $post = get_queried_object();
        if ( ! $post instanceof WP_Post ) {
            return;
        }

        // Check if user can edit this specific post.
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return;
        }

        $allowed_types = get_option( 'imn_w2_post_types', array( 'post', 'page' ) );
        if ( ! is_array( $allowed_types ) || ! in_array( $post->post_type, $allowed_types, true ) ) {
            return;
        }

        if ( 'publish' !== $post->post_status ) {
            return;
        }

        wp_enqueue_style( 'imn-w2-admin-bar', IMN_W2_PLUGIN_URL . 'assets/css/admin-bar.css', array(), IMN_W2_VERSION );
        wp_enqueue_script( 'imn-w2-admin-bar', IMN_W2_PLUGIN_URL . 'assets/js/admin-bar.js', array(), IMN_W2_VERSION, true );
        wp_localize_script( 'imn-w2-admin-bar', 'imn_w2_bar', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'post_id'  => $post->ID,
            'nonce'    => wp_create_nonce( 'imn_w2_manual_push' ),
            'i18n'     => array(
                'pushing'       => __( 'Pushing...', 'indexmenow' ),
                'success'       => __( 'Pushed!', 'indexmenow' ),
                'error'         => __( 'Error', 'indexmenow' ),
                'error_generic' => __( 'An error occurred.', 'indexmenow' ),
                'error_network' => __( 'Network error. Please try again.', 'indexmenow' ),
            ),
        ) );
    }

    /**
     * Enqueue admin bar assets in admin area (for preview, etc.).
     *
     * @param string $hook Current admin page.
     */
    public function enqueue_admin_bar_assets( string $hook ): void {
        // Only load on post preview or when viewing from admin.
        if ( ! is_admin_bar_showing() ) {
            return;
        }

        // The admin bar CSS should be available everywhere in admin.
        wp_enqueue_style( 'imn-w2-admin-bar', IMN_W2_PLUGIN_URL . 'assets/css/admin-bar.css', array(), IMN_W2_VERSION );
    }

    public function handle_ajax(): void {
        check_ajax_referer( 'imn_w2_manual_push', 'nonce' );

        $post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;
        if ( $post_id < 1 ) {
            wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'indexmenow' ) ) );
        }

        // Check if user can edit this specific post.
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
