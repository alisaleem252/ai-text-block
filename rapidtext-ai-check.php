
<?php


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 




// Add admin notice for usage limits
add_action('admin_notices', 'rapidtextai_usage_limit_notice');

function rapidtextai_usage_limit_notice() {
    // Only show on admin pages and to users who can manage options
    if (!current_user_can('manage_options') || !is_admin()) {
        return;
    }
    // Don't show on RapidTextAI settings pages to avoid duplication
    $current_screen = get_current_screen();
    
    if ($current_screen && strpos($current_screen->id, 'rapidtextai') !== false) {
        delete_transient('rapidtextai_usage_check'); // For testing purposes
    }
    
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        return; // Don't show usage notice if no API key is set
    }
    
    // Check if we should show the notice (don't spam users)
    $last_check = get_transient('rapidtextai_usage_check');
    if ($last_check !== false) {
        return; // Don't check again for 1 hour
    }
    
    // Set transient to prevent multiple checks
    set_transient('rapidtextai_usage_check', time(), HOUR_IN_SECONDS);
    
    // Make API request to check usage using the improved API structure
    $api_url = 'https://app.rapidtextai.com/api.php';
    $response = wp_remote_get($api_url . '?gigsixkey=' . urlencode($api_key), array(
        'timeout' => 10,
        'sslverify' => false
    ));
    
    if (is_wp_error($response)) {
        return; // Don't show notice if API is unreachable
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data || !isset($data['response_code'])) {
        return;
    }
    
    $code = $data['response_code'];
    // Handle different response codes based on the API structure
    if ($code == 1) {
        // Active subscription - check if approaching limits
        $requests_used = isset($data['requests_used']) ? intval($data['requests_used']) : 0;
        $request_limit = isset($data['request_limit']) ? intval($data['request_limit']) : 10000;
        $plan_code = isset($data['plan_code']) ? intval($data['plan_code']) : 1;
        
        if ($request_limit > 0) {
            $percentage_used = ($requests_used / $request_limit) * 100;
            
            // Only show notice if usage is above 80%
            if ($percentage_used >= 80) {
                ?>
                <div class="notice notice-warning is-dismissible rapidtextai-usage-notice">
                    <div style="display: flex; align-items: center; padding: 10px 0;">
                        <div style="flex-grow: 1;">
                            <h3 style="margin: 0 0 10px 0;">⚠️ RapidTextAI Usage Alert</h3>
                            <p style="margin: 0 0 10px 0;">
                                <strong>Usage:</strong> <?php echo number_format($requests_used); ?> / <?php echo number_format($request_limit); ?> requests used 
                                (<?php echo round($percentage_used, 1); ?>%)
                            </p>
                            <p style="margin: 0;">
                                You're approaching your monthly limit. 
                                <?php if (isset($data['current_period_end'])): ?>
                                    Your quota resets on <strong><?php echo date('F j, Y', strtotime($data['current_period_end'])); ?></strong>.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div style="margin-left: 20px;">
                            <?php if ($percentage_used >= 90): ?>
                                <a href="https://app.rapidtextai.com/pricing" target="_blank" 
                                   class="button button-primary" style="margin-right: 10px;">
                                    Upgrade Plan
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo admin_url('admin.php?page=rapidtextai-settings'); ?>" 
                               class="button button-secondary">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    } elseif ($code == 2) {
        // Free user - show upgrade notice
        $requests_used = isset($data['requests_used']) ? intval($data['requests_used']) : 0;
        ?>
        <div class="notice notice-info is-dismissible rapidtextai-usage-notice">
            <div style="display: flex; align-items: center; padding: 10px 0;">
                <div style="flex-grow: 1;">
                    <h3 style="margin: 0 0 10px 0;">🚀 RapidTextAI Free Account</h3>
                    <p style="margin: 0 0 10px 0;">
                        <strong>Requests used:</strong> <?php echo number_format($requests_used); ?>
                    </p>
                    <p style="margin: 0;">
                        You're using a <strong>free account</strong> with limited features. 
                        Upgrade to unlock unlimited requests and advanced AI models!
                    </p>
                </div>
                <div style="margin-left: 20px;">
                    <a href="https://app.rapidtextai.com/pricing" target="_blank" 
                       class="button button-primary" style="margin-right: 10px;">
                        Upgrade Now
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=rapidtextai-settings'); ?>" 
                       class="button button-secondary">
                        View Details
                    </a>
                </div>
            </div>
            
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                <h4 style="margin: 0 0 10px 0; color: #2271b1;">✨ Upgrade Benefits:</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div>
                        <strong>🚀 Basic Plan - $19/month</strong>
                        <ul style="margin: 5px 0; padding-left: 20px; font-size: 13px;">
                            <li>10,000 Requests/month</li>
                            <li>All AI Models</li>
                            <li>Custom Writing Styles</li>
                            <li>Auto-blogging Features</li>
                        </ul>
                    </div>
                    <div>
                        <strong>⚡ Pro Plan - $49/month</strong>
                        <ul style="margin: 5px 0; padding-left: 20px; font-size: 13px;">
                            <li>30,000 Requests/month</li>
                            <li>Priority Processing</li>
                            <li>Advanced Features</li>
                            <li>Priority Support</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    } elseif ($code == 3) {
        // Invalid API key
        ?>
        <div class="notice notice-error is-dismissible rapidtextai-usage-notice">
            <p>
                <strong>RapidTextAI Error:</strong> Invalid API key. 
                <a href="<?php echo admin_url('admin.php?page=rapidtextai-settings'); ?>">Please update your API key</a>.
            </p>
        </div>
        <?php
    } elseif ($code == 4) {
        // Invalid key format
        ?>
        <div class="notice notice-error is-dismissible rapidtextai-usage-notice">
            <p>
                <strong>RapidTextAI Error:</strong> Invalid API key format. 
                <a href="<?php echo admin_url('admin.php?page=rapidtextai-settings'); ?>">Please check your API key</a>.
            </p>
        </div>
        <?php
    }
    
    // Add JavaScript for notice handling
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle dismiss button
        $('.rapidtextai-usage-notice').on('click', '.notice-dismiss', function() {
            // Set a longer transient to prevent showing again too soon
            $.post(ajaxurl, {
                action: 'rapidtextai_dismiss_usage_notice',
                nonce: '<?php echo wp_create_nonce('rapidtextai_dismiss_notice'); ?>'
            });
        });
        
        // Auto-dismiss info notices after 15 seconds
        setTimeout(function() {
            $('.rapidtextai-usage-notice.notice-info .notice-dismiss').trigger('click');
        }, 15000);
    });
    </script>
    <?php
}

