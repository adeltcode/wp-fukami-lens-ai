<?php
/**
 * Post chunking page for WP Fukami Lens AI
 *
 * @package FukamiLensAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the post chunking page.
 */
function fukami_lens_post_chunking_page() {
    $chunking_output = '';
    
    if (isset($_POST['fukami_lens_run_post_chunking'])) {
        // Use the chunking service
        $chunking_service = new FUKAMI_LENS_Chunking_Service();
        $chunking_output = $chunking_service->chunk_posts();
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Post Chunking', 'wp-fukami-lens-ai'); ?></h1>
        <p class="description"><?php esc_html_e('Retrieve and chunk WordPress posts using Python processing.', 'wp-fukami-lens-ai'); ?></p>
        
        <form method="post">
            <input type="hidden" name="fukami_lens_run_post_chunking" value="1">
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Chunk Latest Posts', 'wp-fukami-lens-ai'); ?>">
        </form>
        
        <?php if ($chunking_output): ?>
            <div style="margin-top:16px;">
                <h3><?php esc_html_e('Chunking Results:', 'wp-fukami-lens-ai'); ?></h3>
                <div style="background: #f9f9f9; padding: 16px; border: 1px solid #ddd; max-height: 600px; overflow-y: auto;">
                    <pre><?php echo esc_html($chunking_output); ?></pre>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
} 