<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Add chatbots submenu
add_action('admin_menu', 'rapidtextai_add_chatbots_menu',999);

function rapidtextai_add_chatbots_menu() {
    add_submenu_page(
        'rapidtextai-settings',
        'AI Chatbots',
        'AI Chatbots',
        'manage_options',
        'rapidtextai-chatbots',
        'rapidtextai_chatbots_page'
    );
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'rapidtextai_chatbots_admin_scripts');

function rapidtextai_chatbots_admin_scripts($hook) {
    if ($hook !== 'rapidtextai_page_rapidtextai-chatbots') {
        return;
    }
    
    wp_enqueue_script('wp-color-picker');
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-ui-tabs');
    wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/ui-lightness/jquery-ui.css');
    
    wp_enqueue_script(
        'rapidtextai-chatbots-admin',
        RAPIDTEXTAI_PLUGIN_URL . '/ext/chatbots/js/chatbots-admin.js',
        array('jquery', 'wp-color-picker', 'jquery-ui-sortable', 'jquery-ui-tabs'),
        '1.0.0',
        true
    );
    
    wp_enqueue_style(
        'rapidtextai-chatbots-admin',
        RAPIDTEXTAI_PLUGIN_URL . '/ext/chatbots/css/chatbots-admin.css',
        array('wp-color-picker'),
        '1.0.0'
    );
    
    wp_localize_script('rapidtextai-chatbots-admin', 'rapidtextai_chatbots_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rapidtextai_chatbots_nonce'),
    ));
}

// Enqueue frontend scripts
add_action('wp_enqueue_scripts', 'rapidtextai_chatbots_frontend_scripts');

function rapidtextai_chatbots_frontend_scripts() {
    wp_enqueue_script(
        'rapidtextai-chatbots-frontend',
        RAPIDTEXTAI_PLUGIN_URL . '/ext/chatbots/js/chatbots-frontend.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    wp_enqueue_style(
        'rapidtextai-chatbots-frontend',
        RAPIDTEXTAI_PLUGIN_URL . '/ext/chatbots/css/chatbots-frontend.css',
        array(),
        '1.0.0'
    );
    
    wp_localize_script('rapidtextai-chatbots-frontend', 'rapidtextai_chatbots_frontend', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rapidtextai_chatbots_frontend_nonce'),
    ));
}

// Main chatbots page
function rapidtextai_chatbots_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $chatbot_id = isset($_GET['chatbot_id']) ? intval($_GET['chatbot_id']) : 0;
    
    switch ($action) {
        case 'add':
            rapidtextai_chatbots_add_edit_page();
            break;
        case 'edit':
            rapidtextai_chatbots_add_edit_page($chatbot_id);
            break;
        case 'delete':
            rapidtextai_chatbots_delete($chatbot_id);
            rapidtextai_chatbots_list_page();
            break;
        default:
            rapidtextai_chatbots_list_page();
            break;
    }
}

// List chatbots page
function rapidtextai_chatbots_list_page() {
    $chatbots = rapidtextai_get_all_chatbots();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">AI Chatbots</h1>
        <a href="<?php echo admin_url('admin.php?page=rapidtextai-chatbots&action=add'); ?>" class="page-title-action">Add New Chatbot</a>
        <hr class="wp-header-end">
        
        <?php if (empty($chatbots)): ?>
            <div class="notice notice-info">
                <p>No chatbots found. <a href="<?php echo admin_url('admin.php?page=rapidtextai-chatbots&action=add'); ?>">Create your first chatbot</a>.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Shortcode</th>
                        <th>Model</th>
                        <th>Theme</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chatbots as $chatbot): ?>
                        <tr>
                            <td><strong><?php echo esc_html($chatbot['name']); ?></strong></td>
                            <td><code>[rapidtextai_chatbot id="<?php echo $chatbot['id']; ?>"]</code></td>
                            <td><?php echo esc_html($chatbot['model']); ?></td>
                            <td><?php echo esc_html($chatbot['theme']); ?></td>
                            <td>
                                <span class="status-<?php echo $chatbot['status']; ?>">
                                    <?php echo $chatbot['status'] === 'active' ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=rapidtextai-chatbots&action=edit&chatbot_id=' . $chatbot['id']); ?>">Edit</a> |
                                <a href="<?php echo admin_url('admin.php?page=rapidtextai-chatbots&action=delete&chatbot_id=' . $chatbot['id']); ?>" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// Add/Edit chatbot page
