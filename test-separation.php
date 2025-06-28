<?php
/**
 * Test script to verify separation of Python runner and post chunking functionality
 * 
 * This file can be accessed directly to test the chunking service.
 */

// Load WordPress if running standalone
if (!defined('ABSPATH')) {
    require_once dirname(__DIR__, 4) . '/wp-load.php';
}

// Test the chunking service
echo "<h1>Testing Chunking Service Separation</h1>";

try {
    $chunking_service = new FUKAMI_LENS_Chunking_Service();
    
    echo "<h2>Testing get_posts_as_html()</h2>";
    $html_contents = $chunking_service->get_posts_as_html(['numberposts' => 2]);
    echo "<p>Retrieved " . count($html_contents) . " posts as HTML</p>";
    
    if (!empty($html_contents)) {
        echo "<h3>First post HTML preview:</h3>";
        echo "<pre style='max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 8px; border: 1px solid #ddd;'>";
        echo htmlspecialchars(substr($html_contents[0], 0, 500)) . "...";
        echo "</pre>";
        
        echo "<h2>Testing chunk_html_content()</h2>";
        $chunking_output = $chunking_service->chunk_html_content($html_contents[0]);
        echo "<h3>Chunking output:</h3>";
        echo "<pre style='max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 8px; border: 1px solid #ddd;'>";
        echo htmlspecialchars($chunking_output);
        echo "</pre>";
    }
    
    echo "<h2>Testing get_chunking_results()</h2>";
    $result = $chunking_service->get_chunking_results(['numberposts' => 1]);
    echo "<p>Result success: " . ($result['success'] ? 'true' : 'false') . "</p>";
    if ($result['success']) {
        echo "<h3>Chunking results:</h3>";
        echo "<pre style='max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 8px; border: 1px solid #ddd;'>";
        echo htmlspecialchars($result['data']);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>Error: " . htmlspecialchars($result['data']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If you can see chunking results above, the separation is working correctly!</p>";
?> 