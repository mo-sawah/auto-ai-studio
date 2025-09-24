<?php
/**
 * Auto AI Studio AI Connector Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AutoAIStudioAIConnector {
    
    private $settings;
    
    public function __construct() {
        $this->settings = get_option('auto_ai_studio_settings', array());
    }
    
    public function test_connection() {
        $ollama_host = $this->settings['ollama_host'] ?? 'http://localhost:11434';
        $model_name = $this->settings['model_name'] ?? 'llama3:8b';
        
        $test_prompt = 'Respond with "AI connection successful" if you can read this message.';
        
        $result = $this->call_ollama($test_prompt, $model_name);
        
        if (isset($result['success']) && $result['success']) {
            return array(
                'success' => true,
                'message' => 'AI connection successful! Model: ' . $model_name,
                'response' => substr($result['content'], 0, 100)
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . ($result['error'] ?? 'Unknown error')
            );
        }
    }
    
    public function call_ollama($prompt, $model = null, $system_message = '', $options = array()) {
        $ollama_host = $this->settings['ollama_host'] ?? 'http://localhost:11434';
        $model_name = $model ?? ($this->settings['model_name'] ?? 'llama3:8b');
        
        $url = rtrim($ollama_host, '/') . '/api/generate';
        
        $default_options = array(
            'temperature' => 0.3,
            'top_p' => 0.8,
            'max_tokens' => 4000,
            'num_predict' => 4000
        );
        
        $options = array_merge($default_options, $options);
        
        $data = array(
            'model' => $model_name,
            'prompt' => $prompt,
            'system' => $system_message,
            'stream' => false,
            'options' => $options
        );
        
        $response = wp_remote_post($url, array(
            'body' => json_encode($data),
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'timeout' => 300,
            'data_format' => 'body'
        ));
        
        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return array('error' => 'HTTP Error: ' . $response_code);
        }
        
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array('error' => 'JSON decode error: ' . json_last_error_msg());
        }
        
        if (isset($decoded['response'])) {
            return array(
                'success' => true,
                'content' => $decoded['response'],
                'model' => $model_name,
                'eval_count' => $decoded['eval_count'] ?? 0,
                'eval_duration' => $decoded['eval_duration'] ?? 0
            );
        }
        
        return array('error' => 'No response from AI model');
    }
    
    public function generate_article($topic, $type = 'general', $word_count = 800, $sources = array()) {
        $system_message = $this->get_system_message($type);
        $prompt = $this->build_content_prompt($topic, $type, $word_count, $sources);
        
        $options = array(
            'temperature' => 0.4,
            'top_p' => 0.9,
            'max_tokens' => $word_count * 2
        );
        
        return $this->call_ollama($prompt, null, $system_message, $options);
    }
    
    public function generate_title($content, $type = 'general') {
        $system_message = 'You are an expert headline writer. Create compelling, SEO-friendly titles.';
        
        $prompt = "Based on this content, generate 3 different compelling titles for a {$type} article. Make them engaging and click-worthy but not clickbait. Return only the titles, one per line:\n\n" . substr($content, 0, 500);
        
        $options = array(
            'temperature' => 0.6,
            'max_tokens' => 200
        );
        
        return $this->call_ollama($prompt, null, $system_message, $options);
    }
    
    public function generate_meta_description($content, $title) {
        $system_message = 'You are an SEO expert. Create compelling meta descriptions for articles.';
        
        $prompt = "Create an SEO-optimized meta description (150-160 characters) for this article:\n\nTitle: {$title}\n\nContent: " . substr($content, 0, 800) . "\n\nMeta description:";
        
        $options = array(
            'temperature' => 0.4,
            'max_tokens' => 100
        );
        
        return $this->call_ollama($prompt, null, $system_message, $options);
    }
    
    public function extract_keywords($content, $count = 10) {
        $system_message = 'You are an SEO keyword expert. Extract relevant keywords and phrases.';
        
        $prompt = "Extract {$count} SEO-relevant keywords from this content. Focus on terms people would search for. Return only keywords separated by commas:\n\n" . substr($content, 0, 1000);
        
        $options = array(
            'temperature' => 0.2,
            'max_tokens' => 200
        );
        
        return $this->call_ollama($prompt, null, $system_message, $options);
    }
    
    public function humanize_content($content) {
        $system_message = 'You are an expert content editor. Make AI-generated content sound more human and natural while preserving all information.';
        
        $prompt = "Rewrite this content to sound more human and natural. Keep all the information but make it flow better and sound less robotic. Maintain the same length and structure:\n\n" . $content;
        
        $options = array(
            'temperature' => 0.5,
            'top_p' => 0.9,
            'max_tokens' => str_word_count($content) * 2
        );
        
        return $this->call_ollama($prompt, null, $system_message, $options);
    }
    
    private function get_system_message($type) {
        $messages = array(
            'general' => 'You are a professional content writer. Create engaging, informative articles with proper structure, headings, and natural flow.',
            'news' => 'You are a news journalist. Write objective, factual news articles using inverted pyramid structure. Always cite sources when provided.',
            'trending' => 'You are a trending topics writer. Create engaging articles about current hot topics with a conversational tone that appeals to social media audiences.',
            'listicle' => 'You are a listicle writer. Create well-structured list articles with clear headings, engaging introductions, and actionable content.',
            'multipage' => 'You are a guide writer. Create comprehensive, multi-section guides with clear headings, step-by-step instructions, and practical examples.'
        );
        
        return $messages[$type] ?? $messages['general'];
    }
    
    private function build_content_prompt($topic, $type, $word_count, $sources = array()) {
        $prompt = "Write a {$word_count}-word {$type} article about: {$topic}\n\n";
        
        if (!empty($sources)) {
            $prompt .= "Use these sources for reference (cite them appropriately):\n";
            foreach ($sources as $source) {
                $prompt .= "- " . $source['title'] . " (" . $source['url'] . ")\n";
                $prompt .= "  Summary: " . substr($source['content'], 0, 200) . "...\n\n";
            }
        }
        
        $prompt .= "Requirements:\n";
        $prompt .= "- Use proper HTML headings (h2, h3) to structure the content\n";
        $prompt .= "- Write engaging, informative content\n";
        $prompt .= "- Include relevant keywords naturally\n";
        $prompt .= "- Make it SEO-friendly but readable\n";
        $prompt .= "- Add a compelling introduction and conclusion\n";
        
        if ($type === 'news') {
            $prompt .= "- Follow news writing standards with inverted pyramid structure\n";
            $prompt .= "- Include who, what, when, where, why in the first paragraph\n";
            $prompt .= "- Cite sources appropriately\n";
        }
        
        if ($type === 'listicle') {
            $prompt .= "- Structure as a numbered list with detailed explanations\n";
            $prompt .= "- Include practical tips and examples\n";
        }
        
        $prompt .= "\nWrite the complete article now:";
        
        return $prompt;
    }
    
    public function check_content_quality($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentence_count = substr_count($content, '.') + substr_count($content, '!') + substr_count($content, '?');
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        
        $score = 0;
        $issues = array();
        
        // Word count check
        if ($word_count >= 300) {
            $score += 20;
        } else {
            $issues[] = 'Content too short';
        }
        
        // Structure check
        if (preg_match('/<h[2-6].*?>.*?<\/h[2-6]>/i', $content)) {
            $score += 20;
        } else {
            $issues[] = 'Missing headings';
        }
        
        // Paragraph check
        $paragraphs = explode('</p>', $content);
        if (count($paragraphs) >= 3) {
            $score += 20;
        } else {
            $issues[] = 'Not enough paragraphs';
        }
        
        // Readability check
        if ($avg_sentence_length <= 20 && $avg_sentence_length >= 10) {
            $score += 20;
        } else {
            $issues[] = 'Sentence length issues';
        }
        
        // Content uniqueness (basic check)
        $unique_words = array_unique(str_word_count(strtolower(strip_tags($content)), 1));
        $unique_ratio = count($unique_words) / $word_count;
        
        if ($unique_ratio >= 0.6) {
            $score += 20;
        } else {
            $issues[] = 'Low content uniqueness';
        }
        
        return array(
            'score' => $score,
            'word_count' => $word_count,
            'readability' => $avg_sentence_length,
            'issues' => $issues
        );
    }
}