function rapidtextai_chatbots_add_edit_page($chatbot_id = 0) {
    $is_edit = $chatbot_id > 0;
    $chatbot = $is_edit ? rapidtextai_get_chatbot($chatbot_id) : rapidtextai_get_default_chatbot_settings();
    
    if ($is_edit && !$chatbot) {
        wp_die('Chatbot not found.');
    }
    // Handle form submission
    if (isset($_POST['submit_chatbot']) && wp_verify_nonce($_POST['rapidtextai_chatbots_nonce'], 'rapidtextai_chatbots_save')) {
        $result = rapidtextai_save_chatbot($_POST, $chatbot_id);
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Chatbot saved successfully!</p></div>';
            if (!$is_edit) {
                $chatbot_id = $result;
                $chatbot = rapidtextai_get_chatbot($chatbot_id);
                $is_edit = true;
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Error saving chatbot.</p></div>';
        }
    }
    $chatbot = $is_edit ? rapidtextai_get_chatbot($chatbot_id) : rapidtextai_get_default_chatbot_settings();
    include(plugin_dir_path(__FILE__) . 'views/chatbot-form.php');
}

// Get all chatbots
function rapidtextai_get_all_chatbots() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rapidtextai_chatbots';
    
    // Create table if it doesn't exist
    rapidtextai_create_chatbots_table();
    
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
}

// Get single chatbot
function rapidtextai_get_chatbot($chatbot_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rapidtextai_chatbots';
    
    $chatbot = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $chatbot_id), ARRAY_A);
    
    if ($chatbot) {
        // Decode JSON fields
        $chatbot['settings'] = json_decode($chatbot['settings'], true);
        $chatbot['knowledge_base'] = maybe_unserialize($chatbot['knowledge_base']);
        $chatbot['tools'] = maybe_unserialize($chatbot['tools']);
        //$chatbot['php_functions'] = maybe_unserialize($chatbot['php_functions']);        
    }
    
    return $chatbot;
}

// Save chatbot
function rapidtextai_save_chatbot($data, $chatbot_id = 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rapidtextai_chatbots';
    if ( isset($data['tools']) ) {
        $data['tools'] = wp_unslash($data['tools']);
        $tools = array_map(function($tool) {
            if (isset($tool['parameters'])) {
                $tool['parameters'] = json_decode(stripslashes($tool['parameters']), true);
            }
            return $tool;
        }, $data['tools']);
    }
    // if ( isset($data['php_functions']) ) {
    //     $data['php_functions'] = wp_unslash($data['php_functions']);
    //     $php_functions = array_map(function($func) {
    //         if (isset($func['parameters'])) {
    //             $func['parameters'] = json_decode(stripslashes($func['parameters']), true);
    //         }
    //         if (isset($func['code'])) {
    //             $func['code'] = stripslashes($func['code']);
    //         }
    //         return $func;
    //     }, $data['php_functions']);
    // }

    $chatbot_data = array(
        'name' => sanitize_text_field($data['name']),
        'description' => sanitize_textarea_field($data['description']),
        'model' => sanitize_text_field($data['model']),
        'theme' => sanitize_text_field($data['theme']),
        'status' => sanitize_text_field($data['status']),
        'system_message' => sanitize_textarea_field($data['system_message']),
        'welcome_message' => sanitize_textarea_field($data['welcome_message']),
        'settings' => wp_json_encode(array(
            'primary_color' => sanitize_hex_color($data['primary_color']),
            'secondary_color' => sanitize_hex_color($data['secondary_color']),
            'text_color' => sanitize_hex_color($data['text_color']),
            'background_color' => sanitize_hex_color($data['background_color']),
            'position' => sanitize_text_field($data['position']),
            'size' => sanitize_text_field($data['size']),
            'auto_open' => isset($data['auto_open']) ? 1 : 0,
            'auto_open_delay' => intval($data['auto_open_delay']),
            'show_avatar' => isset($data['show_avatar']) ? 1 : 0,
            'avatar_url' => esc_url_raw($data['avatar_url']),
            'max_tokens' => intval($data['max_tokens']),
            'temperature' => floatval($data['temperature']),
        )),
        'knowledge_base' => maybe_serialize($data['knowledge_base']),
        'tools' => maybe_serialize($data['tools']),
        // 'php_functions' => maybe_serialize($data['php_functions']),
        'updated_at' => current_time('mysql')
    );
    
    if ($chatbot_id > 0) {
        // Update existing chatbot
        $result = $wpdb->update($table_name, $chatbot_data, array('id' => $chatbot_id));
        return $result !== false ? $chatbot_id : false;
    } else {
        // Insert new chatbot
        $chatbot_data['created_at'] = current_time('mysql');
        $result = $wpdb->insert($table_name, $chatbot_data);
        return $result ? $wpdb->insert_id : false;
    }
}

