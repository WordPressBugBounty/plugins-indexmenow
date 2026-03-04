<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Auto_Update {

    public function __construct() {
        add_action( 'post_updated', array( $this, 'on_update' ), 10, 3 );
    }

    public function on_update( int $post_id, WP_Post $post_after, WP_Post $post_before ): void {
        if ( get_option( 'imn_w2_auto_update', 'off' ) !== 'on' ) {
            return;
        }

        if ( 'publish' !== $post_after->post_status || 'publish' !== $post_before->post_status ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $content_changed = $post_after->post_content !== $post_before->post_content;
        $title_changed   = $post_after->post_title !== $post_before->post_title;

        if ( ! $content_changed && ! $title_changed ) {
            return;
        }

        $allowed_types = IMN_W2_Helpers::get_allowed_post_types();
        if ( ! in_array( $post_after->post_type, $allowed_types, true ) ) {
            return;
        }

        // Check category filter (only for posts with categories).
        if ( ! IMN_W2_Helpers::passes_category_filter( $post_after ) ) {
            return;
        }

        $permalink = get_permalink( $post_id );
        if ( ! $permalink ) {
            return;
        }

        $pusher = new IMN_W2_Url_Pusher();
        $result = $pusher->push_url( $permalink, array(
            'post_id' => $post_id,
            'trigger' => 'auto_update',
        ) );

        if ( $result['success'] ) {
            update_post_meta( $post_id, '_imn_w2_pushed', current_time( 'mysql' ) );
        }
    }

}
