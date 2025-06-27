<?php
/**
 * Plugin Name: WP Fukami Lens AI
 * Description: Japanese spelling and grammar checker for Classic Editor using OpenAI.
 * Version: 1.0
 * Author: Patrick James Garcia
 * License: GPL2
 * Text Domain: wp-fukami-lens-ai
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin directory constant
if ( ! defined( 'FUKAMI_LENS_PLUGIN_DIR' ) ) {
    define( 'FUKAMI_LENS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// -----------------------------------------------------------------------------
// Core Includes (functionality, helpers, API integrations)
// -----------------------------------------------------------------------------
require_once FUKAMI_LENS_PLUGIN_DIR . 'includes/class-core.php';
require_once FUKAMI_LENS_PLUGIN_DIR . 'includes/functions-ai-openai.php';
require_once FUKAMI_LENS_PLUGIN_DIR . 'includes/functions-ai-anthropic.php';
require_once FUKAMI_LENS_PLUGIN_DIR . 'includes/helpers.php';

// -----------------------------------------------------------------------------
// Admin Includes (settings page, dashboard widget, metaboxes, admin UI)
// -----------------------------------------------------------------------------
require_once FUKAMI_LENS_PLUGIN_DIR . 'admin/class-admin.php';
require_once FUKAMI_LENS_PLUGIN_DIR . 'admin/settings-page.php';
require_once FUKAMI_LENS_PLUGIN_DIR . 'admin/widget.php';

// -----------------------------------------------------------------------------
// (Optional) Load translations
// -----------------------------------------------------------------------------
// add_action( 'plugins_loaded', function() {
//     load_plugin_textdomain( 'wp-fukami-lens-ai', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
// } );

