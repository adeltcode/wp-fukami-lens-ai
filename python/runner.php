<?php
/**
 * Python code runner logic for Nihongo Proofreader AI
 *
 * @package NihongoProofreaderAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if (!function_exists('npa_python_runner_page')) {
    function npa_python_runner_page() {
        $output = '';
        $error = '';
        $code = '';
        if (isset($_POST['npa_python_code'])) {
            $code = stripslashes($_POST['npa_python_code']);
            // Basic sanitization: disallow dangerous functions (for demo, not secure for production)
            if (stripos($code, 'import os') !== false || stripos($code, 'import sys') !== false || stripos($code, 'open(') !== false) {
                $error = esc_html__('Use of certain functions is not allowed for security reasons.', 'wp-nihongo-proofreader-ai');
            } else {
                $tmpfile = tempnam(sys_get_temp_dir(), 'npa_py_');
                file_put_contents($tmpfile, $code);

                // invoke python3 from its installed path, capture stderr
                $cmd    = '/usr/bin/python3 ' . escapeshellarg($tmpfile) . ' 2>&1';
                $output = shell_exec($cmd);

                unlink($tmpfile);
            }
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Run Python Code', 'wp-nihongo-proofreader-ai'); ?></h1>
            <form method="post">
                <textarea name="npa_python_code" rows="10" cols="100" placeholder="<?php esc_attr_e("print('Hello from Python!')", 'wp-nihongo-proofreader-ai'); ?>"><?php echo esc_textarea($code); ?></textarea><br>
                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Run Python Code', 'wp-nihongo-proofreader-ai'); ?>">
            </form>
            <hr>
            <form method="post">
                <input type="hidden" name="npa_run_wp_posts_to_markdown" value="1">
                <input type="submit" class="button" value="<?php esc_attr_e('Show Last 10 Posts as Markdown and chuncked', 'wp-nihongo-proofreader-ai'); ?>">
            </form>
            <?php
            if (isset($_POST['npa_run_wp_posts_to_markdown'])) {
                // --- Retrieve latest 10 posts and pass to Python ---
                $args = [
                    'numberposts' => 5,
                    'post_status' => 'publish',
                    'orderby'     => 'date',
                    'order'       => 'DESC',
                ];
                $posts = get_posts($args);
                $data = [];
                foreach ($posts as $post) {
                    $categories = get_the_category($post->ID);
                    $category_names = array_map(function($cat) { return $cat->name; }, $categories);
                    $tags = get_the_tags($post->ID);
                    $tag_names = $tags ? array_map(function($tag) { return $tag->name; }, $tags) : [];
                    $data[] = [
                        'ID'       => $post->ID,
                        'title'    => ['rendered' => get_the_title($post)],
                        'date'     => $post->post_date,
                        'content'  => ['rendered' => apply_filters('the_content', $post->post_content)],
                        'permalink'=> get_permalink($post->ID),
                        'categories' => $category_names,
                        'tags' => $tag_names,
                    ];
                }
                $python_script = __DIR__ . '/wp_posts_to_markdown.py';
                $max_input_tokens = intval(get_option('npa_rag_max_input_tokens', 8191));
                $rag_embeddings_model = get_option('npa_rag_embeddings_model', 'gpt-3.5-turbo');
                $all_md_output = '';
                foreach ($data as $post) {
                    $tmpfile = tempnam(sys_get_temp_dir(), 'npa_post_');
                    file_put_contents($tmpfile, json_encode([$post], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    // Debug: Output the contents of the temp file before calling Python
                    // echo '<div style="margin-top:16px;"><strong>Temp JSON file for Post ID ' . esc_html($post['ID']) . ':</strong><pre style="white-space:pre-wrap; background:#f8f8f8; border:1px solid #ccc; padding:8px; max-height:400px; overflow:auto;">' . esc_html(file_get_contents($tmpfile)) . '</pre></div>';
                    $cmd = 'NPA_RAG_MAX_INPUT_TOKENS=' . escapeshellarg($max_input_tokens) . ' NPA_RAG_EMBEDDINGS_MODEL=' . escapeshellarg($rag_embeddings_model) . ' ' . escapeshellcmd('/usr/bin/python3') . ' ' . escapeshellarg($python_script) . ' ' . escapeshellarg($tmpfile) . ' 2>&1';
                    $md_output = shell_exec($cmd);
                    unlink($tmpfile);
                    $all_md_output .= $md_output . "\n\n";
                }
                echo '<div style="margin-top:16px;"><strong>' . esc_html__('Markdown Output:', 'wp-nihongo-proofreader-ai') . '</strong><pre style="white-space:pre-wrap;">' . esc_html($all_md_output) . '</pre></div>';
            }
            ?>
            <?php if ($error): ?>
                <div style="color:red;"><strong><?php esc_html_e('Error:', 'wp-nihongo-proofreader-ai'); ?></strong> <?php echo esc_html($error); ?></div>
            <?php elseif ($output): ?>
                <div style="margin-top:16px;"><strong><?php esc_html_e('Output:', 'wp-nihongo-proofreader-ai'); ?></strong><pre><?php echo esc_html($output); ?></pre></div>
            <?php endif; ?>
        </div>
        <?php
    }
} 