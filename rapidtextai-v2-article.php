<?php
/**
 * RapidTextAI article engine v2 bridge.
 *
 * Additive REST routes under `rapidtextai/v1/v2/*` for the Pro article mode:
 *   GET  /v2/capabilities  - is the v2 engine available on the RapidTextAI server?
 *   GET  /v2/site-links    - published posts the engine may link to internally
 *   POST /v2/article       - generate (and optionally save as a draft)
 *
 * Nothing legacy calls these routes and they call nothing legacy. If the server has no v2
 * engine, capabilities reports `available: false` and the UI keeps using the existing
 * generator, so older and newer plugin/server combinations keep working.
 *
 * @package RapidTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RAPIDTEXTAI_V2_BASE', 'https://app.rapidtextai.com/openai/v2/article/' );

/**
 * Capabilities of the v2 engine, cached briefly. Returns array|null (null = unavailable).
 */
function rapidtextai_v2_capabilities( $api_key ) {
    $cache_key = 'rtai_v2_caps_' . md5( $api_key );
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return is_array( $cached ) ? $cached : null;
    }

    $response = wp_remote_get(
        RAPIDTEXTAI_V2_BASE . 'capabilities?gigsixkey=' . rawurlencode( $api_key ),
        array( 'timeout' => 10 )
    );
    $caps = null;
    if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( is_array( $data ) && ! empty( $data['version'] ) ) {
            $caps = $data;
        }
    }
    // Cache misses for a short time so an old server is not hit on every page load.
    set_transient( $cache_key, $caps ? $caps : 'none', $caps ? 10 * MINUTE_IN_SECONDS : 2 * MINUTE_IN_SECONDS );
    return $caps;
}

/**
 * Build the engine request from untrusted REST params. Only known fields are forwarded.
 */
function rapidtextai_v2_build_request( WP_REST_Request $r ) {
    $lines = function ( $v ) {
        $out = array();
        foreach ( preg_split( '/\r\n|\r|\n/', (string) $v ) as $l ) {
            $l = trim( sanitize_text_field( $l ) );
            if ( '' !== $l ) {
                $out[] = $l;
            }
        }
        return array_slice( $out, 0, 40 );
    };
    $csv = function ( $v ) {
        $out = array();
        foreach ( explode( ',', (string) $v ) as $w ) {
            $w = trim( sanitize_text_field( $w ) );
            if ( '' !== $w ) {
                $out[] = $w;
            }
        }
        return array_slice( $out, 0, 40 );
    };

    $req = array(
        'topic'           => sanitize_text_field( (string) $r->get_param( 'topic' ) ),
        'keyword'         => sanitize_text_field( (string) $r->get_param( 'keyword' ) ),
        'model'           => sanitize_text_field( (string) $r->get_param( 'model' ) ),
        'word_count'      => max( 300, min( 4000, (int) ( $r->get_param( 'word_count' ) ?: 1500 ) ) ),
        'tone'            => sanitize_text_field( (string) $r->get_param( 'tone' ) ),
        'language'        => sanitize_text_field( (string) ( $r->get_param( 'language' ) ?: 'English' ) ),
        'audience'        => sanitize_text_field( (string) $r->get_param( 'audience' ) ),
        'article_type'    => sanitize_key( (string) ( $r->get_param( 'article_type' ) ?: 'guide' ) ),
        'cta'             => sanitize_text_field( (string) $r->get_param( 'cta' ) ),
        'include_faq'     => (bool) $r->get_param( 'include_faq' ),
        'include_sources' => (bool) $r->get_param( 'include_sources' ),
        'research'        => (bool) $r->get_param( 'research' ),
        'site_url'        => esc_url_raw( home_url() ),
        'banned_words'    => $csv( $r->get_param( 'banned_words' ) ),
        'stream'          => false,
    );

    $voice_desc   = sanitize_textarea_field( (string) $r->get_param( 'voice_description' ) );
    $voice_sample = sanitize_textarea_field( (string) $r->get_param( 'voice_sample' ) );
    if ( '' !== $voice_desc || '' !== $voice_sample ) {
        $req['brand_voice'] = array(
            'description' => mb_substr( $voice_desc, 0, 1000 ),
            'sample_text' => mb_substr( $voice_sample, 0, 4000 ),
        );
    }

    $facts   = $lines( $r->get_param( 'facts' ) );
    $product = sanitize_textarea_field( (string) $r->get_param( 'product_info' ) );
    if ( $facts || '' !== $product ) {
        $req['knowledge'] = array(
            'facts'        => $facts,
            'product_info' => mb_substr( $product, 0, 4000 ),
        );
    }

    $links = array();
    $given = $r->get_param( 'internal_links' );
    if ( is_array( $given ) ) {
        foreach ( array_slice( $given, 0, 30 ) as $l ) {
            $title = isset( $l['title'] ) ? sanitize_text_field( $l['title'] ) : '';
            $url   = isset( $l['url'] ) ? esc_url_raw( $l['url'] ) : '';
            if ( $title && $url ) {
                $links[] = array( 'title' => $title, 'url' => $url );
            }
        }
    }
    if ( $links ) {
        $req['internal_links'] = $links;
    }

    return array_filter( $req, function ( $v ) {
        return ! ( is_string( $v ) && '' === $v );
    } );
}

