<?php
/**
 * Utility/helper functions for Nihongo Proofreader AI
 *
 * @package NihongoProofreaderAI
 */

/**
 * Check for invalid anchor tags in content.
 *
 * @param string $content
 * @return array
 */
function fukami_lens_check_invalid_anchors($content) {
    $invalids = [];
    // Match <a ...> tags
    preg_match_all('/<a\s+[^>]*>/i', $content, $matches);
    foreach ($matches[0] as $tag) {
        // Check for href attribute with mismatched or missing quotes
        if (!preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $tag)) {
            $invalids[] = $tag;
        }
    }
    return $invalids;
}

/**
 * Check for broken links in content.
 *
 * @param string $content
 * @return array
 */
function fukami_lens_check_broken_links($content) {
    preg_match_all('/<a\s[^>]*href=["\']([^"\']+)["\']/i', $content, $matches);
    $urls = $matches[1] ?? [];
    $broken = [];
    foreach ($urls as $url) {
        $head = wp_remote_head($url, ['timeout' => 5]);
        if (is_wp_error($head) || wp_remote_retrieve_response_code($head) != 200) {
            $broken[] = esc_url($url);
        }
    }
    return $broken;
}
