<?php
/**
 * Plugin Name:     Sync Github Blog
 * Plugin URI:      
 * Description:     Import blog form github MD file's.
 * Author:          Dilip Bheda
 * Author URI:      
 * Text Domain:     github-blog
 * Domain Path:     /languages
 * Version:         1.0
 *
 * @package         GH_Blog
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use GH_Blog\Admin\Admin;

/**
 * Plugin textdomain.
 */
function gh_blog_textdomain() {
	load_plugin_textdomain( 'github-blog', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'gh_blog_textdomain' );

/**
 * Plugin activation.
 */
function gh_blog_activation() {
	// Activation code here.
}
register_activation_hook( __FILE__, 'gh_blog_activation' );

/**
 * Plugin deactivation.
 */
function gh_blog_deactivation() {
	// Deactivation code here.
}
register_deactivation_hook( __FILE__, 'gh_blog_deactivation' );

/**
 * Initialization class.
 */
function gh_blog_init() {
	new Admin();
}
add_action( 'plugins_loaded', 'gh_blog_init' );
