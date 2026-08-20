<?php
/**
 * RapidTextAI REST API endpoints for the React-powered admin pages.
 *
 * Registers a `rapidtextai/v1` namespace used by the React app (app.js) to:
 *   - Read/save the RapidTextAI API key
 *   - Proxy account + model lookups to app.rapidtextai.com
 *   - CRUD auto-blogging campaigns
 *   - Improve topics / read logs
 *   - CRUD AI chatbots
 *
 * @package RapidTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders the React app shell (root div + localized config).
 *
 * The stylesheet and module script are enqueued in the document <head>
 * and footer respectively (see rapidtextai_enqueue_admin_app), so the
 * React bundle is not emitted as stray tags inside the page content.
 *
 * @param array $config Page-level configuration passed to the React app.
 */
function rapidtextai_react_app_root( $config = array() ) {
    $config = array_merge(
        array(
            'page'       => 'settings',
            'rest_url'   => esc_url_raw( rest_url( 'rapidtextai/v1' ) ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'app_url'    => 'https://app.rapidtextai.com',
            'data'       => new stdClass(),
        ),
        $config
    );
    ?>
    <div id="rapidtextai-app"></div>
    <script>
    window.rapidtextai_app = <?php echo wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
    </script>
    <?php
}

/**
 * Enqueue the compiled React app (app.css + app.js) on the three React pages.
 *
 * app.js is registered as an ES module via the script_loader_tag filter so
 * it loads exactly like the already-working meta box bundle.
 */
add_action( 'admin_enqueue_scripts', 'rapidtextai_enqueue_admin_app' );
function rapidtextai_enqueue_admin_app( $hook ) {
    // The three React pages share the "rapidtextai" slug; matching on the
    // substring avoids depending on the exact toplevel_page_ / *_page_ suffix.
    if ( false === strpos( (string) $hook, 'rapidtextai' ) ) {
        return;
    }

    wp_enqueue_style(
        'rapidtextai-app',
        RAPIDTEXTAI_PLUGIN_URL . 'assets/css/app.css',
        array(),
        '1.3.0'
    );

    wp_register_script(
        'rapidtextai-app',
        RAPIDTEXTAI_PLUGIN_URL . 'assets/js/app.js',
        array(),
        '1.3.0',
        true
    );
    wp_enqueue_script( 'rapidtextai-app' );
}

/**
 * Load the compiled app.js as an ES module (required by the Vite build).
 */
add_filter( 'script_loader_tag', 'rapidtextai_app_script_tag', 10, 3 );
function rapidtextai_app_script_tag( $tag, $handle, $src ) {
    if ( 'rapidtextai-app' === $handle ) {
        return '<script type="module" crossorigin src="' . esc_url( $src ) . '"></script>' . "\n";
    }
    return $tag;
}

/**
 * Shared permission callback — only users who can manage options may use these routes.
 */
function rapidtextai_rest_permission() {
    return current_user_can( 'manage_options' );
}

/**
 * Register all REST routes.
 */
add_action( 'rest_api_init', function () {
    $ns = 'rapidtextai/v1';

    // ─────────────────────────────────────────────────────────── Settings
    register_rest_route( $ns, '/settings', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            $api_key = get_option( 'rapidtextai_api_key', '' );
            return rest_ensure_response( array(
                'api_key'     => $api_key,
                'api_key_set' => ! empty( $api_key ),
            ) );
        },
    ) );

    register_rest_route( $ns, '/settings/api-key', array(
        'methods'             => 'POST',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $api_key = sanitize_text_field( (string) $request->get_param( 'api_key' ) );
            if ( '' === $api_key ) {
                return new WP_Error( 'missing_key', 'API key is required.', array( 'status' => 400 ) );
            }
            update_option( 'rapidtextai_api_key', $api_key );
            return rest_ensure_response( array( 'message' => 'API Key saved successfully.' ) );
        },
    ) );

    // ─────────────────────────────────────────────────────────── Account proxy
    register_rest_route( $ns, '/account', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            $api_key = get_option( 'rapidtextai_api_key', '' );
            if ( empty( $api_key ) ) {
                return new WP_Error( 'no_key', 'API key is not configured.', array( 'status' => 400 ) );
            }

            $url      = 'https://app.rapidtextai.com/api.php?gigsixkey=' . urlencode( $api_key );
            $response = wp_remote_get( $url, array( 'timeout' => 30, 'sslverify' => false ) );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'request_failed', $response->get_error_message(), array( 'status' => 502 ) );
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                return new WP_Error( 'invalid_response', 'Invalid response from RapidTextAI.', array( 'status' => 502 ) );
            }

            return rest_ensure_response( $data );
        },
    ) );

    // ─────────────────────────────────────────────────────────── Options (authors + categories)
    register_rest_route( $ns, '/options', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            $authors    = get_users( array(
                'role__in' => array( 'administrator', 'editor', 'author' ),
                'fields'   => array( 'ID', 'display_name' ),
            ) );
            $categories = get_categories( array( 'hide_empty' => 0 ) );

            $author_list = array_map( function ( $u ) {
                return array( 'id' => (int) $u->ID, 'name' => $u->display_name );
            }, $authors );

            $category_list = array_map( function ( $c ) {
                return array( 'id' => (int) $c->term_id, 'name' => $c->name );
            }, $categories );

            return rest_ensure_response( array(
                'authors'    => array_values( $author_list ),
                'categories' => array_values( $category_list ),
            ) );
        },
    ) );

    // ─────────────────────────────────────────────────────────── Models proxy (chatbots)
    register_rest_route( $ns, '/models', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            $api_key = get_option( 'rapidtextai_api_key', '' );
            if ( empty( $api_key ) ) {
                return new WP_Error( 'no_key', 'API key is not configured.', array( 'status' => 400 ) );
            }

            $response = wp_remote_get( 'https://app.rapidtextai.com/openai/v1/models?gigsixkey=' . urlencode( $api_key ), array( 'timeout' => 30 ) );
            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'request_failed', 'Failed to fetch models.', array( 'status' => 502 ) );
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            if ( isset( $data['data'] ) ) {
                return rest_ensure_response( $data['data'] );
            }

            return new WP_Error( 'invalid_response', 'Invalid response format.', array( 'status' => 502 ) );
        },
    ) );

    // ─────────────────────────────────────────────────────────── Campaigns
    register_rest_route( $ns, '/campaigns', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            $campaigns = get_option( 'rapidtextai_auto_blogging_campaigns', array() );
            $out       = array();

            foreach ( $campaigns as $id => $campaign ) {
                $topics             = array_filter( array_map( 'trim', explode( "\n", isset( $campaign['topics'] ) ? $campaign['topics'] : '' ) ) );
                $campaign['topics_count'] = count( $topics );
                $campaign['next_run']     = wp_next_scheduled( 'rapidtextai_auto_blogging_cron_' . $id );
                $out[]                     = $campaign;
            }

            return rest_ensure_response( $out );
        },
    ) );

    register_rest_route( $ns, '/campaigns/(?P<id>[a-zA-Z0-9_\-]+)', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $id        = sanitize_text_field( $request['id'] );
            $campaigns = get_option( 'rapidtextai_auto_blogging_campaigns', array() );

            if ( ! isset( $campaigns[ $id ] ) ) {
                return new WP_Error( 'not_found', 'Campaign not found.', array( 'status' => 404 ) );
            }

            return rest_ensure_response( $campaigns[ $id ] );
        },
    ) );

    register_rest_route( $ns, '/campaigns', array(
        'methods'             => 'POST',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $params = $request->get_json_params();
            if ( ! is_array( $params ) ) {
                $params = $request->get_params();
            }

            $result = rapidtextai_rest_save_campaign( $params );
            if ( is_wp_error( $result ) ) {
                return $result;
            }

            return rest_ensure_response( $result );
        },
    ) );

    register_rest_route( $ns, '/campaigns/(?P<id>[a-zA-Z0-9_\-]+)/toggle', array(
        'methods'             => 'POST',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $id        = sanitize_text_field( $request['id'] );
            $campaigns = get_option( 'rapidtextai_auto_blogging_campaigns', array() );

            if ( ! isset( $campaigns[ $id ] ) ) {
                return new WP_Error( 'not_found', 'Campaign not found.', array( 'status' => 404 ) );
            }

            $campaigns[ $id ]['enabled'] = empty( $campaigns[ $id ]['enabled'] ) ? 1 : 0;
            update_option( 'rapidtextai_auto_blogging_campaigns', $campaigns );

            if ( $campaigns[ $id ]['enabled'] ) {
                rapidtextai_schedule_campaign_cron( $id, $campaigns[ $id ] );
            } else {
                wp_clear_scheduled_hook( 'rapidtextai_auto_blogging_cron_' . $id );
            }

            return rest_ensure_response( array(
                'enabled' => (bool) $campaigns[ $id ]['enabled'],
                'message' => $campaigns[ $id ]['enabled'] ? 'Campaign enabled successfully.' : 'Campaign disabled successfully.',
            ) );
        },
    ) );

    register_rest_route( $ns, '/campaigns/(?P<id>[a-zA-Z0-9_\-]+)', array(
        'methods'             => 'DELETE',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $id        = sanitize_text_field( $request['id'] );
            $campaigns = get_option( 'rapidtextai_auto_blogging_campaigns', array() );

            if ( ! isset( $campaigns[ $id ] ) ) {
                return new WP_Error( 'not_found', 'Campaign not found.', array( 'status' => 404 ) );
            }

            wp_clear_scheduled_hook( 'rapidtextai_auto_blogging_cron_' . $id );
            unset( $campaigns[ $id ] );
            update_option( 'rapidtextai_auto_blogging_campaigns', $campaigns );

            return rest_ensure_response( array( 'message' => 'Campaign deleted successfully.' ) );
        },
    ) );

    register_rest_route( $ns, '/campaigns/(?P<id>[a-zA-Z0-9_\-]+)/generate', array(
        'methods'             => 'POST',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $id        = sanitize_text_field( $request['id'] );
            $campaigns = get_option( 'rapidtextai_auto_blogging_campaigns', array() );

            if ( ! isset( $campaigns[ $id ] ) ) {
                return new WP_Error( 'not_found', 'Campaign not found.', array( 'status' => 404 ) );
            }

            rapidtextai_generate_auto_blog_post( $id );

            return rest_ensure_response( array( 'message' => 'Post generation triggered for selected campaign.' ) );
        },
    ) );

    // ─────────────────────────────────────────────────────────── Improve topics
    register_rest_route( $ns, '/improve-topics', array(
        'methods'             => 'POST',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $topics = sanitize_textarea_field( (string) $request->get_param( 'topics' ) );

            $topics_array = array_filter( array_map( 'trim', explode( "\n", $topics ) ) );
            if ( empty( $topics_array ) ) {
                return new WP_Error( 'no_topics', 'No topics provided.', array( 'status' => 400 ) );
            }
            if ( count( $topics_array ) > 50 ) {
                $topics_array = array_slice( $topics_array, 0, 50 );
            }

            $api_key = get_option( 'rapidtextai_api_key', '' );
            if ( empty( $api_key ) ) {
                return new WP_Error( 'no_key', 'API key not found. Please set up your RapidTextAI authentication.', array( 'status' => 400 ) );
            }

            $prompt  = "I have a list of blog post topics that need improvement to make them more specific, engaging, and SEO-friendly. Please improve each of these topics:\n\n";
            $prompt .= implode( "\n", $topics_array );
            $prompt .= "\n\nIMPORTANT FORMATTING RULES:\n";
            $prompt .= "1. Each improved topic MUST be on a SINGLE LINE (no line breaks within a topic)\n";
            $prompt .= "2. Use semicolons (;) to separate different parts within the same topic\n";
            $prompt .= "3. Separate different topics with a blank line (double newline)\n";
            $prompt .= "4. Include: Topic, Keywords, Tone, Audience, Length, and CTA\n";
            $prompt .= "5. Use the same language as the input topics\n\n";
            $prompt .= "FORMAT EXAMPLE:\n";
            $prompt .= "Topic: Complete Guide to Sustainable Gardening for Beginners; Keywords: sustainable gardening, eco-friendly plants, organic fertilizer, water conservation, composting methods; Tone: friendly and informative; Audience: homeowners and gardening beginners; Length: 2500-3000 words; CTA: Download our free sustainable gardening checklist\n\n";
            $prompt .= "Topic: Another Topic Here; Keywords: keyword1, keyword2, keyword3; Tone: professional; Audience: business owners; Length: 1500-2000 words; CTA: Contact us for consultation\n\n";
            $prompt .= "Return each improved topic on ONE SINGLE LINE, separated by blank lines. Do NOT add line breaks within a topic.";

            $response = wp_remote_post( 'https://app.rapidtextai.com/openai/v1/chat/completions?gigsixkey=' . urlencode( $api_key ), array(
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'body'    => wp_json_encode( array(
                    'model'       => 'gpt-3.5-turbo',
                    'messages'    => array(
                        array( 'role' => 'system', 'content' => 'You are a helpful assistant that improves blog post topics.' ),
                        array( 'role' => 'user', 'content' => $prompt ),
                    ),
                    'max_tokens'  => 500,
                    'temperature' => 0.7,
                ) ),
                'timeout' => 30,
            ) );

            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'request_failed', 'Error connecting to RapidTextAI: ' . $response->get_error_message(), array( 'status' => 502 ) );
            }

            $http_code = wp_remote_retrieve_response_code( $response );
            $body      = wp_remote_retrieve_body( $response );

            if ( 200 !== $http_code ) {
                return new WP_Error( 'api_failed', 'API request failed with code ' . $http_code, array( 'status' => 502 ) );
            }

            $response_data = json_decode( $body, true );
            if ( ! isset( $response_data['choices'][0]['message']['content'] ) ) {
                return new WP_Error( 'invalid_response', 'Invalid response from API', array( 'status' => 502 ) );
            }

            $improved_topics = trim( $response_data['choices'][0]['message']['content'] );
            $improved_topics = preg_replace( '/^\d+\.\s+/m', '', $improved_topics );
            $improved_topics = preg_replace( '/^- /m', '', $improved_topics );

            return rest_ensure_response( array(
                'message'         => 'Topics improved successfully!',
                'improved_topics' => $improved_topics,
            ) );
        },
    ) );

    // ─────────────────────────────────────────────────────────── Logs
    register_rest_route( $ns, '/logs', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            return rest_ensure_response( array( 'logs' => rapidtextai_read_error_logs() ) );
        },
    ) );

    register_rest_route( $ns, '/logs', array(
        'methods'             => 'DELETE',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            if ( rapidtextai_clear_error_logs() ) {
                return rest_ensure_response( array( 'message' => 'Logs cleared successfully.' ) );
            }
            return new WP_Error( 'clear_failed', 'Failed to clear logs.', array( 'status' => 500 ) );
        },
    ) );

    // ─────────────────────────────────────────────────────────── Chatbots
    register_rest_route( $ns, '/chatbots', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            $rows = rapidtextai_get_all_chatbots();
            $out  = array();
            foreach ( $rows as $row ) {
                $out[] = rapidtextai_rest_prepare_chatbot( $row );
            }
            return rest_ensure_response( $out );
        },
    ) );

    register_rest_route( $ns, '/chatbots/(?P<id>\d+)', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $chatbot = rapidtextai_get_chatbot( (int) $request['id'] );
            if ( ! $chatbot ) {
                return new WP_Error( 'not_found', 'Chatbot not found.', array( 'status' => 404 ) );
            }
            return rest_ensure_response( rapidtextai_rest_prepare_chatbot( $chatbot ) );
        },
    ) );

    register_rest_route( $ns, '/chatbots', array(
        'methods'             => 'POST',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $chatbot_id = (int) $request->get_param( 'id' );
            $params     = $request->get_json_params();
            if ( ! is_array( $params ) ) {
                $params = $request->get_params();
            }

            // Accept either a nested `settings` object or flat fields.
            if ( isset( $params['settings'] ) && is_array( $params['settings'] ) ) {
                $params = array_merge( $params, $params['settings'] );
                unset( $params['settings'] );
            }

            $data = array(
                'name'             => isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '',
                'description'      => isset( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '',
                'model'            => isset( $params['model'] ) ? sanitize_text_field( $params['model'] ) : 'gpt-3.5-turbo',
                'theme'            => isset( $params['theme'] ) ? sanitize_text_field( $params['theme'] ) : 'modern',
                'status'           => isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'active',
                'system_message'   => isset( $params['system_message'] ) ? sanitize_textarea_field( $params['system_message'] ) : '',
                'welcome_message'  => isset( $params['welcome_message'] ) ? sanitize_textarea_field( $params['welcome_message'] ) : '',
                'primary_color'    => isset( $params['primary_color'] ) ? sanitize_hex_color( $params['primary_color'] ) : '#007cba',
                'secondary_color'  => isset( $params['secondary_color'] ) ? sanitize_hex_color( $params['secondary_color'] ) : '#005177',
                'text_color'       => isset( $params['text_color'] ) ? sanitize_hex_color( $params['text_color'] ) : '#ffffff',
                'background_color' => isset( $params['background_color'] ) ? sanitize_hex_color( $params['background_color'] ) : '#f9f9f9',
                'position'         => isset( $params['position'] ) ? sanitize_text_field( $params['position'] ) : 'bottom-right',
                'size'             => isset( $params['size'] ) ? sanitize_text_field( $params['size'] ) : 'medium',
                'auto_open'        => ! empty( $params['auto_open'] ) ? 1 : 0,
                'auto_open_delay'  => isset( $params['auto_open_delay'] ) ? intval( $params['auto_open_delay'] ) : 3000,
                'show_avatar'      => ! empty( $params['show_avatar'] ) ? 1 : 0,
                'avatar_url'       => isset( $params['avatar_url'] ) ? esc_url_raw( $params['avatar_url'] ) : '',
                'max_tokens'       => isset( $params['max_tokens'] ) ? intval( $params['max_tokens'] ) : 2000,
                'temperature'      => isset( $params['temperature'] ) ? floatval( $params['temperature'] ) : 0.7,
                'knowledge_base'   => isset( $params['knowledge_base'] ) && is_array( $params['knowledge_base'] ) ? $params['knowledge_base'] : array(),
                'tools'            => isset( $params['tools'] ) && is_array( $params['tools'] ) ? $params['tools'] : array(),
            );

            // rapidtextai_save_chatbot() treats checkbox presence via isset();
            // remove keys when false so they are stored as 0 correctly.
            if ( empty( $data['auto_open'] ) ) {
                unset( $data['auto_open'] );
            }
            if ( empty( $data['show_avatar'] ) ) {
                unset( $data['show_avatar'] );
            }

            $result = rapidtextai_save_chatbot( $data, $chatbot_id );
            if ( ! $result ) {
                return new WP_Error( 'save_failed', 'Error saving chatbot.', array( 'status' => 500 ) );
            }

            return rest_ensure_response( array(
                'id'      => (int) $result,
                'message' => 'Chatbot saved successfully!',
            ) );
        },
    ) );

    register_rest_route( $ns, '/chatbots/(?P<id>\d+)', array(
        'methods'             => 'DELETE',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            rapidtextai_chatbots_delete( (int) $request['id'] );
            return rest_ensure_response( array( 'message' => 'Chatbot deleted successfully.' ) );
        },
    ) );
} );

