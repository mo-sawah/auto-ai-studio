<?php
/**
 * Auto AI Studio Automated News Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="auto-ai-studio-wrap">
    <div class="auto-ai-studio-container">
        
        <!-- Sidebar (reusing same as dashboard) -->
        <div class="auto-ai-studio-sidebar">
            <h2>
                Auto AI Studio
                <div class="subtitle">Content Creation Suite</div>
            </h2>
            
            <div class="sidebar-menu-group">
                <h3>Automation Types</h3>
                <ul>
                    <li><a href="?page=auto-ai-studio-general"><span class="icon">📝</span> General Articles</a></li>
                    <li><a href="?page=auto-ai-studio-news" class="current"><span class="icon">📰</span> Automated News</a></li>
                    <li><a href="?page=auto-ai-studio-videos"><span class="icon">🎬</span> Auto Videos</a></li>
                    <li><a href="?page=auto-ai-studio-podcast"><span class="icon">🎙️</span> Auto Podcast</a></li>
                </ul>
            </div>
            
            <div class="sidebar-menu-group">
                <h3>Management</h3>
                <ul>
                    <li><a href="?page=auto-ai-studio-campaigns"><span class="icon">📊</span> Campaign Manager</a></li>
                    <li><a href="?page=auto-ai-studio-settings"><span class="icon">⚙️</span> Settings</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="auto-ai-studio-main">
            <div class="auto-ai-studio-header">
                <h1>
                    <a href="?page=auto-ai-studio" style="text-decoration: none; color: inherit; margin-right: 16px;">←</a>
                    Automated News Generation
                </h1>
            </div>
            
            <div class="auto-ai-studio-content">
                <p style="font-size: 14px; color: #5f6368; margin-bottom: 32px;">
                    Generate articles automatically from latest news sources and RSS feeds
                </p>
                
                <!-- News Source Selection -->
                <div class="campaign-types">
                    <div class="campaign-type selected" data-type="news-search" data-subtype="google">
                        <div style="width: 32px; height: 32px; background: #4285f4; border-radius: 8px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">G</div>
                        <h4>News Search</h4>
                        <p>Search Google News and generate articles</p>
                    </div>
                    
                    <div class="campaign-type" data-type="google-news" data-subtype="api">
                        <div style="width: 32px; height: 32px; background: #34a853; border-radius: 8px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">📰</div>
                        <h4>Google News</h4>
                        <p>Create articles from news APIs</p>
                    </div>
                    
                    <div class="campaign-type" data-type="twitter-news" data-subtype="social">
                        <div style="width: 32px; height: 32px; background: #1da1f2; border-radius: 8px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">X</div>
                        <h4>Twitter/X News</h4>
                        <p>Generate articles from Twitter sources</p>
                    </div>
                    
                    <div class="campaign-type" data-type="rss-feeds" data-subtype="rss">
                        <div style="width: 32px; height: 32px; background: #ff6600; border-radius: 8px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">📡</div>
                        <h4>RSS Feeds</h4>
                        <p>Generate articles from RSS feeds</p>
                    </div>
                    
                    <div class="campaign-type" data-type="live-news" data-subtype="live">
                        <div style="width: 32px; height: 32px; background: #9c27b0; border-radius: 8px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">🔴</div>
                        <h4>Live News</h4>
                        <p>AI-categorized latest news</p>
                    </div>
                </div>
                
                <!-- Configuration Form -->
                <form id="news-campaign-form" style="background: white; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    
                    <!-- Campaign Name -->
                    <div class="form-group">
                        <label for="campaign-name">Campaign Name</label>
                        <input type="text" id="campaign-name" placeholder="e.g., Daily Tech News" required>
                    </div>
                    
                    <!-- Search Configuration -->
                    <div class="google-news-config">
                        <h3 style="margin-bottom: 16px; color: #202124;">Google News Search Configuration</h3>
                        <p style="color: #5f6368; margin-bottom: 24px; font-size: 14px;">
                            Search Google News and automatically generate articles from trending news sources.
                        </p>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="search-keywords">Search Keywords</label>
                                <input type="text" id="search-keywords" placeholder="e.g., artificial intelligence, climate change">
                                <small style="color: #5f6368;">Separate multiple keywords with commas</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="article-language">Article Language</label>
                                <select id="article-language">
                                    <option value="en">English</option>
                                    <option value="es">Spanish</option>
                                    <option value="fr">French</option>
                                    <option value="de">German</option>
                                    <option value="it">Italian</option>
                                    <option value="pt">Portuguese</option>
                                    <option value="ar">Arabic</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="source-languages">Source Languages</label>
                                <select id="source-languages">
                                    <option value="all">All languages</option>
                                    <option value="en">English only</option>
                                    <option value="multi">Multiple languages</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="countries">Countries</label>
                                <select id="countries">
                                    <option value="all">All countries</option>
                                    <option value="US">United States</option>
                                    <option value="GB">United Kingdom</option>
                                    <option value="CA">Canada</option>
                                    <option value="AU">Australia</option>
                                    <option value="DE">Germany</option>
                                    <option value="FR">France</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Schedule & Frequency -->
                    <div style="margin-top: 32px;">
                        <h3 style="margin-bottom: 16px; color: #202124;">Schedule & Frequency</h3>
                        <p style="color: #5f6368; margin-bottom: 24px; font-size: 14px;">
                            Set how often the campaign should run. Note: For specific time/day scheduling to work, your server's cron job system must be configured to interpret these settings.
                        </p>
                        
                        <div class="frequency-options">
                            <div class="frequency-option" data-frequency="every_15_minutes">Every 15 mins</div>
                            <div class="frequency-option" data-frequency="every_30_minutes">Every 30 mins</div>
                            <div class="frequency-option selected" data-frequency="hourly">Every hour</div>
                            <div class="frequency-option" data-frequency="daily">Daily</div>
                        </div>
                        
                        <div class="form-grid" style="margin-top: 16px;">
                            <div class="form-group">
                                <label for="frequency-number">Every</label>
                                <input type="number" id="frequency-number" value="1" min="1" style="width: 80px; display: inline-block; margin-right: 8px;">
                                <select id="frequency-unit" style="width: auto; display: inline-block;">
                                    <option value="hours">Hours</option>
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Publishing Stats -->
                    <div class="publishing-stats" style="margin: 32px 0;">
                        <h3>Publishing Frequency & Cost Analysis <span style="background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500;">ACTIVE SCHEDULE</span></h3>
                        
                        <div class="stats-row">
                            <div class="stat-box stat-per-day">
                                <div class="number">24</div>
                                <div class="label">Per Day</div>
                            </div>
                            <div class="stat-box stat-per-week">
                                <div class="number">168</div>
                                <div class="label">Per Week</div>
                            </div>
                            <div class="stat-box stat-per-month">
                                <div class="number">730</div>
                                <div class="label">Per Month</div>
                            </div>
                            <div class="stat-box stat-cost">
                                <div class="number">$0.038</div>
                                <div class="label">Per Article</div>
                            </div>
                        </div>
                        
                        <div class="cost-breakdown">
                            <div class="cost-item daily-cost">
                                <div class="cost-label">Daily Cost:</div>
                                <div class="cost-amount">$0.90</div>
                            </div>
                            <div class="cost-item monthly-cost">
                                <div class="cost-label">Monthly Cost:</div>
                                <div class="cost-amount">$27.38</div>
                            </div>
                        </div>
                        
                        <p style="margin-top: 16px; font-size: 12px; opacity: 0.8; text-align: center;">
                            * Based on 5 web search results
                        </p>
                    </div>
                    
                    <!-- Publishing Settings -->
                    <div style="margin-top: 32px;">
                        <h3 style="margin-bottom: 24px; color: #202124;">Publishing Settings</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="content-mode">Content Mode</label>
                                <select id="content-mode">
                                    <option value="draft">Save as Draft</option>
                                    <option value="publish">Publish Immediately</option>
                                    <option value="schedule">Schedule Publication</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="author">Author</label>
                                <select id="author">
                                    <option value="1">News Room</option>
                                    <?php
                                    $users = get_users(array('role' => 'author'));
                                    foreach ($users as $user) {
                                        echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="categories">Categories</label>
                                <select id="categories" multiple>
                                    <?php
                                    $categories = get_categories();
                                    foreach ($categories as $category) {
                                        echo '<option value="' . $category->term_id . '">' . esc_html($category->name) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campaign Options -->
                    <div style="margin-top: 32px;">
                        <h3 style="margin-bottom: 24px; color: #202124;">Campaign Options</h3>
                        
                        <!-- Content Humanization -->
                        <div style="margin-bottom: 24px;">
                            <h4 style="margin-bottom: 12px; color: #202124; font-size: 16px;">Content Humanization</h4>
                            <p style="color: #5f6368; margin-bottom: 16px; font-size: 14px;">
                                Automatically humanize generated content to bypass AI detection before publishing.
                            </p>
                            
                            <label style="display: flex; align-items: center; margin-bottom: 16px;">
                                <div class="toggle-switch" style="margin-right: 12px;">
                                    <input type="checkbox" id="enable-humanization" data-setting="humanization" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span>Enable content humanization</span>
                                <small style="color: #5f6368; margin-left: 8px;">Automatically process content through humanization before publishing</small>
                            </label>
                            
                            <div class="humanization-options">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="humanization-provider">Humanization Provider</label>
                                        <select id="humanization-provider">
                                            <option value="stealthgpt">StealthGPT (Recommended)</option>
                                            <option value="local">Local Processing</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="humanization-mode">Humanization Mode</label>
                                        <select id="humanization-mode">
                                            <option value="balanced">Standard (Balanced)</option>
                                            <option value="aggressive">Aggressive</option>
                                            <option value="conservative">Conservative</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Writing Configuration -->
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="writing-tone">Writing Tone</label>
                                <select id="writing-tone">
                                    <option value="conversational">Conversational & Natural</option>
                                    <option value="professional">Professional</option>
                                    <option value="casual">Casual</option>
                                    <option value="formal">Formal</option>
                                    <option value="journalistic">Journalistic</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="openrouter-model">OpenRouter Model</label>
                                <select id="openrouter-model">
                                    <option value="claude-3.5-sonnet">Claude 3.5 Sonnet (Latest)</option>
                                    <option value="gpt-4-turbo">GPT-4 Turbo</option>
                                    <option value="llama-3-70b">Llama 3 70B</option>
                                    <option value="local">Use Local Model</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Additional Options -->
                        <div style="margin-top: 24px;">
                            <label style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div class="toggle-switch" style="margin-right: 12px;">
                                    <input type="checkbox" id="business-mode" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span>Business mode (StealthGPT)</span>
                                <small style="color: #5f6368; margin-left: 8px;">Higher quality but 3x cost for StealthGPT</small>
                            </label>
                            
                            <label style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div class="toggle-switch" style="margin-right: 12px;">
                                    <input type="checkbox" id="preserve-formatting" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span>Preserve formatting</span>
                                <small style="color: #5f6368; margin-left: 8px;">Maintain HTML structure and links</small>
                            </label>
                            
                            <label style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div class="toggle-switch" style="margin-right: 12px;">
                                    <input type="checkbox" id="retry-detection">
                                    <span class="toggle-slider"></span>
                                </div>
                                <span>Retry on high AI detection</span>
                                <small style="color: #5f6368; margin-left: 8px;">Automatically retry humanization if AI detection score is too high</small>
                            </label>
                            
                            <label style="display: flex; align-items: center; margin-bottom: 12px;">
                                <div class="toggle-switch" style="margin-right: 12px;">
                                    <input type="checkbox" id="fallback-draft" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span>Fallback to draft on failure</span>
                                <small style="color: #5f6368; margin-left: 8px;">Save as draft instead of publishing if humanization fails</small>
                            </label>
                        </div>
                        
                        <!-- Test Humanization Button -->
                        <div style="text-align: center; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e8eaed;">
                            <button type="button" class="btn btn-secondary" style="margin-right: 12px;">Test Humanization</button>
                            <small style="color: #5f6368;">Test the humanization process with sample content</small>
                        </div>
                        
                        <!-- Image Generation -->
                        <div style="margin-top: 32px;">
                            <label style="display: flex; align-items: center; margin-bottom: 16px;">
                                <div class="toggle-switch" style="margin-right: 12px;">
                                    <input type="checkbox" id="generate-images" checked data-setting="include-images">
                                    <span class="toggle-slider"></span>
                                </div>
                                <span>Generate Featured Images</span>
                            </label>
                            
                            <label style="display: flex; align-items: center;">
                                <div class="toggle-switch" style="margin-right: 12px;">
                                    <input type="checkbox" id="campaign-active" checked>
                                    <span class="toggle-slider"></span>
                                </div>
                                <span>Campaign Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div style="margin-top: 40px; text-align: center; padding-top: 24px; border-top: 1px solid #e8eaed;">
                        <button type="button" class="btn btn-secondary" style="margin-right: 12px;">Save as Draft</button>
                        <button type="submit" class="btn btn-primary create-campaign-btn">Create Campaign</button>
                    </div>
                    
                </form>
                
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="campaign-type" value="news-search">
<input type="hidden" id="generation-method" value="smart">
<input type="hidden" id="frequency" value="hourly">

<script>
jQuery(document).ready(function($) {
    // Initialize form interactions
    $('.campaign-type').click(function() {
        $('.campaign-type').removeClass('selected');
        $(this).addClass('selected');
        $('#campaign-type').val($(this).data('type'));
        
        // Show/hide relevant configuration sections
        $('.config-section').hide();
        $('.' + $(this).data('type').replace('-', '_') + '-config').show();
    });
    
    // Update publishing stats when frequency changes
    $('.frequency-option').click(function() {
        const frequency = $(this).data('frequency');
        let perDay, costPerArticle;
        
        switch(frequency) {
            case 'every_15_minutes':
                perDay = 96;
                costPerArticle = 0.025;
                break;
            case 'every_30_minutes':
                perDay = 48;
                costPerArticle = 0.030;
                break;
            case 'hourly':
                perDay = 24;
                costPerArticle = 0.038;
                break;
            case 'daily':
                perDay = 1;
                costPerArticle = 0.080;
                break;
        }
        
        const perWeek = perDay * 7;
        const perMonth = perDay * 30;
        const dailyCost = perDay * costPerArticle;
        const monthlyCost = perMonth * costPerArticle;
        
        $('.stat-per-day .number').text(perDay);
        $('.stat-per-week .number').text(perWeek);
        $('.stat-per-month .number').text(perMonth);
        $('.stat-cost .number').text(' + costPerArticle.toFixed(3));
        $('.daily-cost .cost-amount').text(' + dailyCost.toFixed(2));
        $('.monthly-cost .cost-amount').text(' + monthlyCost.toFixed(2));
    });
});
</script>