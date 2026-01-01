<?php
/**
 * Auto Blogging Campaigns Management UI
 * 
 * This file provides the interface for managing multiple auto-blogging campaigns
 */

if (!defined('ABSPATH')) exit;
?>

<div class="wrap rapidtextai-auto-blogging">
    <div class="rapidtextai-header">
        <h1><?php esc_html_e('Auto Blogging Campaigns', 'rapidtextai'); ?></h1>
        <p class="description"><?php esc_html_e('Manage multiple auto-blogging campaigns with different settings and schedules.', 'rapidtextai'); ?></p>
    </div>

    <div class="rapidtextai-content">
        <?php if (!isset($_GET['edit_campaign']) && empty($_GET['action'])) : ?>
            <!-- Campaigns List -->
            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php esc_html_e('Active Campaigns', 'rapidtextai'); ?></h2>
                    <a href="<?php echo admin_url('admin.php?page=rapidtextai-auto-blogging&edit_campaign=new'); ?>" class="rapidtextai-btn rapidtextai-btn-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php esc_html_e('Add New Campaign', 'rapidtextai'); ?>
                    </a>
                </div>
                <div class="rapidtextai-card-body">
                    <?php if (empty($campaigns)) : ?>
                        <div class="rapidtextai-empty-state">
                            <span class="dashicons dashicons-admin-post"></span>
                            <h3><?php esc_html_e('No Campaigns Yet', 'rapidtextai'); ?></h3>
                            <p><?php esc_html_e('Create your first auto-blogging campaign to start generating content automatically.', 'rapidtextai'); ?></p>
                            <a href="<?php echo admin_url('admin.php?page=rapidtextai-auto-blogging&edit_campaign=new'); ?>" class="rapidtextai-btn rapidtextai-btn-primary">
                                <?php esc_html_e('Create Campaign', 'rapidtextai'); ?>
                            </a>
                        </div>
                    <?php else : ?>
                        <table class="wp-list-table widefat fixed striped rapidtextai-campaigns-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Campaign Name', 'rapidtextai'); ?></th>
                                    <th><?php esc_html_e('Status', 'rapidtextai'); ?></th>
                                    <th><?php esc_html_e('Schedule', 'rapidtextai'); ?></th>
                                    <th><?php esc_html_e('Model', 'rapidtextai'); ?></th>
                                    <th><?php esc_html_e('Topics', 'rapidtextai'); ?></th>
                                    <th><?php esc_html_e('Created', 'rapidtextai'); ?></th>
                                    <th><?php esc_html_e('Actions', 'rapidtextai'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($campaigns as $campaign) : 
                                    $topics_count = count(array_filter(explode("\n", $campaign['topics'])));
                                    $next_run = wp_next_scheduled('rapidtextai_auto_blogging_cron_' . $campaign['id']);
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($campaign['name']); ?></strong></td>
                                        <td>
                                            <?php if ($campaign['enabled']) : ?>
                                                <span class="rapidtextai-status rapidtextai-status-enabled">
                                                    <span class="dashicons dashicons-yes-alt"></span>
                                                    <?php esc_html_e('Enabled', 'rapidtextai'); ?>
                                                </span>
                                                <?php if ($next_run) : ?>
                                                    <br><small><?php echo esc_html('Next: ' . date_i18n('M j, g:i a', $next_run)); ?></small>
                                                <?php endif; ?>
                                            <?php else : ?>
                                                <span class="rapidtextai-status rapidtextai-status-disabled">
                                                    <span class="dashicons dashicons-dismiss"></span>
                                                    <?php esc_html_e('Disabled', 'rapidtextai'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html(ucfirst($campaign['schedule'])); ?></td>
                                        <td><?php echo esc_html($campaign['model']); ?></td>
                                        <td><?php echo esc_html($topics_count . ' ' . _n('topic', 'topics', $topics_count, 'rapidtextai')); ?></td>
                                        <td><?php echo isset($campaign['created']) ? esc_html(date_i18n('M j, Y', strtotime($campaign['created']))) : '-'; ?></td>
                                        <td>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rapidtextai-auto-blogging&edit_campaign=' . $campaign['id']), 'rapidtextai_campaign_action'); ?>" 
                                               class="rapidtextai-btn-icon" title="<?php esc_attr_e('Edit', 'rapidtextai'); ?>">
                                                <span class="dashicons dashicons-edit"></span>
                                            </a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rapidtextai-auto-blogging&action=toggle&campaign_id=' . $campaign['id']), 'rapidtextai_campaign_action'); ?>" 
                                               class="rapidtextai-btn-icon" title="<?php echo $campaign['enabled'] ? esc_attr__('Disable', 'rapidtextai') : esc_attr__('Enable', 'rapidtextai'); ?>">
                                                <span class="dashicons dashicons-<?php echo $campaign['enabled'] ? 'pause' : 'controls-play'; ?>"></span>
                                            </a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=rapidtextai-auto-blogging&action=delete&campaign_id=' . $campaign['id']), 'rapidtextai_campaign_action'); ?>" 
                                               class="rapidtextai-btn-icon rapidtextai-btn-danger" 
                                               onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this campaign?', 'rapidtextai'); ?>');"
                                               title="<?php esc_attr_e('Delete', 'rapidtextai'); ?>">
                                                <span class="dashicons dashicons-trash"></span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php else : ?>
            <!-- Campaign Editor -->
            <div class="rapidtextai-card">
                <div class="rapidtextai-card-header">
                    <h2><?php echo $editing_campaign ? esc_html__('Edit Campaign: ', 'rapidtextai') . esc_html($editing_campaign['name']) : esc_html__('New Campaign', 'rapidtextai'); ?></h2>
                    <a href="<?php echo admin_url('admin.php?page=rapidtextai-auto-blogging'); ?>" class="rapidtextai-btn rapidtextai-btn-secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php esc_html_e('Back to Campaigns', 'rapidtextai'); ?>
                    </a>
                </div>
                <div class="rapidtextai-card-body">
                    <?php include(RAPIDTEXTAI_PLUGIN_DIR . 'admin/auto_blogging_page.php'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.rapidtextai-campaigns-table {
    margin-top: 20px;
}

.rapidtextai-status {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.rapidtextai-status .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    margin-right: 4px;
}

.rapidtextai-status-enabled {
    background: #d1f4e0;
    color: #0c6d38;
}

.rapidtextai-status-disabled {
    background: #f0f0f1;
    color: #646970;
}

.rapidtextai-btn-icon {
    display: inline-block;
    padding: 4px;
    text-decoration: none;
    margin: 0 2px;
}

.rapidtextai-btn-icon .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.rapidtextai-btn-danger:hover {
    color: #d63638;
}

.rapidtextai-empty-state {
    text-align: center;
    padding: 60px 20px;
}

.rapidtextai-empty-state .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    opacity: 0.3;
    margin-bottom: 20px;
}

.rapidtextai-empty-state h3 {
    font-size: 20px;
    margin: 0 0 10px;
}

.rapidtextai-empty-state p {
    color: #646970;
    margin-bottom: 20px;
}

.rapidtextai-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
</style>
