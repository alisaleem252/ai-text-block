<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Return available RapidTextAI models
 */
function rapidtextai_chatbot_models() {
    return array(
        'text-davinci-003' => array('provider' => 'openai', 'model' => 'text-davinci-003'),
        'gpt-3.5-turbo'    => array('provider' => 'openai', 'model' => 'gpt-3.5-turbo'),
        'gpt-4o-mini'      => array('provider' => 'openai', 'model' => 'gpt-4o-mini'),
        'gpt-3.5'          => array('provider' => 'openai', 'model' => 'gpt-3.5-turbo'),
        'gpt-4'            => array('provider' => 'openai', 'model' => 'gpt-4'),
        'gpt-4o'           => array('provider' => 'openai', 'model' => 'gpt-4o'),
        'gpt-4o-search-preview' => array('provider' => 'openai', 'model' => 'gpt-4o-search-preview'),
        'gpt-4o-mini-search-preview' => array('provider' => 'openai', 'model' => 'gpt-4o-mini-search-preview'),
        'gpt-5'            => array('provider' => 'openai', 'model' => 'gpt-5-mini'),
        'gemini-pro'       => array('provider' => 'gemini', 'model' => 'gemini-pro'),
        'gemini-1.5-pro'   => array('provider' => 'gemini', 'model' => 'gemini-1.5-pro'),
        'gemini-1.5-flash' => array('provider' => 'gemini', 'model' => 'gemini-1.5-flash'),
        'gemini-2.0-flash' => array('provider' => 'gemini', 'model' => 'gemini-2.0-flash'),
        'gemini-2.0-pro'   => array('provider' => 'gemini', 'model' => 'gemini-2.0-pro'),
        'gemini-2.5-pro-preview-05-06' => array('provider' => 'gemini', 'model' => 'gemini-2.5-pro-preview-05-06'),
        'gemini-2.5-flash-preview-05-20' => array('provider' => 'gemini', 'model' => 'gemini-2.5-flash-preview-05-20'),
        'deepseek-reasoner' => array('provider' => 'deepseek', 'model' => 'deepseek-reasoner'),
        'deepseek-chat'     => array('provider' => 'deepseek', 'model' => 'deepseek-chat'),
        'grok-2'            => array('provider' => 'grok', 'model' => 'grok-2'),
        'claude-3-haiku-20240307'   => array('provider' => 'claude', 'model' => 'claude-3-haiku-20240307'),
        'claude-3-5-sonnet-20241022' => array('provider' => 'claude', 'model' => 'claude-3-5-sonnet-20241022'),
        'claude-3-opus-20240229'    => array('provider' => 'claude', 'model' => 'claude-3-opus-20240229'),
        'claude-3-7-sonnet-latest'  => array('provider' => 'claude', 'model' => 'claude-3-7-sonnet-latest'),
    );
}

/**
 * Register submenu and shortcode
 */
function rapidtextai_chatbot_admin_menu() {
    add_submenu_page(
        'rapidtextai-settings',
        __( 'Chatbots', 'rapidtextai' ),
        __( 'Chatbots', 'rapidtextai' ),
        'manage_options',
        'rapidtextai-chatbots',
        'rapidtextai_chatbots_page'
    );
}
add_action( 'admin_menu', 'rapidtextai_chatbot_admin_menu' );

/**
 * Render chatbot admin page
 */
