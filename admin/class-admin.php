<?php
/**
 * Admin logic: menus, settings, dashboard widgets, metaboxes.
 *
 * @package NihongoProofreaderAI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'NPA_Admin' ) ) {
    /**
     * Class NPA_Admin
     * Handles admin-side hooks and UI for Nihongo Proofreader AI.
     */
    class NPA_Admin {
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
                'npa-admin',
                plugins_url( '../assets/css/admin.css', __FILE__ )
            );
            // Always enqueue admin.js for plugin admin pages
            wp_enqueue_script(
                'npa-admin',
                plugins_url( '../assets/admin.js', __FILE__ ),
                [ 'jquery' ],
                null,
                true
            );
            wp_localize_script( 'npa-admin', 'npa_ajax', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'npa_check_nonce' ),
                'ask_ai_nonce' => wp_create_nonce( 'npa_ask_ai_nonce' )
            ]);
        }

        /**
         * Add the grammar checker metabox to post editor.
         */
        public function add_metabox() {
            add_meta_box(
                'npa_grammar_checker',
                esc_html__( 'Nihongo Proofreader AI', 'wp-nihongo-proofreader-ai' ),
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
            $settings_url = admin_url('options-general.php?page=npa-settings');
            $ai_provider = get_option('npa_ai_provider', 'openai');
            $provider_label = $ai_provider === 'anthropic' ? 'Anthropic' : 'OpenAI';
            ?>
            <div class="npa-admin">
                <div id="npa-grammar-results"></div>
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div id="npa-check-btn-container">
                        <button type="button" class="button" id="npa-check-btn"><?php esc_html_e('Proofread', 'wp-nihongo-proofreader-ai'); ?></button>
                        <span style="margin-left: 12px; color: #666; font-size: 13px;">
                            <strong><?php esc_html_e('Active:', 'wp-nihongo-proofreader-ai'); ?></strong> <?php echo esc_html($provider_label); ?> API
                        </span>
                    </div>
                    <div id="npa-token-usage"></div>
                    <a href="<?php echo esc_url($settings_url); ?>" class="npa-settings-link" target="_blank" title="<?php esc_attr_e('Settings', 'wp-nihongo-proofreader-ai'); ?>" style="margin-left: 8px;">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </a>
                </div>
            </div>
            <?php
        }
    }
}

// Instantiate only once
if ( ! isset( $GLOBALS['npa_admin_instance'] ) ) {
    $GLOBALS['npa_admin_instance'] = new NPA_Admin();
}