// Handle notice dismissal
add_action('wp_ajax_rapidtextai_dismiss_usage_notice', 'rapidtextai_dismiss_usage_notice_callback');

function rapidtextai_dismiss_usage_notice_callback() {
    if (!wp_verify_nonce($_POST['nonce'], 'rapidtextai_dismiss_notice')) {
        wp_die('Security check failed');
    }
    
    // Set a longer transient to prevent showing the notice again for 24 hours
    set_transient('rapidtextai_usage_check', time(), DAY_IN_SECONDS);
    
    wp_die(); // This is required to terminate immediately and return a proper response
}


/**
 * Check user limits and send admin notification if exceeded
 * Only sends one email per day using transient
 * 
 * @return array|bool Returns API response array on success, false on failure
 */
function rapidtextai_check_user_limits() {
    $api_key = get_option('rapidtextai_api_key', '');
    
    if (empty($api_key)) {
        error_log('RapidTextAI: API key not found for limits check');
        return false;
    }
    
    // Initialize cURL
    $curl = curl_init();
    
    // Set cURL options
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://app.rapidtextai.com/api.php?gigsixkey=' . urlencode($api_key),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json',
            'Content-Type: application/json'
        )
    ));
    
    // Execute cURL request
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);
    
    // Check for cURL errors
    if ($response === false || !empty($curl_error)) {
        error_log('RapidTextAI: cURL error during limits check - ' . $curl_error);
        return false;
    }
    
    // Check HTTP response code
    if ($http_code !== 200) {
        error_log('RapidTextAI: HTTP error ' . $http_code . ' during limits check');
        return false;
    }
    
    // Decode JSON response
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('RapidTextAI: JSON decode error during limits check - ' . json_last_error_msg());
        return false;
    }
    
    // Check if response is valid
    if (!isset($data['response_code'])) {
        error_log('RapidTextAI: Invalid response format during limits check');
        return false;
    }
    
    $response_code = $data['response_code'];
    
    // Handle different response codes
    switch ($response_code) {
        case 1: // Active subscription
            $requests_used = isset($data['requests_used']) ? intval($data['requests_used']) : 0;
            $request_limit = isset($data['request_limit']) ? intval($data['request_limit']) : 0;
            
            // Check if limits are exceeded
            if ($request_limit > 0 && $requests_used >= $request_limit) {
                error_log('RapidTextAI: Request limit exceeded - ' . $requests_used . '/' . $request_limit);
                rapidtextai_send_limit_exceeded_email($data);
                return false;
            }
            
            // Check if approaching limit (90% or higher)
            if ($request_limit > 0) {
                $usage_percentage = ($requests_used / $request_limit) * 100;
                if ($usage_percentage >= 90) {
                    error_log('RapidTextAI: Approaching request limit - ' . round($usage_percentage, 1) . '%');
                    rapidtextai_send_limit_warning_email($data);
                }
            }
            
            break;
            
        case 2: // Free user
            $requests_used = isset($data['requests_used']) ? intval($data['requests_used']) : 0;
            $request_limit = 100; // Free users have 100 requests per month
            
            // Check if limit is exceeded
            if ($requests_used >= $request_limit) {
                error_log('RapidTextAI: Free user request limit exceeded - ' . $requests_used . '/' . $request_limit);
                rapidtextai_send_limit_exceeded_email(array_merge($data, array('request_limit' => $request_limit)));
                return false;
            }
            
            // Check if approaching limit (80% or higher)
            $usage_percentage = ($requests_used / $request_limit) * 100;
            if ($usage_percentage >= 80) {
                error_log('RapidTextAI: Free user approaching request limit - ' . round($usage_percentage, 1) . '%');
                rapidtextai_send_limit_warning_email(array_merge($data, array('request_limit' => $request_limit)));
            }
            
            error_log('RapidTextAI: Free account - ' . $requests_used . '/' . $request_limit . ' requests used');
            rapidtextai_send_upgrade_reminder_email($data);
            break;
            
        case 3: // Invalid API key
            error_log('RapidTextAI: Invalid API key during limits check');
            rapidtextai_send_api_key_error_email();
            return false;
            
        case 4: // Invalid key format
            error_log('RapidTextAI: Invalid API key format during limits check');
            rapidtextai_send_api_key_error_email();
            return false;
            
        case 5: // API key required
            error_log('RapidTextAI: API key required during limits check');
            rapidtextai_send_api_key_error_email();
            return false;
            
        default:
            error_log('RapidTextAI: Unknown response code during limits check - ' . $response_code);
            return false;
    }
    
    return $data;
}

