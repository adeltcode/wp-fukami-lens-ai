<?php
/**
 * Python code runner page for WP Fukami Lens AI
 *
 * @package FukamiLensAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render the Python code runner page.
 */
function fukami_lens_python_runner_page() {
    $output = '';
    $error = '';
    $code = '';
    
    if (isset($_POST['fukami_lens_python_code'])) {
        $code = stripslashes($_POST['fukami_lens_python_code']);
        // Basic sanitization: disallow dangerous functions (for demo, not secure for production)
        if (stripos($code, 'import os') !== false || stripos($code, 'import sys') !== false || stripos($code, 'open(') !== false) {
            $error = esc_html__('Use of certain functions is not allowed for security reasons.', 'wp-fukami-lens-ai');
        } else {
            $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_py_');
            file_put_contents($tmpfile, $code);

            // invoke python3 from its installed path, capture stderr
            $cmd    = '/usr/bin/python3 ' . escapeshellarg($tmpfile) . ' 2>&1';
            $output = shell_exec($cmd);

            unlink($tmpfile);
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Python Code Runner', 'wp-fukami-lens-ai'); ?></h1>
        <p class="description"><?php esc_html_e('Execute Python code for testing and development purposes.', 'wp-fukami-lens-ai'); ?></p>
        
        <form method="post">
            <textarea name="fukami_lens_python_code" rows="10" cols="100" placeholder="<?php esc_attr_e("print('Hello from Python!')", 'wp-fukami-lens-ai'); ?>"><?php echo esc_textarea($code); ?></textarea><br>
            <input type="submit" class="button button-primary" value="<?php esc_attr_e('Run Python Code', 'wp-fukami-lens-ai'); ?>">
        </form>
        
        <?php if ($error): ?>
            <div style="color:red; margin-top: 16px;"><strong><?php esc_html_e('Error:', 'wp-fukami-lens-ai'); ?></strong> <?php echo esc_html($error); ?></div>
        <?php elseif ($output): ?>
            <div style="margin-top:16px;"><strong><?php esc_html_e('Output:', 'wp-fukami-lens-ai'); ?></strong><pre><?php echo esc_html($output); ?></pre></div>
        <?php endif; ?>
    </div>
    <?php
} 