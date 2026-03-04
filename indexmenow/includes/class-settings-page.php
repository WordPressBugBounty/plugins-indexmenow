<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Settings_Page {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_imn_w2_fetch_projects', array( $this, 'ajax_fetch_projects' ) );
        add_action( 'wp_ajax_imn_w2_push_history', array( $this, 'ajax_push_history' ) );
        add_action( 'wp_ajax_imn_w2_refresh_credits', array( $this, 'ajax_refresh_credits' ) );
        add_action( 'wp_ajax_imn_w2_purge_history', array( $this, 'ajax_purge_history' ) );
    }

    public function add_menu_page(): void {
        add_options_page(
            'IndexMeNow',
            'IndexMeNow',
            'manage_options',
            'imn-w2-settings',
            array( $this, 'render_page' )
        );
    }

    public function register_settings(): void {
        register_setting( 'imn_w2_settings', 'imn_w2_api_key', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_api_key' ),
            'default'           => '',
        ) );
        register_setting( 'imn_w2_settings', 'imn_w2_project_mode', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_project_mode' ),
            'default'           => 'auto',
        ) );
        register_setting( 'imn_w2_settings', 'imn_w2_project_id', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ) );
        register_setting( 'imn_w2_settings', 'imn_w2_auto_new_publish', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
            'default'           => 'off',
        ) );
        register_setting( 'imn_w2_settings', 'imn_w2_auto_update', array(
            'type'              => 'string',
            'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
            'default'           => 'off',
        ) );
        register_setting( 'imn_w2_settings', 'imn_w2_post_types', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_post_types' ),
            'default'           => array( 'post', 'page' ),
        ) );
        register_setting( 'imn_w2_settings', 'imn_w2_categories', array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize_categories' ),
            'default'           => array(),
        ) );
        register_setting( 'imn_w2_settings', 'imn_w2_low_credits_threshold', array(
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 10,
        ) );
    }

    public function sanitize_api_key( $value ): string {
        $value = sanitize_text_field( $value );

        if ( empty( $value ) ) {
            delete_transient( 'imn_w2_key_validated' );
            return '';
        }

        // If value is already encrypted, return it as-is (WordPress calls sanitize twice).
        if ( 0 === strpos( $value, 'b64:' ) || 0 === strpos( $value, 'enc:' ) ) {
            return $value;
        }

        $stored_encrypted = get_option( 'imn_w2_api_key', '' );

        // If we already have a stored key, keep it without re-validation.
        if ( ! empty( $stored_encrypted ) ) {
            return $stored_encrypted;
        }

        // No stored key yet - check if just validated via AJAX.
        $validated_hash = get_transient( 'imn_w2_key_validated' );
        if ( $validated_hash && $validated_hash === md5( $value ) ) {
            delete_transient( 'imn_w2_key_validated' );
            return imn_w2_encrypt( $value );
        }

        // New key without AJAX validation - validate with API.
        $client   = new IMN_W2_Api_Client( $value );
        $is_valid = $client->validate_token();

        if ( ! $is_valid ) {
            add_settings_error(
                'imn_w2_api_key',
                'invalid_key',
                __( 'Invalid API key. Please check your key and try again.', 'indexmenow' ),
                'error'
            );
            return '';
        }

        $credits = $client->get_credits();
        if ( false !== $credits ) {
            update_option( 'imn_w2_credits', $credits, false );
        }

        // Cache the project list for display.
        $projects = $client->list_projects();
        if ( is_array( $projects ) ) {
            update_option( 'imn_w2_projects_cache', $projects );
        }

        return imn_w2_encrypt( $value );
    }

    public function sanitize_project_mode( $value ): string {
        return in_array( $value, array( 'auto', 'existing' ), true ) ? $value : 'auto';
    }

    public function sanitize_checkbox( $value ): string {
        return 'on' === $value ? 'on' : 'off';
    }

    public function sanitize_post_types( $value ): array {
        if ( ! is_array( $value ) ) {
            return array( 'post', 'page' );
        }
        return array_map( 'sanitize_key', $value );
    }

    public function sanitize_categories( $value ): array {
        if ( ! is_array( $value ) ) {
            return array();
        }
        return array_map( 'absint', $value );
    }

    public function enqueue_assets( string $hook ): void {
        if ( 'settings_page_imn-w2-settings' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'imn-w2-admin', IMN_W2_PLUGIN_URL . 'assets/css/admin.css', array(), IMN_W2_VERSION );
        wp_enqueue_script( 'imn-w2-settings', IMN_W2_PLUGIN_URL . 'assets/js/settings.js', array(), IMN_W2_VERSION, true );
        wp_localize_script( 'imn-w2-settings', 'imn_w2_settings', array(
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'imn_w2_fetch_projects' ),
            'history_nonce'  => wp_create_nonce( 'imn_w2_push_history' ),
            'credits_nonce'  => wp_create_nonce( 'imn_w2_refresh_credits' ),
            'purge_nonce'    => wp_create_nonce( 'imn_w2_purge_history' ),
            'sitemap_nonce'  => wp_create_nonce( 'imn_w2_sitemap_push' ),
            'i18n'           => array(
                'loading'            => __( 'Loading projects...', 'indexmenow' ),
                'error'              => __( 'Could not load projects. Check your API key.', 'indexmenow' ),
                'no_projects'        => __( 'No projects found in your account.', 'indexmenow' ),
                'select'             => __( '-- Select a project --', 'indexmenow' ),
                'no_history'         => __( 'No push history yet.', 'indexmenow' ),
                'loading_history'    => __( 'Loading...', 'indexmenow' ),
                'error_history'      => __( 'Error loading history.', 'indexmenow' ),
                'refreshing'         => __( 'Refreshing...', 'indexmenow' ),
                'refresh_error'      => __( 'Could not refresh credits.', 'indexmenow' ),
                'confirm_purge'      => __( 'Are you sure you want to delete the selected history entries? This action cannot be undone.', 'indexmenow' ),
                'purge_error'        => __( 'Could not purge history.', 'indexmenow' ),
                'loading_sitemap'    => __( 'Loading sitemap...', 'indexmenow' ),
                'pushing_sitemap'    => __( 'Pushing URLs...', 'indexmenow' ),
                'sitemap_error'      => __( 'Could not load sitemap.', 'indexmenow' ),
                /* translators: 1: number of URLs, 2: number of credits */
                'confirm_sitemap'    => __( 'Push %1$d URLs from sitemap to IndexMeNow? This will use %2$d credits.', 'indexmenow' ),
                'no_urls_selected'   => __( 'No URLs selected.', 'indexmenow' ),
            ),
        ) );
    }

    public function ajax_fetch_projects(): void {
        check_ajax_referer( 'imn_w2_fetch_projects', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        $api_key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        if ( empty( $api_key ) ) {
            $api_key = imn_w2_get_api_key();
        }

        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API key not configured.', 'indexmenow' ) ) );
        }

        $client  = new IMN_W2_Api_Client( $api_key );
        $credits = $client->get_credits();

        if ( false === $credits ) {
            wp_send_json_error( array( 'message' => __( 'Invalid API key.', 'indexmenow' ) ) );
        }

        // Mark this key as just validated (for form save to skip re-validation).
        set_transient( 'imn_w2_key_validated', md5( $api_key ), 300 );
        update_option( 'imn_w2_credits', $credits, false );

        $projects = $client->list_projects();
        if ( false === $projects ) {
            wp_send_json_error( array( 'message' => __( 'Could not load projects.', 'indexmenow' ) ) );
        }

        update_option( 'imn_w2_projects_cache', $projects );

        wp_send_json_success( array(
            'projects' => $projects,
            'credits'  => $credits,
        ) );
    }

    public function ajax_push_history(): void {
        check_ajax_referer( 'imn_w2_push_history', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        $page     = isset( $_POST['page'] ) ? max( 1, (int) $_POST['page'] ) : 1;
        $per_page = 20;
        $entries  = IMN_W2_Push_History::get_all( $page, $per_page );
        $total    = IMN_W2_Push_History::count_all();

        wp_send_json_success( array(
            'entries'    => $entries,
            'total'      => $total,
            'page'       => $page,
            'total_pages' => (int) ceil( $total / $per_page ),
        ) );
    }

    public function ajax_refresh_credits(): void {
        check_ajax_referer( 'imn_w2_refresh_credits', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            wp_send_json_error( array( 'message' => __( 'API key not configured.', 'indexmenow' ) ) );
        }

        $client  = new IMN_W2_Api_Client( $api_key );
        $credits = $client->get_credits();

        if ( false === $credits ) {
            wp_send_json_error( array( 'message' => __( 'Could not refresh credits. Check your API key.', 'indexmenow' ) ) );
        }

        update_option( 'imn_w2_credits', $credits, false );

        wp_send_json_success( array( 'credits' => $credits ) );
    }

    public function ajax_purge_history(): void {
        check_ajax_referer( 'imn_w2_purge_history', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'indexmenow' ) ) );
        }

        $days = isset( $_POST['days'] ) ? (int) $_POST['days'] : 0;

        if ( 0 === $days ) {
            // Delete all history.
            $deleted = IMN_W2_Push_History::delete_all();
        } else {
            // Delete history older than X days.
            $deleted = IMN_W2_Push_History::delete_older_than( $days );
        }

        wp_send_json_success( array(
            'deleted' => $deleted,
            'message' => sprintf(
                /* translators: %d: number of deleted entries */
                _n( '%d entry deleted.', '%d entries deleted.', $deleted, 'indexmenow' ),
                $deleted
            ),
        ) );
    }

    public function render_page(): void {
        $api_key                = imn_w2_get_api_key();
        $project_mode           = get_option( 'imn_w2_project_mode', 'auto' );
        $project_id             = (int) get_option( 'imn_w2_project_id', 0 );
        $auto_new_publish       = get_option( 'imn_w2_auto_new_publish', 'off' );
        $auto_update            = get_option( 'imn_w2_auto_update', 'off' );
        $post_types             = get_option( 'imn_w2_post_types', array( 'post', 'page' ) );
        $categories             = get_option( 'imn_w2_categories', array() );
        $low_credits_threshold  = (int) get_option( 'imn_w2_low_credits_threshold', 10 );
        $credits                = get_option( 'imn_w2_credits', 0 );
        $is_connected           = ! empty( $api_key );
        $projects_cache         = get_option( 'imn_w2_projects_cache', array() );
        $available_post_types   = get_post_types( array( 'public' => true ), 'objects' );
        $available_categories   = get_categories( array( 'hide_empty' => false ) );
        $domain                 = wp_parse_url( home_url(), PHP_URL_HOST );

        include IMN_W2_PLUGIN_DIR . 'views/settings-page.php';
    }
}
