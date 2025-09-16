<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Generate widget HTML for the chatbot
$settings = $chatbot['settings'];
$theme_class = 'theme-' . esc_attr($chatbot['theme']);
$position_class = 'position-' . esc_attr($settings['position']);
$size_class = 'size-' . esc_attr($settings['size']);
?>

<div id="<?php echo esc_attr($instance_id); ?>" 
    class="rapidtextai-chatbot-widget <?php echo esc_attr($theme_class . ' ' . $position_class . ' ' . $size_class); ?>"
    data-chatbot-id="<?php echo esc_attr($chatbot_id); ?>"
    data-settings="<?php echo esc_attr(wp_json_encode($settings)); ?>">
    
    <!-- Chatbot Toggle Button -->
    <button class="chatbot-toggle" type="button" aria-label="Open Chat">
       <svg class="chatbot-toggle-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M20 2H4C2.9 2 2 2.9 2 4V16C2 17.1 2.9 18 4 18H6L9 21L12 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H11.17L9 18.17L6.83 16H4V4H20V16Z" fill="currentColor"/>
          <circle cx="7" cy="10" r="1" fill="currentColor"/>
          <circle cx="12" cy="10" r="1" fill="currentColor"/>
          <circle cx="17" cy="10" r="1" fill="currentColor"/>
       </svg>
    </button>
    
    <!-- Chatbot Container -->
    <div class="chatbot-container">
       <!-- Header -->
       <div class="chatbot-header">
          <h3 class="chatbot-header-title"><?php echo esc_html($chatbot['name']); ?></h3>
          <button class="chatbot-close" type="button" aria-label="Close Chat">
             <svg class="chatbot-close-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
             </svg>
          </button>
       </div>
       
       <!-- Messages Area -->
       <div class="chatbot-messages" role="log" aria-label="Chat messages"></div>
       
       <!-- Input Area -->
       <div class="chatbot-input">
          <textarea class="chatbot-input-field" 
                 placeholder="Type your message..."
                 rows="1"
                 aria-label="Message input"></textarea>
          <button class="chatbot-send" type="button" aria-label="Send message">
             <svg class="chatbot-send-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M22 2L11 13M22 2L15 22L11 13M22 2L2 9L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
             </svg>
          </button>
       </div>
    </div>
</div>

<!-- Custom Styles for this chatbot instance -->
<style>
#<?php echo esc_attr($instance_id); ?> {
    --rapidtextai-primary-color: <?php echo esc_attr($settings['primary_color']); ?>;
    --rapidtextai-secondary-color: <?php echo esc_attr($settings['secondary_color']); ?>;
    --rapidtextai-text-color: <?php echo esc_attr($settings['text_color']); ?>;
    --rapidtextai-background-color: <?php echo esc_attr($settings['background_color']); ?>;
    --rapidtextai-user-bubble-color: <?php echo esc_attr($settings['primary_color']); ?>;
    --rapidtextai-assistant-bubble-color: <?php echo $chatbot['theme'] === 'dark' ? '#2d2d2d' : '#f5f5f5'; ?>;
}

<?php if ($settings['show_avatar'] && !empty($settings['avatar_url'])): ?>
#<?php echo esc_attr($instance_id); ?> .message-avatar {
    background-image: url('<?php echo esc_url($settings['avatar_url']); ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}
<?php endif; ?>
</style>