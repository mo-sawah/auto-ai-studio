<?php
/**
 * Core Auto AI Studio Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoAIStudioCore {
    
    private static $instance = null;
    private $settings;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->settings = get_option('auto_ai_studio_settings', array());
        $this->init_hooks();
        $this->load_admin();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_auto_ai_studio_test_connection', array($this, 'test_ai_connection'));
        add_action('wp_ajax_auto_ai_studio_create_campaign', array($this, 'create_campaign'));
        add_action('wp_ajax_auto_ai_studio_get_stats', array($this, 'get_dashboard_stats'));
        add_action('auto_ai_studio_content_check', array($this, 'process_campaigns'));
    }
    
    public function admin_menu() {
        add_menu_page(
            'Auto AI Studio',
            'Auto AI Studio',
            'manage_options',
            'auto-ai-studio',
            array($this, 'dashboard_page'),
            'data:image/svg+xml;base64,' . base64_encode('<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="white"/></svg>'),
            26
        );
        
        // Automation Types
        add_submenu_page('auto-ai-studio', 'General Articles', 'General Articles', 'manage_options', 'auto-ai-studio-general', array($this, 'general_articles_page'));
        add_submenu_page('auto-ai-studio', 'Automated News', 'Automated News', 'manage_options', 'auto-ai-studio-news', array($this, 'automated_news_page'));
        add_submenu_page('auto-ai-studio', 'Auto Videos', 'Auto Videos', 'manage_options', 'auto-ai-studio-videos', array($this, 'auto_videos_page'));
        add_submenu_page('auto-ai-studio', 'Auto Podcast', 'Auto Podcast', 'manage_options', 'auto-ai-studio-podcast', array($this, 'auto_podcast_page'));
        
        // Management
        add_submenu_page('auto-ai-studio', 'Campaign Manager', 'Campaign Manager', 'manage_options', 'auto-ai-studio-campaigns', array($this, 'campaign_manager_page'));
        add_submenu_page('auto-ai-studio', 'Settings', 'Settings', 'manage_options', 'auto-ai-studio-settings', array($this, 'settings_page'));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'auto-ai-studio') === false) {
            return;
        }
        
        wp_enqueue_style('auto-ai-studio-admin', AUTO_AI_STUDIO_PLUGIN_URL . 'admin/css/admin-style.css', array(), AUTO_AI_STUDIO_VERSION);
        wp_enqueue_script('auto-ai-studio-admin', AUTO_AI_STUDIO_PLUGIN_URL . 'admin/js/admin-script.js', array('jquery'), AUTO_AI_STUDIO_VERSION, true);
        
        wp_localize_script('auto-ai-studio-admin', 'autoAIStudio', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('auto_ai_studio_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'auto-ai-studio'),
                'error' => __('An error occurred', 'auto-ai-studio'),
                'success' => __('Success!', 'auto-ai-studio')
            )
        ));
    }
    
    public function dashboard_page() {
        $campaigns = AutoAIStudioDatabase::get_campaigns();
        $stats = $this->get_stats();
        include AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/dashboard.php';
    }
    
    public function general_articles_page() {
        include AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/general-articles.php';
    }
    
    public function automated_news_page() {
        include AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/automated-news.php';
    }
    
    public function auto_videos_page() {
        include AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/auto-videos.php';
    }
    
    public function auto_podcast_page() {
        include AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/auto-podcast.php';
    }
    
    public function campaign_manager_page() {
        $campaigns = AutoAIStudioDatabase::get_campaigns();
        include AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/campaign-manager.php';
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings($_POST);
        }
        $settings = $this->settings;
        include AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/settings.php';
    }
    
    private function save_settings($post_data) {
        $settings = array(
            'ollama_host' => sanitize_text_field($post_data['ollama_host']),
            'model_name' => sanitize_text_field($post_data['model_name']),
            'python_path' => sanitize_text_field($post_data['python_path']),
            'content_humanization' => isset($post_data['content_humanization']),
            'auto_publish' => isset($post_data['auto_publish']),
            'include_images' => isset($post_data['include_images']),
            'writing_tone' => sanitize_text_field($post_data['writing_tone']),
            'openrouter_model' => sanitize_text_field($post_data['openrouter_model'])
        );
        
        update_option('auto_ai_studio_settings', $settings);
        $this->settings = $settings;
    }
    
    public function test_ai_connection() {
        check_ajax_referer('auto_ai_studio_nonce', 'nonce');
        
        $ai_connector = new AutoAIStudioAIConnector();
        $result = $ai_connector->test_connection();
        
        wp_send_json($result);
    }
    
    public function create_campaign() {
        check_ajax_referer('auto_ai_studio_nonce', 'nonce');
        
        $campaign_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'type' => sanitize_text_field($_POST['type']),
            'keywords' => sanitize_textarea_field($_POST['keywords']),
            'frequency' => sanitize_text_field($_POST['frequency']),
            'settings' => json_encode($_POST['settings']),
            'status' => 'active',
            'created_at' => current_time('mysql')
        );
        
        $campaign_id = AutoAIStudioDatabase::create_campaign($campaign_data);
        
        if ($campaign_id) {
            wp_send_json_success(array('campaign_id' => $campaign_id));
        } else {
            wp_send_json_error(array('message' => 'Failed to create campaign'));
        }
    }
    
    public function get_dashboard_stats() {
        wp_send_json_success($this->get_stats());
    }
    
    private function get_stats() {
        return array(
            'total_campaigns' => AutoAIStudioDatabase::get_campaign_count(),
            'active_campaigns' => AutoAIStudioDatabase::get_campaign_count('active'),
            'total_posts' => AutoAIStudioDatabase::get_generated_posts_count(),
            'posts_today' => AutoAIStudioDatabase::get_generated_posts_count('today'),
            'automation_types' => 4,
            'minimum_frequency' => '10min',
            'automated_status' => '24/7'
        );
    }
    
    public function process_campaigns() {
        $active_campaigns = AutoAIStudioDatabase::get_campaigns('active');
        
        foreach ($active_campaigns as $campaign) {
            if ($this->should_run_campaign($campaign)) {
                $this->run_campaign($campaign);
            }
        }
    }
    
    private function should_run_campaign($campaign) {
        $last_run = $campaign['last_run'];
        $frequency = json_decode($campaign['settings'], true)['frequency'] ?? 'hourly';
        
        if (!$last_run) {
            return true;
        }
        
        $last_run_time = strtotime($last_run);
        $current_time = time();
        
        switch ($frequency) {
            case 'every_15_minutes':
                return ($current_time - $last_run_time) >= 900; // 15 minutes
            case 'every_30_minutes':
                return ($current_time - $last_run_time) >= 1800; // 30 minutes
            case 'hourly':
                return ($current_time - $last_run_time) >= 3600; // 1 hour
            case 'daily':
                return ($current_time - $last_run_time) >= 86400; // 24 hours
            default:
                return false;
        }
    }
    
    private function run_campaign($campaign) {
        $content_generator = new AutoAIStudioContentGenerator();
        
        try {
            $result = $content_generator->generate_content($campaign);
            
            if ($result['success']) {
                // Update last run time
                AutoAIStudioDatabase::update_campaign_last_run($campaign['id']);
                
                // Log success
                AutoAIStudioDatabase::log_campaign_activity($campaign['id'], 'success', 'Content generated successfully');
            } else {
                // Log error
                AutoAIStudioDatabase::log_campaign_activity($campaign['id'], 'error', $result['message']);
            }
        } catch (Exception $e) {
            AutoAIStudioDatabase::log_campaign_activity($campaign['id'], 'error', $e->getMessage());
        }
    }
    
    private function load_admin() {
        // Create admin pages directory if it doesn't exist
        $pages_dir = AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/pages/';
        if (!file_exists($pages_dir)) {
            wp_mkdir_p($pages_dir);
        }
        
        // Create CSS and JS directories
        $css_dir = AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/css/';
        $js_dir = AUTO_AI_STUDIO_PLUGIN_DIR . 'admin/js/';
        
        if (!file_exists($css_dir)) {
            wp_mkdir_p($css_dir);
        }
        
        if (!file_exists($js_dir)) {
            wp_mkdir_p($js_dir);
        }
    }
}