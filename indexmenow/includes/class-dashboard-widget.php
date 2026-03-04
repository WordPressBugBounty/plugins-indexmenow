<?php
/**
 * Dashboard widget showing credits and recent pushes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Dashboard_Widget {

    public function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
    }

    /**
     * Register the dashboard widget.
     */
    public function register_widget(): void {
        $api_key = imn_w2_get_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'imn_w2_dashboard_widget',
            'IndexMeNow',
            array( $this, 'render_widget' )
        );
    }

    /**
     * Render the dashboard widget content.
     */
    public function render_widget(): void {
        $credits = get_option( 'imn_w2_credits', 0 );
        $history = IMN_W2_Push_History::get_all( 1, 5 );
        $low_credits_threshold = (int) get_option( 'imn_w2_low_credits_threshold', 10 );
        $credits_class = ( $credits <= $low_credits_threshold && $credits > 0 ) ? 'imn-w2-credits-low' : '';
        $credits_class = ( $credits <= 0 ) ? 'imn-w2-credits-empty' : $credits_class;
        ?>
        <div class="imn-w2-dashboard-widget">
            <div class="imn-w2-dashboard-credits <?php echo esc_attr( $credits_class ); ?>">
                <span class="imn-w2-dashboard-credits-label"><?php esc_html_e( 'Credits:', 'indexmenow' ); ?></span>
                <span class="imn-w2-dashboard-credits-value"><?php echo esc_html( $credits ); ?></span>
                <?php if ( $credits <= $low_credits_threshold ) : ?>
                    <a href="https://indexmenow.com" target="_blank" rel="noopener" class="imn-w2-dashboard-buy-link">
                        <?php esc_html_e( 'Buy credits', 'indexmenow' ); ?>
                    </a>
                <?php endif; ?>
            </div>

            <h4><?php esc_html_e( 'Recent pushes', 'indexmenow' ); ?></h4>

            <?php if ( empty( $history ) ) : ?>
                <p class="imn-w2-dashboard-no-history"><?php esc_html_e( 'No push history yet.', 'indexmenow' ); ?></p>
            <?php else : ?>
                <ul class="imn-w2-dashboard-history">
                    <?php foreach ( $history as $entry ) : ?>
                        <li class="imn-w2-dashboard-history-item imn-w2-dashboard-history-item--<?php echo esc_attr( $entry['status'] ); ?>">
                            <span class="imn-w2-dashboard-history-status" title="<?php echo esc_attr( $entry['status'] ); ?>">
                                <?php
                                if ( 'success' === $entry['status'] ) {
                                    echo '&#10003;';
                                } elseif ( 'skipped' === $entry['status'] ) {
                                    echo '&#8212;';
                                } else {
                                    echo '&#10007;';
                                }
                                ?>
                            </span>
                            <span class="imn-w2-dashboard-history-url" title="<?php echo esc_attr( $entry['url'] ); ?>">
                                <?php
                                $url_display = strlen( $entry['url'] ) > 60
                                    ? substr( $entry['url'], 0, 57 ) . '...'
                                    : $entry['url'];
                                echo esc_html( $url_display );
                                ?>
                            </span>
                            <span class="imn-w2-dashboard-history-date">
                                <?php
                                $timestamp = strtotime( $entry['pushed_at'] );
                                if ( false !== $timestamp ) {
                                    echo esc_html( sprintf(
                                        /* translators: %s: human-readable time difference */
                                        __( '%s ago', 'indexmenow' ),
                                        human_time_diff( $timestamp, current_time( 'timestamp' ) )
                                    ) );
                                } else {
                                    echo esc_html( $entry['pushed_at'] );
                                }
                                ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p class="imn-w2-dashboard-footer">
                <a href="<?php echo esc_url( admin_url( 'options-general.php?page=imn-w2-settings' ) ); ?>">
                    <?php esc_html_e( 'Settings', 'indexmenow' ); ?>
                </a>
                |
                <a href="<?php echo esc_url( admin_url( 'options-general.php?page=imn-w2-settings#imn-w2-history-wrap' ) ); ?>">
                    <?php esc_html_e( 'Full history', 'indexmenow' ); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
