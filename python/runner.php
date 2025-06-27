<?php
/**
 * Python code runner logic for Nihongo Proofreader AI
 *
 * @package NihongoProofreaderAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if (!function_exists('fukami_lens_python_runner_page')) {
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
            <h1><?php esc_html_e('Run Python Code', 'wp-fukami-lens-ai'); ?></h1>
            <form method="post">
                <textarea name="fukami_lens_python_code" rows="10" cols="100" placeholder="<?php esc_attr_e("print('Hello from Python!')", 'wp-fukami-lens-ai'); ?>"><?php echo esc_textarea($code); ?></textarea><br>
                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Run Python Code', 'wp-fukami-lens-ai'); ?>">
            </form>
            <hr>
            <form method="post">
                <input type="hidden" name="fukami_lens_run_wp_posts_to_markdown" value="1">
                <input type="submit" class="button" value="<?php esc_attr_e('Show Last 10 Posts as Markdown and chuncked', 'wp-fukami-lens-ai'); ?>">
            </form>
            <?php
            if (isset($_POST['fukami_lens_run_wp_posts_to_markdown'])) {
                // --- Retrieve latest 10 posts and pass to Python ---
                $args = [
                    'numberposts' => 5,
                    'post_status' => 'publish',
                    'orderby'     => 'date',
                    'order'       => 'DESC',
                ];
                $posts = get_posts($args);
                $data = [];
                $debug_html_outputs = [];
                foreach ($posts as $post) {
                    $categories = get_the_category($post->ID);
                    $category_names = array_map(function($cat) { return $cat->name; }, $categories);
                    $tags = get_the_tags($post->ID);
                    $tag_names = $tags ? array_map(function($tag) { return $tag->name; }, $tags) : [];

                    // Build semantic HTML for the post
                    $html = '<article itemscope itemtype="http://schema.org/Article">';
                    $html .= '<header>';
                    $html .= '<h1 itemprop="headline">' . esc_html(get_the_title($post)) . '</h1>';
                    $html .= '<time itemprop="datePublished" datetime="' . esc_attr($post->post_date) . '">' . esc_html($post->post_date) . '</time>';
                    $html .= '</header>';
                    $html .= '<section class="content" itemprop="articleBody">' . apply_filters('the_content', $post->post_content) . '</section>';
                    if (!empty($category_names)) {
                        $html .= '<footer><ul class="categories">';
                        foreach ($category_names as $cat) {
                            $html .= '<li>' . esc_html($cat) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    if (!empty($tag_names)) {
                        $html .= '<ul class="tags">';
                        foreach ($tag_names as $tag) {
                            $html .= '<li>' . esc_html($tag) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    $html .= '<div class="permalink"><a href="' . esc_url(get_permalink($post->ID)) . '">Permalink</a></div>';
                    $html .= '</footer>';
                    $html .= '</article>';

                    $data[] = $html;
                    $debug_html_outputs[] = $html;
                }

                $python_script = __DIR__ . '/main.py';
                $max_input_tokens = intval(get_option('fukami_lens_rag_max_input_tokens', 8191));
                $rag_embeddings_model = get_option('fukami_lens_rag_embeddings_model', 'gpt-3.5-turbo');

                foreach ($data as $html_content) {

                    // Generate temporary HTML using post data
                    $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_post_');
                    file_put_contents($tmpfile, $html_content);

                    // Set OS environment variables (must be prepended to the Python command, not run separately)
                    // Run the Python script with environment variables for this process only
                    $cmd_python_code =
                        'FUKAMI_LENS_RAG_MAX_INPUT_TOKENS=' . escapeshellarg($max_input_tokens) . ' ' .
                        'FUKAMI_LENS_RAG_EMBEDDINGS_MODEL=' . escapeshellarg($rag_embeddings_model) . ' ' .
                        escapeshellcmd('/usr/bin/python3') . ' ' . escapeshellarg($python_script) . ' ' . escapeshellarg($tmpfile) . ' 2>&1';
                    $chunking_output = shell_exec($cmd_python_code);

                    echo '<pre>' .$chunking_output . '</pre><br>';

                }
            }
            ?>
            <?php if ($error): ?>
                <div style="color:red;"><strong><?php esc_html_e('Error:', 'wp-fukami-lens-ai'); ?></strong> <?php echo esc_html($error); ?></div>
            <?php elseif ($output): ?>
                <div style="margin-top:16px;"><strong><?php esc_html_e('Output:', 'wp-fukami-lens-ai'); ?></strong><pre><?php echo esc_html($output); ?></pre></div>
            <?php endif; ?>
        </div>
        <?php
    }
} 