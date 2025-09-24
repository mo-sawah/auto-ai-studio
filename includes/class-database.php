<?php
/**
 * Auto AI Studio Database Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoAIStudioDatabase {
    
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Campaigns table
        $campaigns_table = $wpdb->prefix . 'auto_ai_campaigns';
        
        $campaigns_sql = "CREATE TABLE $campaigns_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            type varchar(50) NOT NULL,
            keywords text DEFAULT NULL,
            rss_feeds text DEFAULT NULL,
            frequency varchar(50) DEFAULT 'hourly',
            settings longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            last_run datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Generated content table
        $content_table = $wpdb->prefix . 'auto_ai_generated_content';
        
        $content_sql = "CREATE TABLE $content_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            campaign_id mediumint(9) NOT NULL,
            post_id bigint(20) UNSIGNED DEFAULT NULL,
            title varchar(500) NOT NULL,
            content longtext NOT NULL,
            meta_description text DEFAULT NULL,
            keywords text DEFAULT NULL,
            sources text DEFAULT NULL,
            ai_model varchar(100) DEFAULT NULL,
            word_count int DEFAULT 0,
            status varchar(20) DEFAULT 'draft',
            humanization_score float DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            published_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";
        
        // Campaign activity log
        $activity_table = $wpdb->prefix . 'auto_ai_campaign_activity';
        
        $activity_sql = "CREATE TABLE $activity_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            campaign_id mediumint(9) NOT NULL,
            action varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text DEFAULT NULL,
            data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // RSS feeds table
        $rss_table = $wpdb->prefix . 'auto_ai_rss_feeds';
        
        $rss_sql = "CREATE TABLE $rss_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            url text NOT NULL,
            category varchar(100) DEFAULT NULL,
            language varchar(10) DEFAULT 'en',
            country varchar(10) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            last_checked datetime DEFAULT NULL,
            last_updated datetime DEFAULT NULL,
            total_items int DEFAULT 0,
            error_count int DEFAULT 0,
            last_error text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY is_active (is_active),
            KEY category (category)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($campaigns_sql);
        dbDelta($content_sql);
        dbDelta($activity_sql);
        dbDelta($rss_sql);
        
        // Insert default RSS feeds
        self::insert_default_feeds();
    }
    
    private static function insert_default_feeds() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_rss_feeds';
        
        $default_feeds = array(
            array('Google News - Technology', 'https://news.google.com/rss/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGRqTVhZU0FtVnVHZ0pWVXlnQVAB?hl=en-US&gl=US&ceid=US:en', 'technology'),
            array('Google News - Business', 'https://news.google.com/rss/topics/CAAqJggKIiBDQkFTRWdvSUwyMHZNRGx6TVdZU0FtVnVHZ0pWVXlnQVAB?hl=en-US&gl=US&ceid=US:en', 'business'),
            array('Google News - Health', 'https://news.google.com/rss/topics/CAAqJQgKIh9DQkFTRVFvSUwyMHZNR3QwTlRFU0FtVnVHZ0pWVXlnQVAB?hl=en-US&gl=US&ceid=US:en', 'health'),
            array('Reuters - Top News', 'https://feeds.reuters.com/reuters/topNews', 'general'),
            array('AP News - Top Stories', 'https://feeds.apnews.com/rss/apf-topnews', 'general'),
            array('BBC News - World', 'https://feeds.bbci.co.uk/news/world/rss.xml', 'world'),
            array('TechCrunch', 'https://techcrunch.com/feed/', 'technology'),
            array('Wired', 'https://www.wired.com/feed/rss', 'technology'),
        );
        
        foreach ($default_feeds as $feed) {
            $wpdb->insert(
                $table_name,
                array(
                    'name' => $feed[0],
                    'url' => $feed[1],
                    'category' => $feed[2],
                    'is_active' => 1,
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%d', '%s')
            );
        }
    }
    
    public static function get_campaigns($status = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaigns';
        
        if ($status) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC",
                $status
            ), ARRAY_A);
        } else {
            return $wpdb->get_results(
                "SELECT * FROM $table_name ORDER BY created_at DESC",
                ARRAY_A
            );
        }
    }
    
    public static function get_campaign($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaigns';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ), ARRAY_A);
    }
    
    public static function create_campaign($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaigns';
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public static function update_campaign($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaigns';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
    
    public static function delete_campaign($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaigns';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    public static function get_campaign_count($status = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaigns';
        
        if ($status) {
            return $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE status = %s",
                $status
            ));
        } else {
            return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        }
    }
    
    public static function update_campaign_last_run($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaigns';
        
        return $wpdb->update(
            $table_name,
            array('last_run' => current_time('mysql')),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    public static function get_generated_posts_count($period = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_generated_content';
        
        if ($period === 'today') {
            return $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE()"
            );
        } else {
            return $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        }
    }
    
    public static function save_generated_content($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_generated_content';
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public static function get_generated_content($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_generated_content';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $id
        ), ARRAY_A);
    }
    
    public static function log_campaign_activity($campaign_id, $status, $message, $data = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaign_activity';
        
        return $wpdb->insert(
            $table_name,
            array(
                'campaign_id' => $campaign_id,
                'action' => 'content_generation',
                'status' => $status,
                'message' => $message,
                'data' => $data ? json_encode($data) : null,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    public static function get_campaign_activity($campaign_id, $limit = 50) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_campaign_activity';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE campaign_id = %d ORDER BY created_at DESC LIMIT %d",
            $campaign_id,
            $limit
        ), ARRAY_A);
    }
    
    public static function get_rss_feeds($active_only = true) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_rss_feeds';
        
        if ($active_only) {
            return $wpdb->get_results(
                "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY name",
                ARRAY_A
            );
        } else {
            return $wpdb->get_results(
                "SELECT * FROM $table_name ORDER BY name",
                ARRAY_A
            );
        }
    }
    
    public static function add_rss_feed($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_rss_feeds';
        
        $result = $wpdb->insert($table_name, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public static function update_rss_feed($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'auto_ai_rss_feeds';
        
        return $wpdb->update(
            $table_name,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );
    }
}