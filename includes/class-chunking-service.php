<?php
/**
 * Chunking Service for WP Fukami Lens AI
 *
 * @package FukamiLensAI
 */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'FUKAMI_LENS_Chunking_Service' ) ) {
    /**
     * Class FUKAMI_LENS_Chunking_Service
     * Handles post retrieval and chunking functionality.
     */
    class FUKAMI_LENS_Chunking_Service {
        
        /**
         * Get posts and convert them to HTML for chunking.
         *
         * @param array $args WordPress query arguments
         * @return array Array of HTML content strings
         */
        public function get_posts_as_html($args = []) {
            $default_args = [
                'numberposts' => 5,
                'post_status' => 'publish',
                'orderby'     => 'date',
                'order'       => 'DESC',
            ];
            
            $args = wp_parse_args($args, $default_args);
            $posts = get_posts($args);
            $html_contents = [];
            
            foreach ($posts as $post) {
                $html_contents[] = $this->convert_post_to_html($post);
            }
            
            return $html_contents;
        }
        
        /**
         * Convert a single post to semantic HTML.
         *
         * @param WP_Post $post WordPress post object
         * @return string HTML content
         */
        public function convert_post_to_html($post) {
            $categories = get_the_category($post->ID);
            $category_names = array_map(function($cat) { return $cat->name; }, $categories);
            $tags = get_the_tags($post->ID);
            $tag_names = $tags ? array_map(function($tag) { return $tag->name; }, $tags) : [];
            $permalink = get_permalink($post->ID);

            // Build semantic HTML for the post
            $title = esc_html(get_the_title($post));
            $date = esc_html($post->post_date);
            $canonical_tag = '<link rel="canonical" href="' . $permalink . '">';
            $meta_charset = '<meta charset="utf-8">';

            $html = '<!DOCTYPE html>';
            $html .= '<html lang="ja">';
            $html .= '<head>' . $meta_charset . $canonical_tag . '<title>' . $title . '</title></head>';
            $html .= '<body>';
            $html .= '<header>';
            $html .= '<h1 itemprop="headline">' . $title . '</h1>';
            $html .= '<time itemprop="datePublished" datetime="' . $date . '">' . $date . '</time>';
            $html .= '</header>';
            $html .= '<main>';
            $html .= '<article itemscope itemtype="http://schema.org/Article">';
            $html .= '<section class="content" itemprop="articleBody">' . apply_filters('the_content', $post->post_content) . '</section>';
            $html .= '</article>';
            $html .= '</main>';
            $html .= '<footer>';
            if (!empty($category_names)) {
                $html .= '<section class="categories"><h2>Categories</h2><ul>';
                foreach ($category_names as $cat) {
                    $html .= '<li>' . esc_html($cat) . '</li>';
                }
                $html .= '</ul></section>';
            }
            if (!empty($tag_names)) {
                $html .= '<section class="tags"><h2>Tags</h2><ul>';
                foreach ($tag_names as $tag) {
                    $html .= '<li>' . esc_html($tag) . '</li>';
                }
                $html .= '</ul></section>';
            }
            $html .= '<div class="permalink"><a href="' . $permalink . '">Permalink</a></div>';
            $html .= '</footer>';
            $html .= '</body>';
            $html .= '</html>';

            return $html;
        }
        
        /**
         * Chunk HTML content using Python.
         *
         * @param string $html_content HTML content to chunk
         * @return string Chunking output
         */
        public function chunk_html_content($html_content) {
            $python_script = plugin_dir_path(__FILE__) . '../python/main.py';
            $max_input_tokens = intval(get_option('fukami_lens_rag_max_input_tokens', 8191));
            $rag_embeddings_model = get_option('fukami_lens_rag_embeddings_model', 'gpt-3.5-turbo');

            // Generate temporary HTML file
            $tmpfile = tempnam(sys_get_temp_dir(), 'fukami_lens_post_');
            file_put_contents($tmpfile, $html_content);

            // Set OS environment variables and run the Python script
            $cmd_python_code =
                'FUKAMI_LENS_RAG_MAX_INPUT_TOKENS=' . escapeshellarg($max_input_tokens) . ' ' .
                'FUKAMI_LENS_RAG_EMBEDDINGS_MODEL=' . escapeshellarg($rag_embeddings_model) . ' ' .
                escapeshellcmd('/usr/bin/python3') . ' ' . escapeshellarg($python_script) . ' ' . escapeshellarg($tmpfile) . ' 2>&1';
            
            $output = shell_exec($cmd_python_code);
            
            // Clean up temporary file
            unlink($tmpfile);
            
            return $output;
        }
        
        /**
         * Get posts and chunk them.
         *
         * @param array $args WordPress query arguments
         * @return string Combined chunking output
         */
        public function chunk_posts($args = []) {
            $html_contents = $this->get_posts_as_html($args);
            $output = '';
            
            foreach ($html_contents as $html_content) {
                $output .= $this->chunk_html_content($html_content);
                $output .= '\n';
            }
            
            return $output;
        }
        
        /**
         * Get chunking results for AJAX requests.
         *
         * @param array $args WordPress query arguments
         * @return array Response array with success status and data
         */
        public function get_chunking_results($args = []) {
            try {
                $output = $this->chunk_posts($args);
                return [
                    'success' => true,
                    'data' => $output
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'data' => $e->getMessage()
                ];
            }
        }
    }
} 