/**
 * Send email notification when request limits are exceeded
 * Only sends one email per day using transient
 * 
 * @param array $data API response data
 */
function rapidtextai_send_limit_exceeded_email($data) {
    // Check if we already sent an email today
    $transient_key = 'rapidtextai_limit_exceeded_email_sent';
    if (get_transient($transient_key)) {
        return; // Email already sent today
    }
    
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    $site_url = get_bloginfo('url');
    
    $requests_used = isset($data['requests_used']) ? intval($data['requests_used']) : 0;
    $request_limit = isset($data['request_limit']) ? intval($data['request_limit']) : 0;
    $current_period_end = isset($data['current_period_end']) ? $data['current_period_end'] : '';
    
    $subject = '[' . $site_name . '] RapidTextAI: Request Limit Exceeded - Auto Blogging Paused';
    
    $message = "Dear Administrator,\n\n";
    $message .= "This is an automated notification from your WordPress site: " . $site_name . "\n\n";
    $message .= "IMPORTANT: RapidTextAI Auto Blogging has been paused due to request limit exceeded.\n\n";
    $message .= "Current Usage Details:\n";
    $message .= "- Requests Used: " . number_format($requests_used) . "\n";
    $message .= "- Request Limit: " . number_format($request_limit) . "\n";
    $message .= "- Usage: 100% (Limit Exceeded)\n\n";
    
    if (!empty($current_period_end)) {
        $message .= "Your quota will reset on: " . date('F j, Y', strtotime($current_period_end)) . "\n\n";
    }
    
    $message .= "What you can do:\n\n";
    $message .= "1. UPGRADE YOUR PLAN:\n";
    $message .= "   Visit: https://app.rapidtextai.com/pricing\n";
    $message .= "   • Basic Plan ($19/month): 10,000 requests\n";
    $message .= "   • Pro Plan ($49/month): 30,000 requests\n\n";
    
    $message .= "2. WAIT FOR QUOTA RENEWAL:\n";
    if (!empty($current_period_end)) {
        $message .= "   Your requests will reset on " . date('F j, Y', strtotime($current_period_end)) . "\n\n";
    } else {
        $message .= "   Your requests will reset next month\n\n";
    }
    
    $message .= "3. MANAGE SETTINGS:\n";
    $message .= "   " . admin_url('admin.php?page=rapidtextai-auto-blogging') . "\n\n";
    
    $message .= "Auto blogging will automatically resume once your quota is renewed or you upgrade your plan.\n\n";
    $message .= "Site: " . $site_url . "\n";
    $message .= "Dashboard: " . admin_url() . "\n\n";
    $message .= "This email is sent once per day to avoid spam.\n\n";
    $message .= "Best regards,\n";
    $message .= "RapidTextAI WordPress Plugin";
    
    // Send email
    $sent = wp_mail($admin_email, $subject, $message);
    
    if ($sent) {
        // Set transient to prevent sending another email for 24 hours
        set_transient($transient_key, time(), DAY_IN_SECONDS);
        error_log('RapidTextAI: Limit exceeded notification email sent to ' . $admin_email);
    } else {
        error_log('RapidTextAI: Failed to send limit exceeded notification email');
    }
}

