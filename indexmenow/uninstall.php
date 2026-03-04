<?php
/**
 * Fired when the plugin is uninstalled.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'imn_w2_api_key' );
delete_option( 'imn_w2_project_mode' );
delete_option( 'imn_w2_project_id' );
delete_option( 'imn_w2_projects_cache' );
delete_option( 'imn_w2_auto_new_publish' );
delete_option( 'imn_w2_auto_update' );
delete_option( 'imn_w2_post_types' );
delete_option( 'imn_w2_categories' );
delete_option( 'imn_w2_low_credits_threshold' );
delete_option( 'imn_w2_credits' );
delete_option( 'imn_w2_db_version' );

// Delete transients.
delete_transient( 'imn_w2_low_credits_dismissed' );
delete_transient( 'imn_w2_key_validated' );

global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_imn_w2_pushed' )
);

// Drop push history table.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}imn_w2_push_history" );

// Delete URL status transients.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_imn_w2_url_status_%',
        '_transient_timeout_imn_w2_url_status_%'
    )
);
