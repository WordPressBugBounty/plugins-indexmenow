<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IMN_W2_Auto_New_Publish {

    public function __construct() {
        add_action( 'transition_post_status', array( $this, 'on_transition' ), 10, 3 );
    }

    public function on_transition( string $new_status, string $old_status, WP_Post $post ): void {
        if ( get_option( 'imn_w2_auto_new_publish', 'off' ) !== 'on' ) {
            return;
        }

        if ( 'publish' !== $new_status || 'publish' === $old_status ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        $allowed_types = IMN_W2_Helpers::get_allowed_post_types();
        if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
            return;
        }

        // Check category filter (only for posts with categories).
        if ( ! IMN_W2_Helpers::passes_category_filter( $post ) ) {
            return;
        }

        $permalink = get_permalink( $post->ID );
        if ( ! $permalink ) {
            return;
        }

        $pusher = new IMN_W2_Url_Pusher();
        $result = $pusher->push_url( $permalink, array(
            'post_id'            => $post->ID,
            'trigger'            => 'auto_publish',
            'skip_status_check'  => true,
        ) );

        if ( $result['success'] ) {
            update_post_meta( $post->ID, '_imn_w2_pushed', current_time( 'mysql' ) );
        }
    }

}