/**
 * Send warning email when approaching request limits (90%+)
 * Only sends one email per day using transient
 * 
 * @param array $data API response data
 */
function rapidtextai_send_limit_warning_email($data) {
    // Check if we already sent a warning email today
    $transient_key = 'rapidtextai_limit_warning_email_sent';
    if (get_transient($transient_key)) {
        return; // Email already sent today
    }
    
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    $requests_used = isset($data['requests_used']) ? intval($data['requests_used']) : 0;
    $request_limit = isset($data['request_limit']) ? intval($data['request_limit']) : 0;
    $usage_percentage = $request_limit > 0 ? round(($requests_used / $request_limit) * 100, 1) : 0;
    
    $subject = '[' . $site_name . '] RapidTextAI: Approaching Request Limit (' . $usage_percentage . '%)';
    
    $message = "Dear Administrator,\n\n";
    $message .= "Your RapidTextAI account is approaching its monthly request limit.\n\n";
    $message .= "Current Usage: " . number_format($requests_used) . " / " . number_format($request_limit) . " requests (" . $usage_percentage . "%)\n\n";
    $message .= "To avoid service interruption, consider upgrading your plan:\n";
    $message .= "https://app.rapidtextai.com/pricing\n\n";
    $message .= "Manage settings: " . admin_url('admin.php?page=rapidtextai-auto-blogging');
    
    $sent = wp_mail($admin_email, $subject, $message);
    
    if ($sent) {
        set_transient($transient_key, time(), DAY_IN_SECONDS);
        error_log('RapidTextAI: Limit warning email sent to ' . $admin_email);
    }
}

/**
 * Send reminder email for free users
 * Only sends one email per day using transient
 * 
 * @param array $data API response data
 */
function rapidtextai_send_upgrade_reminder_email($data) {
    // Check if we already sent an upgrade reminder today
    $transient_key = 'rapidtextai_upgrade_reminder_email_sent';
    if (get_transient($transient_key)) {
        return; // Email already sent today
    }
    
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    $requests_used = isset($data['requests_used']) ? intval($data['requests_used']) : 0;
    
    $subject = '[' . $site_name . '] RapidTextAI: Upgrade to Unlock Full Auto Blogging Features';
    
    $message = "Dear Administrator,\n\n";
    $message .= "You're using RapidTextAI with a free account.\n\n";
    $message .= "Requests used: " . number_format($requests_used) . "\n\n";
    $message .= "Upgrade to unlock unlimited requests and advanced features:\n";
    $message .= "https://app.rapidtextai.com/pricing\n\n";
    $message .= "Benefits of upgrading:\n";
    $message .= "• Unlimited monthly requests\n";
    $message .= "• Access to all AI models\n";
    $message .= "• Priority support\n";
    $message .= "• Advanced auto-blogging features";
    
    $sent = wp_mail($admin_email, $subject, $message);
    
    if ($sent) {
        set_transient($transient_key, time(), DAY_IN_SECONDS);
        error_log('RapidTextAI: Upgrade reminder email sent to ' . $admin_email);
    }
}

/**
 * Send email notification for API key errors
 * Only sends one email per day using transient
 */
function rapidtextai_send_api_key_error_email() {
    // Check if we already sent an API key error email today
    $transient_key = 'rapidtextai_api_key_error_email_sent';
    if (get_transient($transient_key)) {
        return; // Email already sent today
    }
    
    $admin_email = get_option('admin_email');
    $site_name = get_bloginfo('name');
    
    $subject = '[' . $site_name . '] RapidTextAI: API Key Error - Auto Blogging Disabled';
    
    $message = "Dear Administrator,\n\n";
    $message .= "RapidTextAI Auto Blogging has been disabled due to an API key error.\n\n";
    $message .= "Please check and update your API key:\n";
    $message .= admin_url('admin.php?page=rapidtextai-settings') . "\n\n";
    $message .= "Get your API key from:\n";
    $message .= "https://app.rapidtextai.com/dashboard\n\n";
    $message .= "Auto blogging will resume once a valid API key is configured.";
    
    $sent = wp_mail($admin_email, $subject, $message);
    
    if ($sent) {
        set_transient($transient_key, time(), DAY_IN_SECONDS);
        error_log('RapidTextAI: API key error email sent to ' . $admin_email);
    }
}