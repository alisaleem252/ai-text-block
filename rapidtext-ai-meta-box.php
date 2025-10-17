<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add meta box to post edit screen
function rapidtextai_add_meta_box() {
    add_meta_box(
        'rapidtextai_meta_box',
        __('RapidTextAI', 'rapidtextai'),
        'rapidtextai_meta_box_callback',
        ['post', 'page'], // You can add custom post types here
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'rapidtextai_add_meta_box' );

// Meta box callback function
function rapidtextai_meta_box_callback( $post ) {
    // Get the API key option
    $current_api_key = get_option('rapidtextai_api_key', '');

    // Include nonce field for security
    wp_nonce_field( 'rapidtextai_meta_box_nonce', 'rapidtextai_meta_box_nonce' );

    // Output the form
    ?>
    <div id="articleForm">
        <div class="mb-3">
            <label for="articleTopic" class="form-label">Write an article about</label>
            <input type="text" class="form-control" id="articleTopic" placeholder="Enter topic here" required>
        </div>
        <div class="mb-3">
            <label for="articleKeywords" class="form-label">Focus Keywords</label>
            <input type="text" class="form-control" id="articleKeywords" placeholder="keyword 1, keyword 2" required>
        </div>
        <div class="mb-3">
            <label for="modelSelection" class="form-label">Select Model</label>
            <select class="form-select" id="modelSelection">
                <option value="gemini-2.0-flash">Gemini 2.0 (Google)</option>
                <option value="gemini-2.5-flash-preview-05-20">Gemini 2.5 (Google)</option>
                <option value="deepseek-chat">Deepseek 3.1 (DeepSeek)</option>
                <option value="claude-3-7-sonnet-latest">Claude 3.7 Sonnet (Anthropic)</option>
                <option value="claude-3-7-sonnet-latest">Claude 3.7 Sonnet (Anthropic)</option>
                <option value="glm-4.5-flash">GLM-4.5 Flash (Zhipu AI)</option>
                <option value="gpt-5">GPT-5 (OpenAI)</option>
                <option value="gpt-4">GPT-4 (OpenAI)</option>
                <option value="gpt-3.5">GPT-3.5 (OpenAI)</option>
                <option value="grok-2">Grok 2 (xAI)</option>
                <option value="grok-3">Grok 3 (xAI)</option>
            </select>
        </div>

        <!-- Advanced Options Link -->
        <a href="#" id="showAdvancedOptions">Show Advanced Options</a> <small>(For Pro Users)</small>

        <!-- Advanced Options Section (Initially Hidden) -->
        <div id="advancedOptions" style="display:none; margin-top: 20px;">
            <div class="mb-3">
                <label for="articleLength" class="form-label">Preferred Article Length</label>
                <select class="form-select" id="articleLength">
                    <option value="">Select Length</option>
                    <option value="500">500-700 words</option>
                    <option value="1000">1000-1500 words</option>
                    <option value="2000">2000-3000+ words</option>
                    <option value="100">100-200 words</option>
                    <option value="300">300-500 words</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="targetAudience" class="form-label">Target Audience</label>
                <input type="text" class="form-control" id="targetAudience" placeholder="e.g., students, professionals">
            </div>

            <div class="mb-3">
                <label for="articleTone" class="form-label">Tone</label>
                <select class="form-select" id="articleTone">
                    <option value="">Select Tone</option>
                    <option value="formal">Formal</option>
                    <option value="conversational">Conversational</option>
                    <option value="persuasive">Persuasive</option>
                    <option value="friendly">Friendly</option>
                    <option value="neutral">Neutral</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="writingStyle" class="form-label">Writing Style</label>
                <select class="form-select" id="writingStyle">
                    <option value="">Select Style</option>
                    <option value="informative">Informative</option>
                    <option value="narrative">Narrative</option>
                    <option value="technical">Technical</option>
                    <option value="descriptive">Descriptive</option>
                    <option value="explanatory">Explanatory</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="references" class="form-label">References/Sources (Optional)</label>
                <textarea class="form-control" id="references" rows="3" placeholder="Enter references, URLs, or sources"></textarea>
            </div>

            <div class="mb-3">
                <label for="articleStructure" class="form-label">Article Structure</label>
                <select class="form-select" id="articleStructure">
                    <option value="">Select Structure</option>
                    <option value="introduction-body-conclusion">Introduction, Body, Conclusion</option>
                    <option value="problem-solution">Problem, Solution</option>
                    <option value="cause-effect">Cause, Effect</option>
                    <option value="listicle">Listicle</option>
                    <option value="how-to">How-to</option>
                </select>
            </div>
            <!-- Internal links -->
            <div class="mb-3">
                <label for="internalLinks" class="form-label">Internal Links</label>
                <input type="text" class="form-control" id="internalLinks" placeholder="e.g., https://example.com/page1, https://example.com/page2">
            </div>
            <!-- External links -->
            <div class="mb-3">
                <label for="externalLinks" class="form-label">External Links</label>
                <input type="text" class="form-control" id="externalLinks" placeholder="e.g., https://example.com/page1, https://example.com/page2">
            </div>
            <div class="mb-3">
                <label for="callToAction" class="form-label">Include a Call-to-Action</label>
                <input type="text" class="form-control" id="callToAction" placeholder="e.g., Subscribe now, Learn more">
            </div>
        </div>
        <div class="mb-3" style="display: flex; gap: 10px; align-items: center;">
            <button type="button" class="btn btn-primary btn-lg" id="generateArticleButton" style="padding: 12px 24px; font-weight: 600;">
            <span class="dashicons dashicons-edit" style="margin-right: 8px;"></span>
            Generate Article
            </button>
            <button type="button" class="btn btn-outline-secondary" id="regenerateImagesButton" style="padding: 8px 16px; font-size: 14px;" title="Regenerate images for the current article">
            <span class="dashicons dashicons-images-alt2" style="margin-right: 4px;"></span>
            Regenerate Images
            </button>
        </div>
    </div>
    <?php
}

// Enqueue scripts
function rapidtextai_metabox_enqueue_scripts( $hook ) {
    // Only enqueue on post edit screen
    if ( 'post.php' != $hook && 'post-new.php' != $hook ) {
        return;
    }
    wp_enqueue_style( 'rapidtextai_styles', plugin_dir_url( __FILE__ ) . 'assets/css/rapidtextai-styles.css', array(), '1.0' );
    wp_enqueue_script( 'rapidtextai_marked_js', plugin_dir_url( __FILE__ ) . 'assets/js/marked.min.js', array(), '4.3.0', true );
    wp_enqueue_script( 'rapidtextai_script', plugin_dir_url( __FILE__ ) . 'assets/js/rapidtextai.js', array( 'jquery' ), '3.6', true );
    if (post_type_supports(get_post_type(), 'excerpt')) {
        wp_enqueue_script('rapidtextai_script-admin-js',  plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array('wp-element'), wp_rand(), true);        
    }
    if (post_type_supports(get_post_type(), 'post_tag') || post_type_supports(get_post_type(), 'category') || get_post_type() == 'post') {        
        wp_enqueue_script('rapidtextai_script-tags-js',  plugin_dir_url( __FILE__ ) . 'assets/js/tags.js', array('wp-element'), wp_rand(), true);
    }
    if (post_type_supports(get_post_type(), 'thumbnail')) {
        wp_enqueue_script('rapidtextai_script-featured-js', plugin_dir_url( __FILE__ ) . 'assets/js/featured.js', array('wp-element'), wp_rand(), true);
    }

    // Localize script to pass data
    wp_localize_script( 'rapidtextai_script', 'rapidtextai_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php', is_ssl() ? 'https' : 'relative'),
        'nonce'    => wp_create_nonce( 'rapidtextai_nonce' ),
        'api_key'  => get_option('rapidtextai_api_key', ''),
    ) );
}
add_action( 'admin_enqueue_scripts', 'rapidtextai_metabox_enqueue_scripts' );

