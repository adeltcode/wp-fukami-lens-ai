<?php
/**
 * Admin logic: menus, settings, dashboard widgets, metaboxes.
 *
 * @package FukamiLensAI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'FUKAMI_LENS_Admin' ) ) {
    /**
     * Class FUKAMI_LENS_Admin
     * Handles admin-side hooks and UI for WP Fukami Lens AI.
     */
    class FUKAMI_LENS_Admin {
        /**
         * Constructor: Hooks admin actions.
         */
        public function __construct() {
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
            add_action( 'add_meta_boxes', [ $this, 'add_metabox' ] );
        }

        /**
         * Enqueue admin JS/CSS assets for the plugin.
         *
         * @param string $hook The current admin page.
         */
        public function enqueue_assets( $hook ) {
            global $typenow;
            // Always enqueue admin.css for plugin admin pages
            wp_enqueue_style(
                'fukami-lens-admin',
                plugins_url( '../assets/css/admin.css', __FILE__ )
            );
            // Always enqueue admin.js for plugin admin pages
            wp_enqueue_script(
                'fukami-lens-admin',
                plugins_url( '../assets/admin.js', __FILE__ ),
                [ 'jquery' ],
                null,
                true
            );
            wp_localize_script( 'fukami-lens-admin', 'fukami_lens_ajax', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'fukami_lens_check_nonce' ),
                'ask_ai_nonce' => wp_create_nonce( 'fukami_lens_ask_ai_nonce' ),
                'chunk_posts_nonce' => wp_create_nonce( 'fukami_lens_chunk_posts_nonce' )
            ]);
        }

        /**
         * Add the grammar checker metabox to post editor.
         */
        public function add_metabox() {
            add_meta_box(
                'fukami_lens_grammar_checker',
                esc_html__( 'WP Fukami Lens AI', 'wp-fukami-lens-ai' ),
                [ $this, 'render_metabox' ],
                null,
                'normal',
                'high'
            );
        }

        /**
         * Render the metabox HTML.
         *
         * @param WP_Post $post The current post object.
         */
        public function render_metabox( $post ) {
            $settings_url = admin_url('options-general.php?page=fukami-lens-settings');
            $ai_provider = get_option('fukami_lens_ai_provider', 'openai');
            $provider_label = $ai_provider === 'anthropic' ? 'Anthropic' : 'OpenAI';
            ?>
            <div class="fukami-lens-admin">
                <div id="fukami-lens-grammar-results"></div>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div id="fukami-lens-check-btn-container">
                        <button type="button" class="button" id="fukami-lens-check-btn"><?php esc_html_e('Proofread', 'wp-fukami-lens-ai'); ?></button>
                        <span style="margin-left: 12px; color: #666; font-size: 13px;">
                            <strong><?php esc_html_e('Active:', 'wp-fukami-lens-ai'); ?></strong> <?php echo esc_html($provider_label); ?> API
                        </span>
                    </div>
                    <div id="fukami-lens-token-usage"></div>
                    <a href="<?php echo esc_url($settings_url); ?>" class="fukami-lens-settings-link" target="_blank" title="<?php esc_attr_e('Settings', 'wp-fukami-lens-ai'); ?>" style="margin-left: 8px;">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </a>
                </div>
            </div>
            <?php
        }
    }
}

// Instantiate only once
if ( ! isset( $GLOBALS['fukami_lens_admin_instance'] ) ) {
    $GLOBALS['fukami_lens_admin_instance'] = new FUKAMI_LENS_Admin();
}
