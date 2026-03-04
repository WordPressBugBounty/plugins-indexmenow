<?php
/**
 * Plugin Name: IndexMeNow
 * Description: Push your URLs to IndexMeNow for fast Google indexation. Supports manual push, auto-push on publish, and auto-push on update.
 * Version: 1.2.4
 * Author: IndexMeNow
 * Author URI: https://indexmenow.com
 * License: GPL-2.0-or-later
 * Text Domain: indexmenow
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'IMN_W2_VERSION', '1.2.4' );
define( 'IMN_W2_DB_VERSION', 1 );
define( 'IMN_W2_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMN_W2_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'IMN_W2_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'IMN_W2_API_BASE', 'https://tool.indexmenow.com/api/v1' );

// Performance and security constants.
define( 'IMN_W2_CACHE_DURATION', 5 * MINUTE_IN_SECONDS );
define( 'IMN_W2_RATE_LIMIT_SECONDS', 5 );
define( 'IMN_W2_SITEMAP_MAX_DEPTH', 3 );
define( 'IMN_W2_SITEMAP_MAX_URLS', 2000 );

/**
 * Encrypt a string using AES-256-CBC encryption.
 *
 * @param string $plain_text Plain text to encrypt.
 * @return string Encrypted string or empty string on failure.
 */
function imn_w2_encrypt( string $plain_text ): string {
    if ( empty( $plain_text ) ) {
        return '';
    }

    // Use AES-256-CBC encryption if openssl is available.
    if ( function_exists( 'openssl_encrypt' ) ) {
        $key = defined( 'AUTH_KEY' ) && AUTH_KEY ? AUTH_KEY : 'imn-w2-default-key';
        $key = hash( 'sha256', $key, true );
        $iv  = openssl_random_pseudo_bytes( 16 );

        $encrypted = openssl_encrypt( $plain_text, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
        if ( false === $encrypted ) {
            return '';
        }

        return 'enc:' . base64_encode( $iv . $encrypted );
    }

    // Fallback to base64 encoding if openssl is not available.
    return 'b64:' . base64_encode( $plain_text );
}

/**
 * Decrypt a string encrypted with imn_w2_encrypt().
 *
 * @param string $encrypted_text Encrypted string.
 * @return string Decrypted plain text or empty string on failure.
 */
function imn_w2_decrypt( string $encrypted_text ): string {
    if ( empty( $encrypted_text ) ) {
        return '';
    }

    // Handle base64 encoding.
    if ( 0 === strpos( $encrypted_text, 'b64:' ) ) {
        $decoded = base64_decode( substr( $encrypted_text, 4 ), true );
        return false === $decoded ? '' : $decoded;
    }

    // Handle AES-256-CBC encrypted data (enc:).
    if ( 0 === strpos( $encrypted_text, 'enc:' ) ) {
        if ( ! function_exists( 'openssl_decrypt' ) ) {
            return '';
        }

        $key  = defined( 'AUTH_KEY' ) && AUTH_KEY ? AUTH_KEY : 'imn-w2-default-key';
        $key  = hash( 'sha256', $key, true );
        $data = base64_decode( substr( $encrypted_text, 4 ), true );

        if ( false === $data || strlen( $data ) < 17 ) {
            return '';
        }

        $iv     = substr( $data, 0, 16 );
        $cipher = substr( $data, 16 );
        $plain  = openssl_decrypt( $cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

        return false === $plain ? '' : $plain;
    }

    // Assume plain text (legacy, not encrypted).
    return $encrypted_text;
}

/**
 * Get the decrypted API key.
 *
 * @return string Decrypted API key.
 */
function imn_w2_get_api_key(): string {
    $encrypted = get_option( 'imn_w2_api_key', '' );
    return imn_w2_decrypt( $encrypted );
}

/**
 * Save the encrypted API key.
 *
 * @param string $api_key Plain text API key.
 * @return bool True on success.
 */
function imn_w2_save_api_key( string $api_key ): bool {
    $encrypted = imn_w2_encrypt( $api_key );
    return update_option( 'imn_w2_api_key', $encrypted );
}

require_once IMN_W2_PLUGIN_DIR . 'includes/class-helpers.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-push-history.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-api-client.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-url-pusher.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-manual-push.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-auto-new-publish.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-auto-update.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-posts-list.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-dashboard-widget.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-low-credits-notice.php';
require_once IMN_W2_PLUGIN_DIR . 'includes/class-sitemap-push.php';

/**
 * Set default options and create custom tables on activation.
 */
function imn_w2_activate() {
    add_option( 'imn_w2_api_key', '' );
    add_option( 'imn_w2_project_mode', 'auto' );
    add_option( 'imn_w2_project_id', 0 );
    add_option( 'imn_w2_auto_new_publish', 'off' );
    add_option( 'imn_w2_auto_update', 'off' );
    add_option( 'imn_w2_post_types', array( 'post', 'page' ) );
    add_option( 'imn_w2_categories', array() );
    add_option( 'imn_w2_low_credits_threshold', 10 );
    add_option( 'imn_w2_credits', 0 );

    IMN_W2_Push_History::create_table();
    update_option( 'imn_w2_db_version', IMN_W2_DB_VERSION );
}
register_activation_hook( __FILE__, 'imn_w2_activate' );

/**
 * Check if DB schema needs updating on admin init.
 */
function imn_w2_maybe_update_db() {
    $current_db_version = (int) get_option( 'imn_w2_db_version', 0 );
    if ( $current_db_version < IMN_W2_DB_VERSION ) {
        IMN_W2_Push_History::create_table();
        update_option( 'imn_w2_db_version', IMN_W2_DB_VERSION );
    }
}
add_action( 'admin_init', 'imn_w2_maybe_update_db' );

/**
 * Add "Settings" link on the Plugins page.
 */
function imn_w2_plugin_action_links( array $links ): array {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=imn-w2-settings' ) ) . '">'
        . esc_html__( 'Settings', 'indexmenow' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . IMN_W2_PLUGIN_BASENAME, 'imn_w2_plugin_action_links' );

new IMN_W2_Settings_Page();
new IMN_W2_Manual_Push();
new IMN_W2_Auto_New_Publish();
new IMN_W2_Auto_Update();
new IMN_W2_Posts_List();
new IMN_W2_Dashboard_Widget();
new IMN_W2_Low_Credits_Notice();
new IMN_W2_Sitemap_Push();
