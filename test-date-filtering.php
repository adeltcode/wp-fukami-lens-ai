<?php
/**
 * Test script to verify date filtering functionality in the chunking service
 * 
 * This file can be accessed directly to test the date filtering capabilities.
 */

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    // Try multiple possible paths for wp-load.php
    $possible_paths = [
        dirname(__DIR__, 4) . '/wp-load.php',  // Standard WordPress structure
        dirname(__DIR__, 3) . '/wp-load.php',  // Alternative structure
        dirname(__DIR__, 5) . '/wp-load.php',  // Another alternative
        '/var/www/wp-load.php',               // Docker container path
        '/var/www/html/wp-load.php',          // Alternative Docker path
    ];
    
    $wp_loaded = false;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Could not find wp-load.php. Please ensure this script is run from within WordPress.');
    }
}

// Test the chunking service with date filtering
echo "<h1>Testing Date Filtering in Chunking Service</h1>";

try {
    $chunking_service = new FUKAMI_LENS_Chunking_Service();
    
    echo "<h2>Test 1: Default behavior (no date filter)</h2>";
    $html_contents = $chunking_service->get_posts_as_html(['numberposts' => 2]);
    echo "<p>Retrieved " . count($html_contents) . " posts (no date filter)</p>";
    
    echo "<h2>Test 2: Date range filtering</h2>";
    // Test with a date range (last 30 days)
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
    
    echo "<p>Testing date range: {$start_date} to {$end_date}</p>";
    
    $html_contents_filtered = $chunking_service->get_posts_as_html([
        'numberposts' => 5,
        'start_date' => $start_date,
        'end_date' => $end_date
    ]);
    
    echo "<p>Retrieved " . count($html_contents_filtered) . " posts with date filter</p>";
    
    echo "<h2>Test 3: Start date only</h2>";
    $html_contents_start_only = $chunking_service->get_posts_as_html([
        'numberposts' => 3,
        'start_date' => $start_date
    ]);
    
    echo "<p>Retrieved " . count($html_contents_start_only) . " posts with start date filter only</p>";
    
    echo "<h2>Test 4: End date only</h2>";
    $html_contents_end_only = $chunking_service->get_posts_as_html([
        'numberposts' => 3,
        'end_date' => $end_date
    ]);
    
    echo "<p>Retrieved " . count($html_contents_end_only) . " posts with end date filter only</p>";
    
    echo "<h2>Test 5: Advanced date_query</h2>";
    $html_contents_advanced = $chunking_service->get_posts_as_html([
        'numberposts' => 2,
        'date_query' => [
            'after' => '2024-01-01',
            'before' => '2024-12-31',
            'inclusive' => true
        ]
    ]);
    
    echo "<p>Retrieved " . count($html_contents_advanced) . " posts with advanced date_query</p>";
    
    echo "<h2>Test Results Summary</h2>";
    echo "<ul>";
    echo "<li>Default (no filter): " . count($html_contents) . " posts</li>";
    echo "<li>Date range filter: " . count($html_contents_filtered) . " posts</li>";
    echo "<li>Start date only: " . count($html_contents_start_only) . " posts</li>";
    echo "<li>End date only: " . count($html_contents_end_only) . " posts</li>";
    echo "<li>Advanced date_query: " . count($html_contents_advanced) . " posts</li>";
    echo "</ul>";
    
    echo "<h2>Date Filtering Test Complete</h2>";
    echo "<p>If you can see different post counts above, the date filtering is working correctly!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 