<?php
/**
 * Auto AI Studio Content Generator Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoAIStudioContentGenerator {
    
    private $ai_connector;
    private $settings;
    
    public function __construct() {
        $this->ai_connector = new AutoAIStudioAIConnector();
        $this->settings = get_option('auto_ai_studio_settings', array());
    }
    
    public function generate_content($campaign) {
        $settings = json_decode($campaign['settings'], true);
        $type = $campaign['type'];
        
        try {
            switch ($type) {
                case 'general_articles':
                    return $this->generate_general_article($campaign, $settings);
                case 'automated_news':
                    return $this->generate_news_article($campaign, $settings);
                case 'auto_videos':
                    return $this->generate_video_content($campaign, $settings);
                case 'auto_podcast':
                    return $this->generate_podcast_content($campaign, $settings);
                default:
                    return array('success' => false, 'message' => 'Unknown campaign type');
            }
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
    
    private function generate_general_article($campaign, $settings) {
        $keywords = explode(',', $campaign['keywords']);
        $main_keyword = trim($keywords[0]);
        
        if (empty($main_keyword)) {
            return array('success' => false, 'message' => 'No keywords provided');
        }
        
        // Get research data
        $research_data = $this->research_topic($main_keyword, $settings);
        
        // Generate article content
        $article_type = $settings['article_type'] ?? 'standard';
        $word_count = $settings['word_count'] ?? 800;
        
        $content_result = $this->ai_connector->generate_article(
            $main_keyword,
            $article_type,
            $word_count,
            $research_data['sources'] ?? array()
        );
        
        if (!$content_result['success']) {
            return array('success' => false, 'message' => 'Failed to generate content: ' . $content_result['error']);
        }
        
        $content = $content_result['content'];
        
        // Generate title options
        $title_result = $this->ai_connector->generate_title($content, $article_type);
        $titles = $title_result['success'] ? explode("\n", trim($title_result['content'])) : array($main_keyword . ' - Complete Guide');
        $selected_title = trim($titles[0]);
        
        // Generate meta description
        $meta_result = $this->ai_connector->generate_meta_description($content, $selected_title);
        $meta_description = $meta_result['success'] ? trim($meta_result['content']) : '';
        
        // Extract keywords
        $keywords_result = $this->ai_connector->extract_keywords($content);
        $extracted_keywords = $keywords_result['success'] ? trim($keywords_result['content']) : '';
        
        // Humanize content if enabled
        if ($settings['enable_humanization'] ?? false) {
            $humanized_result = $this->ai_connector->humanize_content($content);
            if ($humanized_result['success']) {
                $content = $humanized_result['content'];
            }
        }
        
        // Check content quality
        $quality_check = $this->ai_connector->check_content_quality($content);
        
        // Save generated content
        $content_data = array(
            'campaign_id' => $campaign['id'],
            'title' => $selected_title,
            'content' => $content,
            'meta_description' => $meta_description,
            'keywords' => $extracted_keywords,
            'sources' => json_encode($research_data['sources'] ?? array()),
            'ai_model' => $this->settings['model_name'] ?? 'llama3:8b',
            'word_count' => str_word_count(strip_tags($content)),
            'status' => 'draft',
            'humanization_score' => $quality_check['score'] ?? 0,
            'created_at' => current_time('mysql')
        );
        
        $content_id = AutoAIStudioDatabase::save_generated_content($content_data);
        
        if (!$content_id) {
            return array('success' => false, 'message' => 'Failed to save content to database');
        }
        
        // Publish if auto-publish is enabled
        if ($settings['auto_publish'] ?? false) {
            $post_result = $this->publish_content($content_id, $settings);
            
            if ($post_result['success']) {
                return array(
                    'success' => true, 
                    'message' => 'Article generated and published successfully',
                    'content_id' => $content_id,
                    'post_id' => $post_result['post_id']
                );
            }
        }
        
        return array(
            'success' => true, 
            'message' => 'Article generated successfully (saved as draft)',
            'content_id' => $content_id
        );
    }
    
    private function generate_news_article($campaign, $settings) {
        $keywords = explode(',', $campaign['keywords']);
        
        // Get latest news from RSS feeds or search
        $news_sources = $this->get_news_sources($keywords, $settings);
        
        if (empty($news_sources)) {
            return array('success' => false, 'message' => 'No news sources found for the given keywords');
        }
        
        // Select most relevant news item
        $selected_source = $news_sources[0];
        
        // Generate news article
        $content_result = $this->ai_connector->generate_article(
            $selected_source['title'],
            'news',
            $settings['word_count'] ?? 600,
            array($selected_source)
        );
        
        if (!$content_result['success']) {
            return array('success' => false, 'message' => 'Failed to generate news content');
        }
        
        $content = $content_result['content'];
        $title = $selected_source['title'];
        
        // Process and save content similar to general articles
        return $this->process_and_save_content($campaign, $content, $title, $settings, array($selected_source));
    }
    
    private function generate_video_content($campaign, $settings) {
        // Search for relevant videos (YouTube API integration would go here)
        $keywords = explode(',', $campaign['keywords']);
        $main_keyword = trim($keywords[0]);
        
        // For now, generate article about video topic
        $video_prompt = "Create an article about {$main_keyword} that would work well as a video script or companion article to a video.";
        
        $content_result = $this->ai_connector->generate_article($video_prompt, 'general', $settings['word_count'] ?? 1000);
        
        if (!$content_result['success']) {
            return array('success' => false, 'message' => 'Failed to generate video content');
        }
        
        return $this->process_and_save_content($campaign, $content_result['content'], $main_keyword . ' - Video Guide', $settings);
    }
    
    private function generate_podcast_content($campaign, $settings) {
        $keywords = explode(',', $campaign['keywords']);
        $main_keyword = trim($keywords[0]);
        
        // Generate podcast-style content
        $podcast_prompt = "Create a detailed article about {$main_keyword} written in a conversational podcast style with interview questions and detailed explanations.";
        
        $content_result = $this->ai_connector->generate_article($podcast_prompt, 'general', $settings['word_count'] ?? 1200);
        
        if (!$content_result['success']) {
            return array('success' => false, 'message' => 'Failed to generate podcast content');
        }
        
        return $this->process_and_save_content($campaign, $content_result['content'], $main_keyword . ' - Podcast Episode', $settings);
    }
    
    private function research_topic($topic, $settings) {
        // Call Python research script if available
        $python_path = $this->settings['python_path'] ?? '/usr/bin/python3';
        $script_path = AUTO_AI_STUDIO_PLUGIN_DIR . 'python/content_researcher.py';
        
        if (file_exists($script_path)) {
            $command = escapeshellcmd($python_path) . ' ' . escapeshellarg($script_path) . ' ' . escapeshellarg($topic);
            $output = shell_exec($command . ' 2>&1');
            
            if ($output) {
                $research_data = json_decode($output, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $research_data;
                }
            }
        }
        
        // Fallback: basic web search using WordPress HTTP API
        return $this->basic_web_search($topic);
    }
    
    private function basic_web_search($topic) {
        // Simple Google search fallback
        $search_url = 'https://www.googleapis.com/customsearch/v1';
        // Note: This requires Google Custom Search API key (free tier available)
        
        return array(
            'sources' => array(),
            'summary' => "Research topic: {$topic}"
        );
    }
    
    private function get_news_sources($keywords, $settings) {
        // Get RSS feeds from database
        $rss_feeds = AutoAIStudioDatabase::get_rss_feeds(true);
        $sources = array();
        
        foreach ($rss_feeds as $feed) {
            $rss_content = $this->fetch_rss_content($feed['url']);
            
            if ($rss_content && !empty($rss_content['items'])) {
                foreach ($rss_content['items'] as $item) {
                    // Check if item matches keywords
                    if ($this->matches_keywords($item['title'] . ' ' . $item['description'], $keywords)) {
                        $sources[] = array(
                            'title' => $item['title'],
                            'url' => $item['link'],
                            'content' => $item['description'],
                            'published' => $item['pubDate'] ?? '',
                            'source' => $feed['name']
                        );
                    }
                }
            }
        }
        
        // Sort by relevance/date
        usort($sources, function($a, $b) {
            return strtotime($b['published']) - strtotime($a['published']);
        });
        
        return array_slice($sources, 0, 5); // Return top 5 most recent matches
    }
    
    private function fetch_rss_content($url) {
        $response = wp_remote_get($url, array('timeout' => 30));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        // Parse RSS/XML
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            return false;
        }
        
        $items = array();
        
        // Handle different RSS formats
        if (isset($xml->channel->item)) {
            foreach ($xml->channel->item as $item) {
                $items[] = array(
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'description' => (string) $item->description,
                    'pubDate' => (string) $item->pubDate
                );
            }
        } elseif (isset($xml->entry)) {
            // Atom format
            foreach ($xml->entry as $entry) {
                $items[] = array(
                    'title' => (string) $entry->title,
                    'link' => (string) $entry->link['href'],
                    'description' => (string) $entry->summary,
                    'pubDate' => (string) $entry->published
                );
            }
        }
        
        return array('items' => $items);
    }
    
    private function matches_keywords($text, $keywords) {
        $text_lower = strtolower($text);
        
        foreach ($keywords as $keyword) {
            $keyword = trim(strtolower($keyword));
            if (strpos($text_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function process_and_save_content($campaign, $content, $title, $settings, $sources = array()) {
        // Generate meta description
        $meta_result = $this->ai_connector->generate_meta_description($content, $title);
        $meta_description = $meta_result['success'] ? trim($meta_result['content']) : '';
        
        // Extract keywords
        $keywords_result = $this->ai_connector->extract_keywords($content);
        $extracted_keywords = $keywords_result['success'] ? trim($keywords_result['content']) : '';
        
        // Save content
        $content_data = array(
            'campaign_id' => $campaign['id'],
            'title' => $title,
            'content' => $content,
            'meta_description' => $meta_description,
            'keywords' => $extracted_keywords,
            'sources' => json_encode($sources),
            'ai_model' => $this->settings['model_name'] ?? 'llama3:8b',
            'word_count' => str_word_count(strip_tags($content)),
            'status' => 'draft',
            'created_at' => current_time('mysql')
        );
        
        $content_id = AutoAIStudioDatabase::save_generated_content($content_data);
        
        if (!$content_id) {
            return array('success' => false, 'message' => 'Failed to save content');
        }
        
        // Auto-publish if enabled
        if ($settings['auto_publish'] ?? false) {
            $post_result = $this->publish_content($content_id, $settings);
            
            if ($post_result['success']) {
                return array('success' => true, 'message' => 'Content generated and published', 'post_id' => $post_result['post_id']);
            }
        }
        
        return array('success' => true, 'message' => 'Content generated successfully', 'content_id' => $content_id);
    }
    
    private function publish_content($content_id, $settings) {
        $content_data = AutoAIStudioDatabase::get_generated_content($content_id);
        
        if (!$content_data) {
            return array('success' => false, 'message' => 'Content not found');
        }
        
        // Create WordPress post
        $post_data = array(
            'post_title' => $content_data['title'],
            'post_content' => $content_data['content'],
            'post_status' => $settings['content_mode'] === 'publish' ? 'publish' : 'draft',
            'post_author' => $settings['author_id'] ?? 1,
            'post_category' => $settings['categories'] ?? array(),
            'meta_input' => array(
                'auto_ai_studio_generated' => true,
                'auto_ai_studio_content_id' => $content_id,
                'auto_ai_studio_campaign_id' => $content_data['campaign_id']
            )
        );
        
        // Add meta description if available
        if (!empty($content_data['meta_description'])) {
            $post_data['meta_input']['_yoast_wpseo_metadesc'] = $content_data['meta_description'];
        }
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array('success' => false, 'message' => $post_id->get_error_message());
        }
        
        // Update content record with post ID
        AutoAIStudioDatabase::update_generated_content($content_id, array(
            'post_id' => $post_id,
            'status' => 'published',
            'published_at' => current_time('mysql')
        ));
        
        return array('success' => true, 'post_id' => $post_id);
    }
}