// Delete chatbot
function rapidtextai_chatbots_delete($chatbot_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rapidtextai_chatbots';
    
    $wpdb->delete($table_name, array('id' => $chatbot_id));
}

// Get default chatbot settings
function rapidtextai_get_default_chatbot_settings() {
    return array(
        'name' => '',
        'description' => '',
        'model' => 'gpt-3.5-turbo',
        'theme' => 'modern',
        'status' => 'active',
        'system_message' => 'You are a helpful AI assistant.',
        'welcome_message' => 'Hello! How can I help you today?',
        'settings' => array(
            'primary_color' => '#007cba',
            'secondary_color' => '#005177',
            'text_color' => '#ffffff',
            'background_color' => '#f9f9f9',
            'position' => 'bottom-right',
            'size' => 'medium',
            'auto_open' => false,
            'auto_open_delay' => 3000,
            'show_avatar' => true,
            'avatar_url' => '',
            'max_tokens' => 2000,
            'temperature' => 0.7,
        ),
        'knowledge_base' => array(),
        'tools' => array(),
        // 'php_functions' => array(),
    );
}

// Create chatbots table
function rapidtextai_create_chatbots_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rapidtextai_chatbots';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text,
        model varchar(100) NOT NULL,
        theme varchar(50) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        system_message text,
        welcome_message text,
        settings longtext,
        knowledge_base longtext,
        tools longtext,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Initialize chatbots table on plugin activation
register_activation_hook(RAPIDTEXTAI_PLUGIN_DIR . '../rapidtext-ai-text-block.php', 'rapidtextai_create_chatbots_table');

// AJAX handlers
add_action('wp_ajax_rapidtextai_get_models', 'rapidtextai_get_models_callback');
add_action('wp_ajax_rapidtextai_chatbot_message', 'rapidtextai_chatbot_message_callback');
add_action('wp_ajax_nopriv_rapidtextai_chatbot_message', 'rapidtextai_chatbot_message_callback');
// add_action('wp_ajax_rapidtextai_test_php_function', 'rapidtextai_test_php_function_callback');

// Get available models
function rapidtextai_get_models_callback() {
    if (!wp_verify_nonce($_POST['nonce'], 'rapidtextai_chatbots_nonce')) {
        wp_send_json_error('Nonce verification failed');
    }
    
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        wp_send_json_error('API key not configured');
    }
    
    $response = wp_remote_get("https://app.rapidtextai.com/openai/v1/models?gigsixkey=" . urlencode($api_key));
    
    if (is_wp_error($response)) {
        wp_send_json_error('Failed to fetch models');
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['data'])) {
        wp_send_json_success($data['data']);
    } else {
        wp_send_json_error('Invalid response format');
    }
}

// Handle chatbot messages
function rapidtextai_chatbot_message_callback() {
    if (!wp_verify_nonce($_POST['nonce'], 'rapidtextai_chatbots_frontend_nonce')) {
        wp_send_json_error('Nonce verification failed');
    }
    
    $chatbot_id = intval($_POST['chatbot_id']);
    $message = sanitize_text_field($_POST['message']);
    $conversation_history = isset($_POST['history']) ? $_POST['history'] : array();
    
    $chatbot = rapidtextai_get_chatbot($chatbot_id);
    if (!$chatbot || $chatbot['status'] !== 'active') {
        wp_send_json_error('Chatbot not found or inactive');
    }
    
    // Process the message and get response
    $response = rapidtextai_process_chatbot_message($chatbot, $message, $conversation_history);
    
    wp_send_json_success(array('response' => $response));
}

