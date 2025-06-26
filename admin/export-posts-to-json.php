<?php
// Export latest 10 published posts as JSON for Python conversion
// Usage: Run from browser or CLI (for local dev/testing)

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

// Query the last 10 published posts
$args = [
    'numberposts' => 10,
    'post_status' => 'publish',
    'orderby'     => 'date',
    'order'       => 'DESC',
];
$posts = get_posts($args);

$data = [];
foreach ($posts as $post) {
    $author = get_userdata($post->post_author);
    $data[] = [
        'title'   => ['rendered' => get_the_title($post)],
        'date'    => $post->post_date,
        'content' => ['rendered' => apply_filters('the_content', $post->post_content)],
        '_embedded' => [
            'author' => [
                ['name' => $author ? $author->display_name : 'Unknown']
            ]
        ]
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
