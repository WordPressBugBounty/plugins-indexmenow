<?php
/**
 * Settings page template.
 *
 * @var string $api_key
 * @var string $project_mode
 * @var int    $project_id
 * @var string $auto_new_publish
 * @var string $auto_update
 * @var array  $post_types
 * @var array  $categories
 * @var int    $low_credits_threshold
 * @var int    $credits
 * @var bool   $is_connected
 * @var array  $projects_cache
 * @var array  $available_post_types
 * @var array  $available_categories
 * @var string $domain
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap imn-w2-settings">
    <h1><?php esc_html_e( 'IndexMeNow Settings', 'indexmenow' ); ?></h1>

    <?php settings_errors(); ?>

    <div class="imn-w2-status-card" data-connected-text="<?php esc_attr_e( 'Connected', 'indexmenow' ); ?>">
        <h2><?php esc_html_e( 'Connection Status', 'indexmenow' ); ?></h2>
        <p class="imn-w2-status <?php echo $is_connected ? 'imn-w2-status--connected' : 'imn-w2-status--disconnected'; ?>" id="imn-w2-connection-status">
            <?php echo $is_connected ? esc_html__( 'Connected', 'indexmenow' ) : esc_html__( 'Not connected', 'indexmenow' ); ?>
        </p>
        <p id="imn-w2-credits-row" <?php echo $is_connected ? '' : 'style="display:none;"'; ?>>
            <?php esc_html_e( 'Credits:', 'indexmenow' ); ?>
            <strong id="imn-w2-credits-display"><?php echo esc_html( $credits ); ?></strong>
            <button type="button" id="imn-w2-refresh-credits" class="button button-small">
                <?php esc_html_e( 'Refresh', 'indexmenow' ); ?>
            </button>
            <span class="spinner imn-w2-credits-spinner"></span>
        </p>
        <?php if ( ! $is_connected ) : ?>
            <p id="imn-w2-connect-hint"><?php esc_html_e( 'Enter your API key below to connect.', 'indexmenow' ); ?></p>
        <?php endif; ?>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'imn_w2_settings' ); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="imn_w2_api_key"><?php esc_html_e( 'API Key', 'indexmenow' ); ?></label></th>
                <td>
                    <div class="imn-w2-api-key-wrap">
                        <input type="password" id="imn_w2_api_key" name="imn_w2_api_key"
                               value="<?php echo esc_attr( $api_key ); ?>"
                               class="regular-text" autocomplete="off" />
                        <button type="button" id="imn-w2-toggle-key" class="button imn-w2-toggle-key" title="<?php esc_attr_e( 'Show/Hide API key', 'indexmenow' ); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button type="button" id="imn-w2-verify-key" class="button">
                            <?php esc_html_e( 'Verify & load projects', 'indexmenow' ); ?>
                        </button>
                        <span class="spinner imn-w2-key-spinner"></span>
                        <span id="imn-w2-key-status" style="display:none;"></span>
                    </div>
                    <p class="description">
                        <?php
                        printf(
                            /* translators: %1$s: opening link tag, %2$s: closing link tag */
                            esc_html__( 'Find your API key at %1$stool.indexmenow.com/docapi%2$s.', 'indexmenow' ),
                            '<a href="https://tool.indexmenow.com/docapi" target="_blank" rel="noopener">',
                            '</a>'
                        );
                        ?>
                    </p>
                </td>
            </tr>
            <tr id="imn-w2-project-row" <?php echo $is_connected ? '' : 'style="display:none;"'; ?>>
                <th scope="row"><?php esc_html_e( 'Project', 'indexmenow' ); ?></th>
                <td>
                    <fieldset>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="radio" name="imn_w2_project_mode" value="auto"
                                   <?php checked( $project_mode, 'auto' ); ?> />
                            <?php
                            printf(
                                /* translators: %1$s: opening code tag, %2$s: domain name, %3$s: closing code tag */
                                esc_html__( 'Create a new project automatically: %1$s%2$s (wp-w2)%3$s', 'indexmenow' ),
                                '<code>',
                                esc_html( $domain ),
                                '</code>'
                            );
                            ?>
                        </label>
                        <label style="display: block; margin-bottom: 8px;">
                            <input type="radio" name="imn_w2_project_mode" value="existing"
                                   <?php checked( $project_mode, 'existing' ); ?> />
                            <?php esc_html_e( 'Use an existing project:', 'indexmenow' ); ?>
                        </label>
                        <select id="imn_w2_project_id" name="imn_w2_project_id"
                                class="imn-w2-project-select"
                                <?php echo $project_mode !== 'existing' ? 'disabled' : ''; ?>>
                            <option value="0"><?php esc_html_e( '-- Select a project --', 'indexmenow' ); ?></option>
                            <?php if ( is_array( $projects_cache ) ) : ?>
                                <?php foreach ( $projects_cache as $imn_w2_project ) : ?>
                                    <option value="<?php echo esc_attr( $imn_w2_project['id'] ); ?>"
                                            <?php selected( $project_id, (int) $imn_w2_project['id'] ); ?>>
                                        <?php echo esc_html( $imn_w2_project['name'] ); ?>
                                        (<?php echo esc_html( isset( $imn_w2_project['total_urls'] ) ? $imn_w2_project['total_urls'] : 0 ); ?> URLs)
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Choose where your URLs will be sent. In auto mode, a project named after your domain will be created on first push.', 'indexmenow' ); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Auto-push on new publish', 'indexmenow' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="imn_w2_auto_new_publish" value="on"
                               <?php checked( $auto_new_publish, 'on' ); ?> />
                        <?php esc_html_e( 'Automatically push URLs when a post is first published', 'indexmenow' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Auto-push on update', 'indexmenow' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="imn_w2_auto_update" value="on"
                               <?php checked( $auto_update, 'on' ); ?> />
                        <?php esc_html_e( 'Automatically push URLs when a published post\'s content or title is updated', 'indexmenow' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Post types', 'indexmenow' ); ?></th>
                <td>
                    <?php foreach ( $available_post_types as $imn_w2_pt ) : ?>
                        <?php if ( 'attachment' === $imn_w2_pt->name ) continue; ?>
                        <label style="display: block; margin-bottom: 4px;">
                            <input type="checkbox" name="imn_w2_post_types[]"
                                   value="<?php echo esc_attr( $imn_w2_pt->name ); ?>"
                                   <?php checked( in_array( $imn_w2_pt->name, $post_types, true ) ); ?> />
                            <?php echo esc_html( $imn_w2_pt->label ); ?>
                        </label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e( 'Select which post types can be pushed to IndexMeNow.', 'indexmenow' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Category filter', 'indexmenow' ); ?></th>
                <td>
                    <?php if ( ! empty( $available_categories ) ) : ?>
                        <div class="imn-w2-categories-wrap" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                            <?php foreach ( $available_categories as $imn_w2_cat ) : ?>
                                <label style="display: block; margin-bottom: 4px;">
                                    <input type="checkbox" name="imn_w2_categories[]"
                                           value="<?php echo esc_attr( $imn_w2_cat->term_id ); ?>"
                                           <?php checked( in_array( $imn_w2_cat->term_id, $categories, true ) ); ?> />
                                    <?php echo esc_html( $imn_w2_cat->name ); ?>
                                    <span class="imn-w2-cat-count">(<?php echo esc_html( $imn_w2_cat->count ); ?>)</span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php esc_html_e( 'Only auto-push posts from selected categories. Leave empty to push all categories.', 'indexmenow' ); ?></p>
                    <?php else : ?>
                        <p class="description"><?php esc_html_e( 'No categories found.', 'indexmenow' ); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="imn_w2_low_credits_threshold"><?php esc_html_e( 'Low credits alert', 'indexmenow' ); ?></label>
                </th>
                <td>
                    <input type="number" id="imn_w2_low_credits_threshold" name="imn_w2_low_credits_threshold"
                           value="<?php echo esc_attr( $low_credits_threshold ); ?>"
                           min="0" max="1000" step="1" class="small-text" />
                    <p class="description"><?php esc_html_e( 'Show a warning when credits fall below this threshold. Set to 0 to disable.', 'indexmenow' ); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button( esc_html__( 'Save Settings', 'indexmenow' ) ); ?>
    </form>

    <?php if ( $is_connected ) : ?>
    <hr />
    <h2><?php esc_html_e( 'Sitemap Push', 'indexmenow' ); ?></h2>
    <p class="description"><?php esc_html_e( 'Push all URLs from your sitemap to IndexMeNow. Useful for bulk indexation of existing content.', 'indexmenow' ); ?></p>
    <div class="imn-w2-sitemap-wrap">
        <button type="button" id="imn-w2-load-sitemap" class="button">
            <?php esc_html_e( 'Load sitemap URLs', 'indexmenow' ); ?>
        </button>
        <span class="spinner imn-w2-sitemap-spinner"></span>
        <span id="imn-w2-sitemap-status"></span>
    </div>
    <div id="imn-w2-sitemap-preview" style="display: none; margin-top: 15px;">
        <p>
            <strong id="imn-w2-sitemap-count"></strong>
            <button type="button" id="imn-w2-select-all-urls" class="button button-small"><?php esc_html_e( 'Select all', 'indexmenow' ); ?></button>
            <button type="button" id="imn-w2-deselect-all-urls" class="button button-small"><?php esc_html_e( 'Deselect all', 'indexmenow' ); ?></button>
        </p>
        <div id="imn-w2-sitemap-urls" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff; margin-bottom: 10px;"></div>
        <p>
            <button type="button" id="imn-w2-push-sitemap" class="button button-primary">
                <?php esc_html_e( 'Push selected URLs', 'indexmenow' ); ?>
            </button>
            <span class="spinner imn-w2-push-sitemap-spinner"></span>
            <span id="imn-w2-push-sitemap-status"></span>
        </p>
    </div>

    <hr />
    <h2><?php esc_html_e( 'Push History', 'indexmenow' ); ?></h2>
    <div class="imn-w2-history-actions">
        <label for="imn-w2-purge-days"><?php esc_html_e( 'Delete entries older than:', 'indexmenow' ); ?></label>
        <select id="imn-w2-purge-days">
            <option value="30"><?php esc_html_e( '30 days', 'indexmenow' ); ?></option>
            <option value="60"><?php esc_html_e( '60 days', 'indexmenow' ); ?></option>
            <option value="90"><?php esc_html_e( '90 days', 'indexmenow' ); ?></option>
            <option value="0"><?php esc_html_e( 'All history', 'indexmenow' ); ?></option>
        </select>
        <button type="button" id="imn-w2-purge-history" class="button">
            <?php esc_html_e( 'Purge', 'indexmenow' ); ?>
        </button>
        <span class="spinner imn-w2-purge-spinner"></span>
        <span id="imn-w2-purge-status" style="display:none;"></span>
    </div>
    <div id="imn-w2-history-wrap">
        <table class="wp-list-table widefat fixed striped imn-w2-history-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Date', 'indexmenow' ); ?></th>
                    <th><?php esc_html_e( 'URL', 'indexmenow' ); ?></th>
                    <th><?php esc_html_e( 'Post', 'indexmenow' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'indexmenow' ); ?></th>
                    <th><?php esc_html_e( 'Trigger', 'indexmenow' ); ?></th>
                    <th><?php esc_html_e( 'Message', 'indexmenow' ); ?></th>
                </tr>
            </thead>
            <tbody id="imn-w2-history-body">
                <tr><td colspan="6"><?php esc_html_e( 'Loading...', 'indexmenow' ); ?></td></tr>
            </tbody>
        </table>
        <div class="imn-w2-history-pagination" id="imn-w2-history-pagination"></div>
    </div>
    <?php endif; ?>
</div>
