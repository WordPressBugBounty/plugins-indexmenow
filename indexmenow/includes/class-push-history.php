<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Push_History {

    /**
     * Get the table name.
     */
    public static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'imn_w2_push_history';
    }

    /**
     * Create the push history table.
     */
    public static function create_table(): void {
        global $wpdb;

        $table   = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            url VARCHAR(2048) NOT NULL,
            project_id INT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'success',
            message VARCHAR(255) NOT NULL DEFAULT '',
            push_trigger VARCHAR(20) NOT NULL DEFAULT 'manual',
            pushed_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_post_id (post_id),
            KEY idx_pushed_at (pushed_at)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Drop the push history table.
     */
    public static function drop_table(): void {
        global $wpdb;
        $table = self::table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
    }

    /**
     * Log a push history entry.
     *
     * @param array $data {
     *     @type int    $post_id      Post ID.
     *     @type string $url          URL pushed.
     *     @type int    $project_id   Project ID.
     *     @type string $status       'success', 'error', or 'skipped'.
     *     @type string $message      Result message.
     *     @type string $push_trigger 'manual', 'auto_publish', or 'auto_update'.
     * }
     */
    public static function log( array $data ): void {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert(
            self::table_name(),
            array(
                'post_id'      => isset( $data['post_id'] ) ? absint( $data['post_id'] ) : 0,
                'url'          => isset( $data['url'] ) ? $data['url'] : '',
                'project_id'   => isset( $data['project_id'] ) ? absint( $data['project_id'] ) : 0,
                'status'       => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'success',
                'message'      => isset( $data['message'] ) ? mb_substr( $data['message'], 0, 255 ) : '',
                'push_trigger' => isset( $data['push_trigger'] ) ? sanitize_key( $data['push_trigger'] ) : 'manual',
                'pushed_at'    => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%d', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Get push history for a specific post.
     *
     * @param int $post_id Post ID.
     * @param int $limit   Max entries to return.
     * @return array
     */
    public static function get_by_post( int $post_id, int $limit = 5 ): array {
        global $wpdb;

        $table = self::table_name();

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE post_id = %d ORDER BY pushed_at DESC LIMIT %d",
                $post_id,
                $limit
            ),
            ARRAY_A
        ) ?: array();
        // phpcs:enable
    }

    /**
     * Get paginated push history.
     *
     * @param int $page     Page number (1-based).
     * @param int $per_page Items per page.
     * @return array
     */
    public static function get_all( int $page = 1, int $per_page = 20 ): array {
        global $wpdb;

        $table  = self::table_name();
        $offset = ( $page - 1 ) * $per_page;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} ORDER BY pushed_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ),
            ARRAY_A
        ) ?: array();
        // phpcs:enable
    }

    /**
     * Count all push history entries.
     *
     * @return int
     */
    public static function count_all(): int {
        global $wpdb;

        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Delete push history entries older than a given number of days.
     *
     * @param int $days Number of days.
     * @return int Number of deleted entries.
     */
    public static function delete_older_than( int $days ): int {
        global $wpdb;

        $table = self::table_name();
        $date  = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE pushed_at < %s",
                $date
            )
        );
        // phpcs:enable

        return false === $deleted ? 0 : (int) $deleted;
    }

    /**
     * Delete all push history entries.
     *
     * @return int Number of deleted entries.
     */
    public static function delete_all(): int {
        global $wpdb;

        $table = self::table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is safe.
        $deleted = $wpdb->query( "TRUNCATE TABLE {$table}" );

        return false === $deleted ? 0 : (int) $deleted;
    }
}
