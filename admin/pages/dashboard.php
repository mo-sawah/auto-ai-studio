<?php
/**
 * Auto AI Studio Dashboard Page
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="auto-ai-studio-wrap">
    <div class="auto-ai-studio-container">
        
        <!-- Sidebar Navigation -->
        <div class="auto-ai-studio-sidebar">
            <h2>
                Auto AI Studio
                <div class="subtitle">Content Creation Suite</div>
            </h2>
            
            <div class="sidebar-menu-group">
                <h3>Automation Types</h3>
                <ul>
                    <li>
                        <a href="?page=auto-ai-studio-general" class="<?php echo ($_GET['page'] ?? '') === 'auto-ai-studio-general' ? 'current' : ''; ?>">
                            <span class="icon">📝</span>
                            General Articles
                        </a>
                    </li>
                    <li>
                        <a href="?page=auto-ai-studio-news" class="<?php echo ($_GET['page'] ?? '') === 'auto-ai-studio-news' ? 'current' : ''; ?>">
                            <span class="icon">📰</span>
                            Automated News
                        </a>
                    </li>
                    <li>
                        <a href="?page=auto-ai-studio-videos" class="<?php echo ($_GET['page'] ?? '') === 'auto-ai-studio-videos' ? 'current' : ''; ?>">
                            <span class="icon">🎬</span>
                            Auto Videos
                        </a>
                    </li>
                    <li>
                        <a href="?page=auto-ai-studio-podcast" class="<?php echo ($_GET['page'] ?? '') === 'auto-ai-studio-podcast' ? 'current' : ''; ?>">
                            <span class="icon">🎙️</span>
                            Auto Podcast
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="sidebar-menu-group">
                <h3>Management</h3>
                <ul>
                    <li>
                        <a href="?page=auto-ai-studio-campaigns" class="<?php echo ($_GET['page'] ?? '') === 'auto-ai-studio-campaigns' ? 'current' : ''; ?>">
                            <span class="icon">📊</span>
                            Campaign Manager
                        </a>
                    </li>
                    <li>
                        <a href="?page=auto-ai-studio-settings" class="<?php echo ($_GET['page'] ?? '') === 'auto-ai-studio-settings' ? 'current' : ''; ?>">
                            <span class="icon">⚙️</span>
                            Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="auto-ai-studio-main">
            <div class="auto-ai-studio-header">
                <h1>
                    <div class="header-icon">🤖</div>
                    Welcome to Auto AI Studio
                </h1>
                <button class="btn btn-primary test-connection-btn">Test AI Connection</button>
            </div>
            
            <div class="auto-ai-studio-content">
                
                <!-- Welcome Message -->
                <div style="text-align: center; margin-bottom: 40px;">
                    <p style="font-size: 16px; color: #5f6368; line-height: 1.5;">
                        Set up automated content generation campaigns to keep your site updated with fresh<br>
                        content. Choose from our automation types below to create scheduled campaigns that<br>
                        run automatically.
                    </p>
                </div>
                
                <!-- Automation Types Grid -->
                <div class="dashboard-grid">
                    <div class="dashboard-card general" onclick="location.href='?page=auto-ai-studio-general'">
                        <div class="card-icon">📝</div>
                        <h3>General Articles</h3>
                        <p>Create automated article generation campaigns with custom prompts and settings</p>
                    </div>
                    
                    <div class="dashboard-card news" onclick="location.href='?page=auto-ai-studio-news'">
                        <div class="card-icon">📰</div>
                        <h3>Automated News</h3>
                        <p>Generate articles automatically from latest news sources and RSS feeds</p>
                    </div>
                    
                    <div class="dashboard-card videos" onclick="location.href='?page=auto-ai-studio-videos'">
                        <div class="card-icon">🎬</div>
                        <h3>Auto Videos</h3>
                        <p>Find and embed YouTube videos automatically with generated descriptions</p>
                    </div>
                    
                    <div class="dashboard-card podcast" onclick="location.href='?page=auto-ai-studio-podcast'">
                        <div class="card-icon">🎙️</div>
                        <h3>Auto Podcast</h3>
                        <p>Create articles with automated podcast audio generation</p>
                    </div>
                </div>
                
                <!-- Statistics Overview -->
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number" id="automation-types"><?php echo $stats['automation_types'] ?? 4; ?></span>
                        <span class="stat-label">Automation Types</span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-number" id="minimum-frequency"><?php echo $stats['minimum_frequency'] ?? '10min'; ?></span>
                        <span class="stat-label">Minimum Frequency</span>
                    </div>
                    
                    <div class="stat-item">
                        <span class="stat-number" id="automated-status"><?php echo $stats['automated_status'] ?? '24/7'; ?></span>
                        <span class="stat-label">Automated</span>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div style="text-align: center; margin-top: 40px;">
                    <p style="font-size: 14px; color: #5f6368; margin-bottom: 16px;">
                        New to automation? Start with <a href="?page=auto-ai-studio-general" style="color: #4285f4; text-decoration: none;">General Articles</a> to create your first automated content campaign.<br>
                        Want to manage existing campaigns? Go to <a href="?page=auto-ai-studio-campaigns" style="color: #4285f4; text-decoration: none;">Campaign Manager</a>
                    </p>
                </div>
                
                <!-- Recent Activity -->
                <?php if (!empty($campaigns)): ?>
                <div style="background: white; border-radius: 12px; padding: 24px; margin-top: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <h3 style="margin-top: 0; color: #202124;">Recent Campaigns</h3>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid #e8eaed;">
                                    <th style="text-align: left; padding: 12px 0; color: #5f6368; font-weight: 500;">Campaign</th>
                                    <th style="text-align: left; padding: 12px 0; color: #5f6368; font-weight: 500;">Type</th>
                                    <th style="text-align: left; padding: 12px 0; color: #5f6368; font-weight: 500;">Status</th>
                                    <th style="text-align: left; padding: 12px 0; color: #5f6368; font-weight: 500;">Last Run</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($campaigns, 0, 5) as $campaign): ?>
                                <tr style="border-bottom: 1px solid #f1f3f4;">
                                    <td style="padding: 12px 0; font-weight: 500;"><?php echo esc_html($campaign['name']); ?></td>
                                    <td style="padding: 12px 0; color: #5f6368;">
                                        <?php 
                                        $type_names = [
                                            'general_articles' => 'General Articles',
                                            'automated_news' => 'Automated News',
                                            'auto_videos' => 'Auto Videos',
                                            'auto_podcast' => 'Auto Podcast'
                                        ];
                                        echo $type_names[$campaign['type']] ?? ucfirst(str_replace('_', ' ', $campaign['type']));
                                        ?>
                                    </td>
                                    <td style="padding: 12px 0;">
                                        <span class="status-badge <?php echo $campaign['status']; ?>" style="
                                            padding: 4px 8px; 
                                            border-radius: 12px; 
                                            font-size: 11px; 
                                            font-weight: 500;
                                            text-transform: uppercase;
                                            <?php if ($campaign['status'] === 'active'): ?>
                                                background: rgba(52, 168, 83, 0.1); color: #34a853;
                                            <?php else: ?>
                                                background: rgba(95, 99, 104, 0.1); color: #5f6368;
                                            <?php endif; ?>
                                        ">
                                            <?php echo ucfirst($campaign['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px 0; color: #5f6368;">
                                        <?php 
                                        if ($campaign['last_run']) {
                                            echo human_time_diff(strtotime($campaign['last_run']), current_time('timestamp')) . ' ago';
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Connection Status -->
                <div class="connection-status" style="margin-top: 24px;"></div>
                
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.status-success {
    color: #34a853;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-error {
    color: #ea4335;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>