// AJAX handler
function rapidtextai_generate_article($return = false) {

     // Check if streaming is requested
    if (isset($_POST['stream']) && $_POST['stream'] === 'true') {
        return rapidtextai_generate_article_stream();
    }
    
    // check nonce without ajax
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'rapidtextai_nonce' ) ) {
        if ($return) {
            return 'Invalid nonce';
        }
        wp_send_json_error(array(
            'message' => 'Invalid nonce',
        ));
    }
    
    // Get POST data
    $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';
    $tone_of_voice = isset( $_POST['toneOfVoice'] ) ? sanitize_text_field( $_POST['toneOfVoice'] ) : '';
    $language = isset( $_POST['language'] ) ? sanitize_text_field( $_POST['language'] ) : 'en';
    $text = isset( $_POST['text'] ) ? sanitize_textarea_field( $_POST['text'] ) : '';
    $temperature = isset( $_POST['temperature'] ) ? sanitize_text_field( $_POST['temperature'] ) : '0.7';
    $custom_prompt = isset( $_POST['custom_prompt'] ) ? sanitize_textarea_field( $_POST['custom_prompt'] ) : '';
    $chatsession = isset( $_POST['chatsession'] ) ? sanitize_text_field( $_POST['chatsession'] ) : 'rapidtextai__'.sanitize_text_field( $_POST['model'] ).'_' . time() . '_' . wp_rand();
    $userid = isset( $_POST['userid'] ) ? sanitize_text_field( $_POST['userid'] ) : '';

    // Handle model
    if ( isset( $_POST['model'] ) && ! empty( $_POST['model'] ) ) {
        $model = sanitize_text_field( $_POST['model'] );
    } else {
        $model = 'default_model'; // Set a default model if not provided
    }

    // Handle custom prompt

    // Build the API request payload in OpenAI format
    $api_payload = json_encode([
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => $custom_prompt
            ]
        ],
        'chatsession' => $chatsession,
    ]);

    // Now, make the API call
    $current_api_key = get_option('rapidtextai_api_key', '');

    // API endpoint with API key
    $api_url = "https://app.rapidtextai.com/openai/v1/chat/completionsarticle?gigsixkey=" . urlencode( $current_api_key );

    // Set up headers for the request
    $args = array(
        'body' => $api_payload,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'timeout' => 60,
    );

    // Make the API request
    $response = wp_remote_post($api_url, $args);

    // Handle the response
    if (is_wp_error($response)) {
        if ($return) {
            return $response->get_error_message();
        }
        wp_send_json_error(array(
            'message' => $response->get_error_message(),
        ));
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Check if response is valid JSON and has expected OpenAI format
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array(
                'message' => 'Invalid response format from API',
                'raw' => $body
            ));
        } else {
            // Pass through the OpenAI formatted response
            if($return){
                return $data;
            } else {
                wp_send_json_success($data);
            }
        }
    }
}
add_action( 'wp_ajax_rapidtextai_generate_article', 'rapidtextai_generate_article' );

