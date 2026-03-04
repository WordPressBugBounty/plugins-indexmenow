<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="imn-w2-metabox">
    <p class="imn-w2-metabox__url">
        <strong><?php esc_html_e( 'URL:', 'indexmenow' ); ?></strong><br />
        <code><?php echo esc_html( $permalink ); ?></code>
    </p>

    <?php if ( ! empty( $history ) ) : ?>
        <div class="imn-w2-metabox__history">
            <strong><?php esc_html_e( 'Recent pushes:', 'indexmenow' ); ?></strong>
            <ul class="imn-w2-history-list">
                <?php foreach ( $history as $imn_w2_entry ) : ?>
                    <li class="imn-w2-history-item imn-w2-history-item--<?php echo esc_attr( $imn_w2_entry['status'] ); ?>">
                        <span class="imn-w2-history-status"><?php
                            if ( 'success' === $imn_w2_entry['status'] ) {
                                echo '&#10003;';
                            } elseif ( 'skipped' === $imn_w2_entry['status'] ) {
                                echo '&#8212;';
                            } else {
                                echo '&#10007;';
                            }
                        ?></span>
                        <span class="imn-w2-history-date"><?php echo esc_html( $imn_w2_entry['pushed_at'] ); ?></span>
                        <span class="imn-w2-history-trigger"><?php echo esc_html( $imn_w2_entry['push_trigger'] ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ( $pushed_at ) : ?>
        <p class="imn-w2-metabox__status">
            <?php
            printf(
                /* translators: %s: date and time of last push */
                esc_html__( 'Last pushed: %s', 'indexmenow' ),
                esc_html( $pushed_at )
            );
            ?>
        </p>
    <?php endif; ?>

    <button type="button"
            class="button button-primary imn-w2-push-btn"
            data-post-id="<?php echo esc_attr( $post->ID ); ?>"
            data-nonce="<?php echo esc_attr( $nonce ); ?>">
        <?php esc_html_e( 'Push to IndexMeNow', 'indexmenow' ); ?>
    </button>

    <span class="imn-w2-push-status" style="display:none;"></span>
    <span class="spinner imn-w2-push-spinner"></span>
</div>