function rapidtextai_chatbots_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $chatbots = get_option( 'rapidtextai_chatbots', array() );

    // Handle delete action
    if ( isset( $_GET['delete'] ) && check_admin_referer( 'rapidtextai_delete_chatbot_' . sanitize_text_field( $_GET['delete'] ) ) ) {
        $delete_id = sanitize_text_field( $_GET['delete'] );
        if ( isset( $chatbots[ $delete_id ] ) ) {
            unset( $chatbots[ $delete_id ] );
            update_option( 'rapidtextai_chatbots', $chatbots );
            echo '<div class="updated"><p>' . esc_html__( 'Chatbot deleted.', 'rapidtextai' ) . '</p></div>';
        }
    }

    // Handle save action
    if ( isset( $_POST['rapidtextai_chatbot_nonce'] ) && wp_verify_nonce( $_POST['rapidtextai_chatbot_nonce'], 'rapidtextai_save_chatbot' ) ) {
        $id    = sanitize_text_field( wp_unique_id( 'rtcb_' ) );
        $chatbots[ $id ] = array(
            'name'   => sanitize_text_field( $_POST['chatbot_name'] ),
            'model'  => sanitize_text_field( $_POST['chatbot_model'] ),
            'prompt' => sanitize_textarea_field( $_POST['chatbot_prompt'] ),
            'tools'  => sanitize_textarea_field( $_POST['chatbot_tools'] ),
            'theme'  => sanitize_text_field( $_POST['chatbot_theme'] ),
            'layout' => sanitize_text_field( $_POST['chatbot_layout'] ),
            'code'   => sanitize_textarea_field( $_POST['chatbot_code'] ),
        );
        update_option( 'rapidtextai_chatbots', $chatbots );
        echo '<div class="updated"><p>' . esc_html__( 'Chatbot saved.', 'rapidtextai' ) . '</p></div>';
    }

    $models = rapidtextai_chatbot_models();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'RapidTextAI Chatbots', 'rapidtextai' ); ?></h1>
        <form method="post">
            <?php wp_nonce_field( 'rapidtextai_save_chatbot', 'rapidtextai_chatbot_nonce' ); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="chatbot_name"><?php esc_html_e( 'Name', 'rapidtextai' ); ?></label></th>
                    <td><input name="chatbot_name" type="text" id="chatbot_name" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="chatbot_model"><?php esc_html_e( 'Model', 'rapidtextai' ); ?></label></th>
                    <td>
                        <select name="chatbot_model" id="chatbot_model">
                            <?php foreach ( $models as $key => $model ) : ?>
                                <option value="<?php echo esc_attr( $model['model'] ); ?>"><?php echo esc_html( $key ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="chatbot_prompt"><?php esc_html_e( 'System Prompt', 'rapidtextai' ); ?></label></th>
                    <td><textarea name="chatbot_prompt" id="chatbot_prompt" class="large-text" rows="3"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="chatbot_tools"><?php esc_html_e( 'Tools (JSON)', 'rapidtextai' ); ?></label></th>
                    <td><textarea name="chatbot_tools" id="chatbot_tools" class="large-text" rows="3"></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="chatbot_theme"><?php esc_html_e( 'Theme', 'rapidtextai' ); ?></label></th>
                    <td><input name="chatbot_theme" type="text" id="chatbot_theme" class="regular-text" value="light"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="chatbot_layout"><?php esc_html_e( 'Layout', 'rapidtextai' ); ?></label></th>
                    <td><input name="chatbot_layout" type="text" id="chatbot_layout" class="regular-text" value="default"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="chatbot_code"><?php esc_html_e( 'Code Snippet', 'rapidtextai' ); ?></label></th>
                    <td><textarea name="chatbot_code" id="chatbot_code" class="large-text" rows="3"></textarea></td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Chatbot', 'rapidtextai' ) ); ?>
        </form>

        <?php if ( ! empty( $chatbots ) ) : ?>
            <h2><?php esc_html_e( 'Existing Chatbots', 'rapidtextai' ); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'rapidtextai' ); ?></th>
                        <th><?php esc_html_e( 'Shortcode', 'rapidtextai' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'rapidtextai' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $chatbots as $id => $bot ) : ?>
                        <tr>
                            <td><?php echo esc_html( $bot['name'] ); ?></td>
                            <td>[rapidtextai_chatbot id="<?php echo esc_attr( $id ); ?>"]</td>
                            <td>
                                <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=rapidtextai-chatbots&delete=' . $id ), 'rapidtextai_delete_chatbot_' . $id ); ?>" class="button"><?php esc_html_e( 'Delete', 'rapidtextai' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Shortcode output
 */