// Process chatbot message
function rapidtextai_process_chatbot_message($chatbot, $message, $history) {
    $api_key = get_option('rapidtextai_api_key', '');
    if (empty($api_key)) {
        return 'API key not configured.';
    }
    
    // Build messages array for API
    $messages = array();
    
    // Add system message
    if (!empty($chatbot['system_message'])) {
        $messages[] = array(
            'role' => 'system',
            'content' => $chatbot['system_message']
        );
    }
    
    // Add knowledge base context
    if (!empty($chatbot['knowledge_base'])) {
        $kb_context = rapidtextai_get_relevant_knowledge($chatbot['knowledge_base'], $message);
        if ($kb_context) {
            $messages[] = array(
                'role' => 'system',
                'content' => "Knowledge Base Context:\n" . $kb_context
            );
        }
    }
    
    // Add conversation history
    foreach ($history as $msg) {
        $messages[] = array(
            'role' => $msg['role'],
            'content' => $msg['content']
        );
    }
    
    // Add current user message
    $messages[] = array(
        'role' => 'user',
        'content' => $message
    );
    
    // Check if tools are available
    $tools = array();
    if (!empty($chatbot['tools'])) {
        foreach ($chatbot['tools'] as $tool) {
            $tools[] = array(
                'type' => 'function',
                'function' => array(
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => json_decode($tool['parameters'], true)
                )
            );
        }
    }
    
    // Add PHP functions as tools
    // if (!empty($chatbot['php_functions'])) {
    //     foreach ($chatbot['php_functions'] as $php_func) {
    //         $tools[] = array(
    //             'type' => 'function',
    //             'function' => array(
    //                 'name' => $php_func['name'],
    //                 'description' => $php_func['description'],
    //                 'parameters' => json_decode($php_func['parameters'], true)
    //             )
    //         );
    //     }
    // }
    
    // Prepare API request
    $api_data = array(
        'model' => $chatbot['model'],
        'messages' => $messages,
        'max_tokens' => $chatbot['settings']['max_tokens'],
        'temperature' => $chatbot['settings']['temperature']
    );
    
    if (!empty($tools)) {
        $api_data['tools'] = $tools;
        $api_data['tool_choice'] = 'auto';
    }
    //print_r($api_data);
    $response = wp_remote_post("https://app.rapidtextai.com/openai/v1/chat/completions?gigsixkey=" . urlencode($api_key), array(
        'headers' => array('Content-Type' => 'application/json'),
        'body' => json_encode($api_data),
        'timeout' => 30
    ));
    // print_r($response);
    // ini_set('display_errors', 1);
    // error_reporting(E_ALL);
    if (is_wp_error($response)) {
        return 'Sorry, I encountered an error. Please try again.';
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (isset($data['choices'][0]['message'])) {
        $assistant_message = $data['choices'][0]['message'];
        
        // Check if there are tool calls
        if (isset($assistant_message['tool_calls'])) {
            return rapidtextai_handle_tool_calls($assistant_message['tool_calls'], $chatbot);
        }
        
        return $assistant_message['content'];
    }
    
    return 'Sorry, I couldn\'t understand that. Please try again.';
}

// Handle tool calls
function rapidtextai_handle_tool_calls($tool_calls, $chatbot) {
    $results = array();
    
    foreach ($tool_calls as $tool_call) {
        $function_name = $tool_call['function']['name'];
        $arguments = json_decode($tool_call['function']['arguments'], true);
        
        // Check if it's a PHP function first
        // $php_function = null;
        // if (!empty($chatbot['php_functions'])) {
        //     foreach ($chatbot['php_functions'] as $php_func) {
        //         if ($php_func['name'] === $function_name) {
        //             $php_function = $php_func;
        //             break;
        //         }
        //     }
        // }
        
        // Check if it's an external tool
        $external_tool = null;
        if (!$php_function && !empty($chatbot['tools'])) {
            foreach ($chatbot['tools'] as $tool) {
                if ($tool['name'] === $function_name) {
                    $external_tool = $tool;
                    break;
                }
            }
        }
        
        if ($external_tool) {
            $result = rapidtextai_execute_external_tool($external_tool, $arguments);
            $results[] = $result;
        } else {
            $results[] = "Tool '{$function_name}' not found or not configured.";
        }
    }
    
    return implode("\n\n", $results);
}

// Execute PHP function
// function rapidtextai_execute_php_function($php_function, $arguments) {
//     // Security check - only allow whitelisted functions
//     // $allowed_functions = apply_filters('rapidtextai_allowed_php_functions', array(
//     //     'create_ticket',
//     //     'book_appointment',
//     //     'get_product_info',
//     //     'send_email'
//     // ));
    
//     // if (!in_array($php_function['name'], $allowed_functions)) {
//     //     return 'Function not allowed for security reasons.';
//     // }
    
//     try {
//         // Execute the PHP code safely
//         $code = $php_function['code'];
        
//         // Create a safe environment
//         $safe_globals = array(
//             'arguments' => $arguments,
//             'wpdb' => $GLOBALS['wpdb'],
//             'wp_insert_post' => 'wp_insert_post',
//             'wp_mail' => 'wp_mail'
//         );
        
//         // Use eval with extreme caution - in production, consider a sandboxed environment
//         $result = eval($code);
        
//         return $result ?: 'Function executed successfully.';
        
//     } catch (Exception $e) {
//         return 'Error executing function: ' . $e->getMessage();
//     }
// }

// write function for rapidtextai_execute_external_tool
function rapidtextai_execute_external_tool($external_tool, $arguments) {
    $api_url = $external_tool['api_url'];
    $method = strtoupper($external_tool['method'] ?? 'POST');
    $headers = $external_tool['headers'] ?? array();
    
    // Prepare headers
    $wp_headers = array();
    if (!empty($headers)) {
        foreach ($headers as $header) {
            if (!empty($header['key']) && !empty($header['value'])) {
                $wp_headers[$header['key']] = $header['value'];
            }
        }
    }
    
    // Set default content type if not specified
    if (!isset($wp_headers['Content-Type'])) {
        $wp_headers['Content-Type'] = 'application/json';
    }
    
    // Prepare request arguments
    $request_args = array(
        'method' => $method,
        'headers' => $wp_headers,
        'timeout' => 30
    );
    
    // Add body for POST/PUT/PATCH requests
    if (in_array($method, array('POST', 'PUT', 'PATCH'))) {
        if ($wp_headers['Content-Type'] === 'application/json') {
            $request_args['body'] = json_encode($arguments);
        } else {
            $request_args['body'] = $arguments;
        }
    } elseif ($method === 'GET' && !empty($arguments)) {
        // Add query parameters for GET requests
        $api_url = add_query_arg($arguments, $api_url);
    }
    
    try {
        $response = wp_remote_request($api_url, $request_args);
        
        if (is_wp_error($response)) {
            return 'Error calling external tool: ' . $response->get_error_message();
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            // Try to decode JSON response
            $decoded_response = json_decode($response_body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                // Return formatted response or specific field if configured
                if (!empty($external_tool['response_field'])) {
                    // convert this current.condition.text to ['current']['condition']['text']
                    $fields = explode('.', $external_tool['response_field']);
                    $value = $decoded_response;
                    foreach ($fields as $field) {
                        if (isset($value[$field])) {
                            $value = $value[$field];
                        } else {
                            $value = null;
                            break;
                        }
                    }

                    return $value ?? $response_body;
                }
                return is_array($decoded_response) ? json_encode($decoded_response, JSON_PRETTY_PRINT) : $response_body;
            }
            return $response_body;
        } else {
            return "External tool returned error code {$response_code}: {$response_body}";
        }
        
    } catch (Exception $e) {
        return 'Error executing external tool: ' . $e->getMessage();
    }
}

// Get relevant knowledge from knowledge base
function rapidtextai_get_relevant_knowledge($knowledge_base, $query) {
    if (empty($knowledge_base)) {
        return '';
    }
    
    $relevant_docs = array();
    $query_lower = strtolower($query);
    
    foreach ($knowledge_base as $doc) {
        $title_lower = strtolower($doc['title']);
        $content_lower = strtolower($doc['content']);
        
        // Simple keyword matching - can be improved with vector similarity
        if (strpos($title_lower, $query_lower) !== false || 
            strpos($content_lower, $query_lower) !== false) {
            $relevant_docs[] = $doc['title'] . ': ' . $doc['content'];
        }
    }
    
    return implode("\n\n", array_slice($relevant_docs, 0, 3)); // Limit to top 3 matches
}

// Test PHP function
// function rapidtextai_test_php_function_callback() {
//     if (!wp_verify_nonce($_POST['nonce'], 'rapidtextai_chatbots_nonce')) {
//         wp_send_json_error('Nonce verification failed');
//     }
    
//     $code = stripslashes($_POST['code']);
//     $test_args = json_decode(stripslashes($_POST['test_args']), true);
    
//     try {
//         // Test the function in a safe environment
//         $arguments = $test_args;
//         ob_start();
//         $result = eval($code);
//         $output = ob_get_clean();
        
//         wp_send_json_success(array(
//             'result' => $result,
//             'output' => $output
//         ));
        
//     } catch (Exception $e) {
//         wp_send_json_error('Error: ' . $e->getMessage());
//     }
// }

// Shortcode for displaying chatbot
add_shortcode('rapidtextai_chatbot', 'rapidtextai_chatbot_shortcode');

function rapidtextai_chatbot_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts);
    
    $chatbot_id = intval($atts['id']);
    $chatbot = rapidtextai_get_chatbot($chatbot_id);

    if (!$chatbot || $chatbot['status'] !== 'active') {
        return '';
    }
    
    // Generate unique ID for this chatbot instance
    $instance_id = 'rapidtextai-chatbot-' . $chatbot_id . '-' . uniqid();
    
    ob_start();
    include(plugin_dir_path(__FILE__) . 'views/chatbot-widget.php');
    return ob_get_clean();
}