add_action( 'rest_api_init', function () {
    $ns = 'rapidtextai/v1';

    register_rest_route( $ns, '/v2/capabilities', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function () {
            $api_key = get_option( 'rapidtextai_api_key', '' );
            if ( empty( $api_key ) ) {
                return rest_ensure_response( array( 'available' => false, 'reason' => 'no_key' ) );
            }
            $caps = rapidtextai_v2_capabilities( $api_key );
            if ( ! $caps ) {
                return rest_ensure_response( array( 'available' => false, 'reason' => 'server' ) );
            }
            $caps['available'] = true;
            return rest_ensure_response( $caps );
        },
    ) );

    register_rest_route( $ns, '/v2/site-links', array(
        'methods'             => 'GET',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $args = array(
                'post_type'      => array( 'post', 'page' ),
                'post_status'    => 'publish',
                'posts_per_page' => 20,
                'orderby'        => 'date',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            );
            $search = sanitize_text_field( (string) $request->get_param( 'search' ) );
            if ( '' !== $search ) {
                $args['s'] = $search;
            }
            $out = array();
            foreach ( get_posts( $args ) as $p ) {
                $out[] = array(
                    'title' => html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' ),
                    'url'   => get_permalink( $p ),
                );
            }
            return rest_ensure_response( $out );
        },
    ) );

    register_rest_route( $ns, '/v2/article', array(
        'methods'             => 'POST',
        'permission_callback' => 'rapidtextai_rest_permission',
        'callback'            => function ( WP_REST_Request $request ) {
            $api_key = get_option( 'rapidtextai_api_key', '' );
            if ( empty( $api_key ) ) {
                return new WP_Error( 'no_key', 'API key is not configured.', array( 'status' => 400 ) );
            }
            $body = rapidtextai_v2_build_request( $request );
            if ( empty( $body['topic'] ) ) {
                return new WP_Error( 'no_topic', 'A topic is required.', array( 'status' => 400 ) );
            }

            // A full article takes 1-3 minutes.
            if ( function_exists( 'set_time_limit' ) ) {
                @set_time_limit( 330 );
            }
            $response = wp_remote_post(
                RAPIDTEXTAI_V2_BASE . 'generate?gigsixkey=' . rawurlencode( $api_key ),
                array(
                    'headers' => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key ),
                    'body'    => wp_json_encode( $body ),
                    'timeout' => 320,
                )
            );
            if ( is_wp_error( $response ) ) {
                return new WP_Error( 'request_failed', 'Could not reach RapidTextAI: ' . $response->get_error_message(), array( 'status' => 502 ) );
            }
            $code = (int) wp_remote_retrieve_response_code( $response );
            $data = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( 200 !== $code || ! is_array( $data ) || empty( $data['body_html'] ) ) {
                $msg = is_array( $data ) && ! empty( $data['error']['message'] ) ? $data['error']['message'] : 'Generation failed (HTTP ' . $code . ').';
                return new WP_Error( 'generation_failed', sanitize_text_field( $msg ), array( 'status' => 429 === $code ? 429 : 502 ) );
            }

            // The engine output is derived from web research: strip anything WordPress would not allow in a post.
            $data['body_html'] = wp_kses_post( $data['body_html'] );

            $result = array(
                'title'            => sanitize_text_field( $data['title'] ?? '' ),
                'slug'             => sanitize_title( $data['slug'] ?? '' ),
                'meta_description' => sanitize_text_field( $data['meta_description'] ?? '' ),
                'body_html'        => $data['body_html'],
                'word_count'       => (int) ( $data['word_count'] ?? 0 ),
                'tags'             => array_map( 'sanitize_text_field', (array) ( $data['tags'] ?? array() ) ),
                'quality'          => $data['quality'] ?? new stdClass(),
                'sources'          => $data['sources'] ?? array(),
                'image_suggestions' => $data['image_suggestions'] ?? array(),
                'warnings'         => array_map( 'sanitize_text_field', (array) ( $data['warnings'] ?? array() ) ),
            );

            if ( $request->get_param( 'save_draft' ) && current_user_can( 'edit_posts' ) ) {
                $post_id = wp_insert_post( array(
                    'post_title'   => $result['title'] ?: $body['topic'],
                    'post_name'    => $result['slug'],
                    'post_content' => $result['body_html'],
                    'post_excerpt' => $result['meta_description'],
                    'post_status'  => 'draft',
                    'post_type'    => 'post',
                    'tags_input'   => $result['tags'],
                ), true );
                if ( ! is_wp_error( $post_id ) ) {
                    $result['post_id']  = (int) $post_id;
                    $result['edit_url'] = get_edit_post_link( $post_id, 'raw' );
                }
            }

            return rest_ensure_response( $result );
        },
    ) );
} );
