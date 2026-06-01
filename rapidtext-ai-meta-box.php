<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Meta box replaced by modal — no meta box registered
function rapidtextai_add_meta_box() {}
add_action( 'add_meta_boxes', 'rapidtextai_add_meta_box' );

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

    // Enqueue the modal trigger button script (follows same pattern as featured.js)
    wp_enqueue_script(
        'rapidtextai_generate_modal',
        plugin_dir_url( __FILE__ ) . 'assets/js/generate-modal.js',
        array( 'jquery' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/generate-modal.js' ),
        true
    );
    wp_localize_script( 'rapidtextai_generate_modal', 'rapidtextai_modal', array(
        'icon_url' => 'https://app.rapidtextai.com/assets/images/fav.png?v=1.1',
    ) );

    // Render the Generate Article modal HTML + CSS in the footer
    add_action( 'admin_footer', 'rapidtextai_render_generate_modal' );
}
add_action( 'admin_enqueue_scripts', 'rapidtextai_metabox_enqueue_scripts' );

/**
 * Outputs the Generate Article modal overlay and injects the trigger button
 * next to the Featured Image meta box in the post editor.
 */
function rapidtextai_render_generate_modal() {
    $metabox_js_url  = esc_url( RAPIDTEXTAI_PLUGIN_URL . 'assets/js/metabox.js' );
    $metabox_css_url = esc_url( RAPIDTEXTAI_PLUGIN_URL . 'assets/css/metabox.css' );
    $icon_url        = 'https://app.rapidtextai.com/assets/images/fav.png?v=1.1';
    ?>
    <!-- RapidTextAI Generate Article Modal -->
    <div id="rapidtextai-modal-overlay" aria-modal="true" role="dialog" aria-label="<?php esc_attr_e( 'Generate Article', 'rapidtextai' ); ?>">
        <div id="rapidtextai-modal-wrap">
            <div id="rapidtextai-modal-header">
                <span id="rapidtextai-modal-title">
                    <img src="<?php echo esc_url( $icon_url ); ?>" alt="" width="20" height="20">
                    <?php esc_html_e( 'Generate Article', 'rapidtextai' ); ?>
                </span>
                <button id="rapidtextai-modal-close" type="button" aria-label="<?php esc_attr_e( 'Close', 'rapidtextai' ); ?>">&times;</button>
            </div>
            <div id="rapidtextai-modal-body">
                <link rel="stylesheet" crossorigin href="<?php echo $metabox_css_url; ?>">
                <div id="rapidtextai-root"></div>
            </div>
        </div>
    </div>
    <script type="module" crossorigin src="<?php echo $metabox_js_url; ?>"></script>

    <style>
    /* Generate Article trigger button */
    .rapidtextai-generate-article-btn {
        display: flex !important;
        align-items: center !important;
        gap: 6px !important;
    }
    .rapidtextai-generate-article-btn img {
        border-radius: 2px;
        flex-shrink: 0;
    }
    /* Modal */
    #rapidtextai-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.55);
        z-index: 100000;
        align-items: center;
        justify-content: center;
    }
    #rapidtextai-modal-overlay.rtai-open {
        display: flex;
    }
    #rapidtextai-modal-wrap {
        background: #fff;
        border-radius: 8px;
        width: 92%;
        max-width: 900px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 8px 40px rgba(0,0,0,.3);
        overflow: hidden;
    }
    #rapidtextai-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 20px;
        border-bottom: 1px solid #ddd;
        background: #f8f8f8;
        font-size: 15px;
        font-weight: 600;
        flex-shrink: 0;
    }
    #rapidtextai-modal-title {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    #rapidtextai-modal-title img {
        border-radius: 3px;
    }
    #rapidtextai-modal-close {
        background: none;
        border: none;
        font-size: 24px;
        line-height: 1;
        cursor: pointer;
        color: #666;
        padding: 0 4px;
        transition: color .15s;
    }
    #rapidtextai-modal-close:hover { color: #000; }
    #rapidtextai-modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }
    </style>
    <?php
}

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
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
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