// Add this new streaming function
function rapidtextai_generate_article_stream() {
    // Check nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'rapidtextai_nonce' ) ) {
        wp_send_json_error(array('message' => 'Invalid nonce'));
        return;
    }
    
    // Get POST data
    $custom_prompt = isset( $_POST['custom_prompt'] ) ? sanitize_textarea_field( $_POST['custom_prompt'] ) : '';
    $model = isset( $_POST['model'] ) ? sanitize_text_field( $_POST['model'] ) : 'gemini-2.0-flash';
    $chatsession = isset( $_POST['chatsession'] ) ? sanitize_text_field( $_POST['chatsession'] ) : 'rapidtextai_'.$model.'_' . time() . '_' . wp_rand();

    // Build the API request payload with streaming enabled
    $api_payload = json_encode([
        'model' => $model,
        'messages' => [
            [
                'role' => 'user',
                'content' => $custom_prompt
            ]
        ],
        'stream' => true, // Enable streaming
        'chatsession' => $chatsession,
    ]);

    $current_api_key = get_option('rapidtextai_api_key', '');
    $api_url = "https://app.rapidtextai.com/openai/v1/chat/completionsarticle-stream?gigsixkey=" . urlencode( $current_api_key );

    // Set SSE headers
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
    
    // Disable output buffering
    while (ob_get_level()) {
        ob_end_flush();
    }
    ob_implicit_flush(true);

    // Initialize cURL for streaming
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $api_payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_WRITEFUNCTION => function($curl, $data) {
            // Forward streaming data directly to client
            echo $data;
            flush();
            return strlen($data);
        },
        CURLOPT_TIMEOUT => 300,
        CURLOPT_RETURNTRANSFER => false,
    ]);

    curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "data: " . json_encode(['error' => ['message' => $error]]) . "\n\n";
    }
    
    exit();
}
add_action( 'wp_ajax_rapidtextai_generate_article_stream', 'rapidtextai_generate_article_stream' );