/**
 * Sanitize and persist a campaign (create or update) from request params.
 *
 * @param array $params Request parameters.
 * @return array|WP_Error
 */
function rapidtextai_rest_save_campaign( $params ) {
    $campaigns   = get_option( 'rapidtextai_auto_blogging_campaigns', array() );
    $existing_id = isset( $params['id'] ) && ! empty( $params['id'] ) ? sanitize_text_field( $params['id'] ) : '';
    $is_new      = empty( $existing_id );

    $campaign_id = $existing_id;
    if ( $is_new ) {
        $campaign_id = 'campaign_' . time();
        $i           = 0;
        while ( isset( $campaigns[ $campaign_id ] ) && $i < 100 ) {
            $campaign_id = 'campaign_' . time() . '_' . wp_rand( 100, 999 );
            $i++;
        }
    }

    $campaign = array(
        'id'                      => $campaign_id,
        'name'                    => isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '',
        'enabled'                 => ! empty( $params['enabled'] ) ? 1 : 0,
        'schedule'                => isset( $params['schedule'] ) ? sanitize_text_field( $params['schedule'] ) : 'daily',
        'post_status'             => isset( $params['post_status'] ) ? sanitize_text_field( $params['post_status'] ) : 'draft',
        'post_author'             => isset( $params['post_author'] ) ? intval( $params['post_author'] ) : get_current_user_id(),
        'topics'                  => isset( $params['topics'] ) ? sanitize_textarea_field( $params['topics'] ) : '',
        'model'                   => isset( $params['model'] ) ? sanitize_text_field( $params['model'] ) : 'gpt-3.5-turbo',
        'tone'                    => isset( $params['tone'] ) ? sanitize_text_field( $params['tone'] ) : 'informative',
        'post_category'           => isset( $params['post_category'] ) && is_array( $params['post_category'] ) ? array_map( 'intval', $params['post_category'] ) : array(),
        'generate_tags'           => ! empty( $params['generate_tags'] ) ? 1 : 0,
        'tags_count'              => isset( $params['tags_count'] ) ? intval( $params['tags_count'] ) : 5,
        'excerpt_length'          => isset( $params['excerpt_length'] ) ? intval( $params['excerpt_length'] ) : 55,
        'taxonomy_limit'          => isset( $params['taxonomy_limit'] ) ? intval( $params['taxonomy_limit'] ) : 5,
        'include_images'          => ! empty( $params['include_images'] ) ? 1 : 0,
        'include_featured_image'  => ! empty( $params['include_featured_image'] ) ? 1 : 0,
        'max_images'              => isset( $params['max_images'] ) ? intval( $params['max_images'] ) : 5,
        'enable_logging'          => ! empty( $params['enable_logging'] ) ? 1 : 0,
    );

    if ( $is_new ) {
        $campaign['created'] = current_time( 'mysql' );
    } elseif ( isset( $campaigns[ $campaign_id ]['created'] ) ) {
        $campaign['created'] = $campaigns[ $campaign_id ]['created'];
    }
    $campaign['updated'] = current_time( 'mysql' );

    $campaigns[ $campaign_id ] = $campaign;
    update_option( 'rapidtextai_auto_blogging_campaigns', $campaigns );

    wp_clear_scheduled_hook( 'rapidtextai_auto_blogging_cron_' . $campaign_id );
    if ( $campaign['enabled'] ) {
        rapidtextai_schedule_campaign_cron( $campaign_id, $campaign );
    }

    // Maintain backward compatibility with the old option.
    $all = get_option( 'rapidtextai_auto_blogging_campaigns', array() );
    if ( count( $all ) === 1 || false !== strpos( $campaign['name'], 'Default' ) ) {
        update_option( 'rapidtextai_auto_blogging', $campaign );
    }

    return array(
        'id'      => $campaign_id,
        'message' => $is_new ? 'Campaign created successfully.' : 'Campaign updated successfully.',
    );
}

