<?php
/**
 * Plugin Name: Auto AI Studio
 * Plugin URI: https://sawahsolutions.com
 * Description: Advanced AI-powered content automation suite for WordPress. Generate articles, news, videos, and podcasts automatically using local AI models.
 * Version: 1.0.0
 * Author: Mohamed Sawah
 * Author URI: https://sawahsolutions.com
 * License: GPL v2 or later
 * Text Domain: auto-ai-studio
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AUTO_AI_STUDIO_VERSION', '1.0.0');
define('AUTO_AI_STUDIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AUTO_AI_STUDIO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AUTO_AI_STUDIO_PLUGIN_FILE', __FILE__);

/**
 * Main Auto AI Studio Class
 */
class AutoAIStudio {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    private function load_dependencies() {
        require_once AUTO_AI_STUDIO_PLUGIN_DIR . 'includes/class-database.php';
        require_once AUTO_AI_STUDIO_PLUGIN_DIR . 'includes/class-ai-connector.php';
        require_once AUTO_AI_STUDIO_PLUGIN_DIR . 'includes/class-content-generator.php';
        require_once AUTO_AI_STUDIO_PLUGIN_DIR . 'includes/class-auto-ai-studio.php';
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Initialize main class
        AutoAIStudioCore::get_instance();
    }
    
    public function activate() {
        // Create database tables
        AutoAIStudioDatabase::create_tables();
        
        // Schedule cron jobs
        if (!wp_next_scheduled('auto_ai_studio_content_check')) {
            wp_schedule_event(time(), 'hourly', 'auto_ai_studio_content_check');
        }
        
        // Set default options
        add_option('auto_ai_studio_settings', array(
            'ollama_host' => 'http://localhost:11434',
            'model_name' => 'llama3:8b',
            'python_path' => '/usr/bin/python3',
            'content_humanization' => true,
            'auto_publish' => false,
            'include_images' => true
        ));
        
        // Create upload directory for Python scripts
        $upload_dir = wp_upload_dir();
        $python_dir = $upload_dir['basedir'] . '/auto-ai-studio/python/';
        if (!file_exists($python_dir)) {
            wp_mkdir_p($python_dir);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('auto_ai_studio_content_check');
        wp_clear_scheduled_hook('auto_ai_studio_rss_refresh');
    }
}

// Initialize the plugin
AutoAIStudio::get_instance();