function rapidtextai_chatbot_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id' => '',
    ), $atts, 'rapidtextai_chatbot' );

    $chatbots = get_option( 'rapidtextai_chatbots', array() );
    $id       = $atts['id'];
    if ( empty( $id ) || empty( $chatbots[ $id ] ) ) {
        return '';
    }
    $bot = $chatbots[ $id ];

    $plugin_file = dirname( __FILE__ ) . '/../rapidtext-ai-text-block.php';
    wp_enqueue_style( 'rapidtextai-chatbot', plugins_url( 'chatbot/chatbot.css', $plugin_file ), array(), '1.0' );
    wp_enqueue_script( 'rapidtextai-chatbot', plugins_url( 'chatbot/chatbot.js', $plugin_file ), array( 'jquery' ), '1.0', true );
    wp_localize_script( 'rapidtextai-chatbot', 'rapidtextaiChatbot', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'rapidtextai_chatbot_nonce' ),
        'error'    => __( 'Unable to get response', 'rapidtextai' ),
    ) );

    ob_start();
    ?>
    <div class="rapidtextai-chatbot rapidtextai-theme-<?php echo esc_attr( $bot['theme'] ); ?> rapidtextai-layout-<?php echo esc_attr( $bot['layout'] ); ?>" data-chatbot="<?php echo esc_attr( $id ); ?>">
        <div class="rapidtextai-chatbot-messages"></div>
        <textarea class="rapidtextai-chatbot-input" rows="3"></textarea>
        <button type="button" class="rapidtextai-chatbot-send"><?php esc_html_e( 'Send', 'rapidtextai' ); ?></button>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'rapidtextai_chatbot', 'rapidtextai_chatbot_shortcode' );

/**
 * AJAX handler to send messages to RapidTextAI
 */
function rapidtextai_chatbot_message() {
    check_ajax_referer( 'rapidtextai_chatbot_nonce', 'nonce' );

    $chatbot_id = isset( $_POST['chatbot_id'] ) ? sanitize_text_field( $_POST['chatbot_id'] ) : '';
    $message    = isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
    $chatbots   = get_option( 'rapidtextai_chatbots', array() );

    if ( empty( $chatbot_id ) || empty( $message ) || empty( $chatbots[ $chatbot_id ] ) ) {
        wp_send_json_error( 'invalid' );
    }

    $bot     = $chatbots[ $chatbot_id ];
    $api_key = get_option( 'rapidtextai_api_key' );
    if ( empty( $api_key ) ) {
        wp_send_json_error( 'missing_key' );
    }

    $messages = array(
        array( 'role' => 'system', 'content' => $bot['prompt'] ),
        array( 'role' => 'user', 'content' => $message ),
    );

    $body = array(
        'model'    => $bot['model'],
        'messages' => $messages,
    );

    if ( ! empty( $bot['tools'] ) ) {
        $tools = json_decode( $bot['tools'], true );
        if ( $tools ) {
            $body['tools'] = $tools;
        }
    }
    if ( ! empty( $bot['code'] ) ) {
        $body['code_snippets'] = $bot['code'];
    }

    $endpoint  = 'https://app.rapidtextai.com/openai/v1/chat/completions?gigsixkey=' . $api_key;
    $response  = wp_remote_post( $endpoint, array(
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body'    => wp_json_encode( $body ),
        'timeout' => 30,
    ) );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( 'request_error' );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( isset( $data['choices'][0]['message']['content'] ) ) {
        wp_send_json_success( array( 'reply' => trim( $data['choices'][0]['message']['content'] ) ) );
    }

    wp_send_json_error( 'bad_response' );
}
add_action( 'wp_ajax_rapidtextai_chatbot_message', 'rapidtextai_chatbot_message' );
add_action( 'wp_ajax_nopriv_rapidtextai_chatbot_message', 'rapidtextai_chatbot_message' );

?>
