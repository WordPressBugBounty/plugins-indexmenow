<?php
/**
 * Low credits admin notice.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Low_Credits_Notice {

    public function __construct() {
        add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_imn_w2_dismiss_low_credits', array( $this, 'dismiss_notice' ) );
    }

    /**
     * Show low credits notice if applicable.
     */
    public function maybe_show_notice(): void {
        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Check if notice was dismissed recently (24 hours).
        $dismissed_until = get_transient( 'imn_w2_low_credits_dismissed' );
        if ( $dismissed_until ) {
            return;
        }

        $credits   = (int) get_option( 'imn_w2_credits', 0 );
        $threshold = (int) get_option( 'imn_w2_low_credits_threshold', 10 );

        if ( $credits > $threshold ) {
            return;
        }

        $class   = 'notice notice-warning is-dismissible imn-w2-low-credits-notice';
        $message = '';

        if ( $credits <= 0 ) {
            $class   = 'notice notice-error is-dismissible imn-w2-low-credits-notice';
            $message = sprintf(
                /* translators: %s: link to buy credits */
                __( 'IndexMeNow: You have no credits remaining. %s to continue pushing URLs.', 'indexmenow' ),
                '<a href="https://indexmenow.com" target="_blank" rel="noopener">' . __( 'Buy credits', 'indexmenow' ) . '</a>'
            );
        } else {
            $message = sprintf(
                /* translators: 1: credits count, 2: link to buy credits */
                __( 'IndexMeNow: You have only %1$d credits remaining. %2$s to avoid interruption.', 'indexmenow' ),
                $credits,
                '<a href="https://indexmenow.com" target="_blank" rel="noopener">' . __( 'Buy more credits', 'indexmenow' ) . '</a>'
            );
        }

        printf(
            '<div class="%1$s" data-nonce="%2$s"><p>%3$s</p></div>',
            esc_attr( $class ),
            esc_attr( wp_create_nonce( 'imn_w2_dismiss_low_credits' ) ),
            wp_kses( $message, array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) )
        );
    }

    /**
     * Enqueue dismiss handler script.
     */
    public function enqueue_assets(): void {
        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $credits   = (int) get_option( 'imn_w2_credits', 0 );
        $threshold = (int) get_option( 'imn_w2_low_credits_threshold', 10 );

        if ( $credits > $threshold ) {
            return;
        }

        $dismissed_until = get_transient( 'imn_w2_low_credits_dismissed' );
        if ( $dismissed_until ) {
            return;
        }

        wp_enqueue_script( 'imn-w2-low-credits-notice', IMN_W2_PLUGIN_URL . 'assets/js/low-credits-notice.js', array(), IMN_W2_VERSION, true );
        wp_localize_script( 'imn-w2-low-credits-notice', 'imn_w2_low_credits', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
        ) );
    }

    /**
     * Handle AJAX dismissal.
     */
    public function dismiss_notice(): void {
        check_ajax_referer( 'imn_w2_dismiss_low_credits', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error();
        }

        // Dismiss for 24 hours.
        set_transient( 'imn_w2_low_credits_dismissed', true, DAY_IN_SECONDS );

        wp_send_json_success();
    }
}