/**
 * Normalize a chatbot row (decode JSON/serialized fields) for REST output.
 *
 * @param array $row Raw DB row.
 * @return array
 */
function rapidtextai_rest_prepare_chatbot( $row ) {
    // The single-chatbot path (rapidtextai_get_chatbot) already json_decodes
    // `settings`, while the list path (rapidtextai_get_all_chatbots) returns
    // the raw JSON string — so handle both cases here.
    $settings = isset( $row['settings'] ) ? $row['settings'] : array();
    if ( is_string( $settings ) ) {
        $decoded  = json_decode( $settings, true );
        $settings = is_array( $decoded ) ? $decoded : array();
    } elseif ( ! is_array( $settings ) ) {
        $settings = array();
    }
    $row['settings'] = $settings;

    $kb = maybe_unserialize( isset( $row['knowledge_base'] ) ? $row['knowledge_base'] : '' );
    $row['knowledge_base'] = is_array( $kb ) ? $kb : array();

    $tools = maybe_unserialize( isset( $row['tools'] ) ? $row['tools'] : '' );
    $row['tools'] = is_array( $tools ) ? $tools : array();

    $row['id']        = (int) $row['id'];
    $row['shortcode'] = '[rapidtextai_chatbot id="' . $row['id'] . '"]';

    return $row;
}
