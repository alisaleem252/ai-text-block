<?php
/**
 * RapidTextAI v2 suite: SEO tools, brands, dashboard, apply/restore and v2 (Pro) campaigns.
 *
 * Everything here talks to the RapidTextAI API with the site's saved key, so usage is counted
 * on the user's RapidTextAI request balance. Existing (classic) campaigns are not touched.
 *
 * @package rapidtextai
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const RAPIDTEXTAI_V2_CAMPAIGNS_OPTION = 'rapidtextai_v2_campaigns';
const RAPIDTEXTAI_V2_RUNS_OPTION      = 'rapidtextai_v2_runs';
const RAPIDTEXTAI_V2_CRON_HOOK        = 'rapidtextai_v2_campaign_tick';
const RAPIDTEXTAI_V2_RUN_NOW_HOOK     = 'rapidtextai_v2_campaign_run_now';

/* ───────────────────────────────────────────────────────── API helper */

/**
 * Call a RapidTextAI endpoint (path relative to /openai/) and return the decoded JSON.
 *
 * @param string     $method  GET|POST|PUT|DELETE.
 * @param string     $path    e.g. 'v2/seo/originality'.
 * @param array|null $body    JSON body.
 * @param int        $timeout Seconds.
 * @return array|WP_Error
 */
function rapidtextai_api_request( $method, $path, $body = null, $timeout = 60 ) {
    $api_key = get_option( 'rapidtextai_api_key', '' );
    if ( empty( $api_key ) ) {
        return new WP_Error( 'no_key', 'Add your RapidTextAI API key in Settings first.', array( 'status' => 400 ) );
    }
    $url  = rapidtextai_api_origin() . '/openai/' . ltrim( $path, '/' );
    $url .= ( false === strpos( $url, '?' ) ? '?' : '&' ) . 'gigsixkey=' . rawurlencode( $api_key );
    $args = array(
        'method'  => $method,
        'timeout' => $timeout,
        'headers' => array( 'Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key ),
    );
    if ( null !== $body ) {
        $args['body'] = wp_json_encode( $body );
    }
    $res = wp_remote_request( $url, $args );
    if ( is_wp_error( $res ) ) {
        return new WP_Error( 'request_failed', 'Could not reach RapidTextAI: ' . $res->get_error_message(), array( 'status' => 502 ) );
    }
    $code = (int) wp_remote_retrieve_response_code( $res );
    $data = json_decode( wp_remote_retrieve_body( $res ), true );
    if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
        $msg = 'RapidTextAI returned HTTP ' . $code . '.';
        if ( is_array( $data ) ) {
            if ( ! empty( $data['error']['message'] ) ) {
                $msg = $data['error']['message'];
            } elseif ( ! empty( $data['error'] ) && is_string( $data['error'] ) ) {
                $msg = $data['error'];
            } elseif ( ! empty( $data['message'] ) && is_string( $data['message'] ) ) {
                $msg = $data['message'];
            }
        }
        $status = in_array( $code, array( 400, 401, 402, 404, 413, 429, 503 ), true ) ? $code : 502;
        return new WP_Error( 'api_error', sanitize_text_field( $msg ), array( 'status' => $status ) );
    }
    return $data;
}

/* ───────────────────────────────────────────────────────── Preferences */

function rapidtextai_default_engine() {
    $e = get_option( 'rapidtextai_default_engine', 'v2' );
    return 'classic' === $e ? 'classic' : 'v2';
}

function rapidtextai_legacy_campaign_count() {
    $c = get_option( 'rapidtextai_auto_blogging_campaigns', array() );
    return is_array( $c ) ? count( $c ) : 0;
}

/* ───────────────────────────────────────────────────────── v2 campaigns: storage */

function rapidtextai_v2_get_campaigns() {
    $c = get_option( RAPIDTEXTAI_V2_CAMPAIGNS_OPTION, array() );
    return is_array( $c ) ? $c : array();
}

function rapidtextai_v2_frequencies() {
    return array( 'hourly', 'twicedaily', 'daily', 'weekly' );
}

/** Split the topics box into [ [topic, keyword], ... ]. Accepts "Topic | keyword" and legacy "Topic: x; Keywords: y". */
function rapidtextai_v2_parse_topics( $text ) {
    $out = array();
    foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
        $line = trim( $line );
        if ( '' === $line ) {
            continue;
        }
        $topic   = $line;
        $keyword = '';
        if ( preg_match( '/^topic\s*:\s*(.+?)(?:;|$)/i', $line, $m ) ) {
            $topic = trim( $m[1] );
            if ( preg_match( '/keywords?\s*:\s*(.+?)(?:;|$)/i', $line, $k ) ) {
                $keyword = trim( $k[1] );
            }
        } elseif ( false !== strpos( $line, '|' ) ) {
            list( $topic, $keyword ) = array_map( 'trim', explode( '|', $line, 2 ) );
        }
        if ( '' !== $topic ) {
            $out[] = array( 'topic' => $topic, 'keyword' => $keyword );
        }
    }
    return $out;
}

function rapidtextai_v2_sanitize_campaign( array $in, array $existing = array() ) {
    $bool = function ( $key, $default ) use ( $in, $existing ) {
        if ( array_key_exists( $key, $in ) ) {
            return (bool) $in[ $key ];
        }
        return array_key_exists( $key, $existing ) ? (bool) $existing[ $key ] : $default;
    };
    $str = function ( $key, $default = '' ) use ( $in, $existing ) {
        if ( array_key_exists( $key, $in ) ) {
            return sanitize_text_field( (string) $in[ $key ] );
        }
        return array_key_exists( $key, $existing ) ? (string) $existing[ $key ] : $default;
    };
    $int = function ( $key, $default, $min, $max ) use ( $in, $existing ) {
        $v = array_key_exists( $key, $in ) ? (int) $in[ $key ] : ( array_key_exists( $key, $existing ) ? (int) $existing[ $key ] : $default );
        return max( $min, min( $max, $v ) );
    };

    $freq = $str( 'frequency', 'daily' );
    if ( ! in_array( $freq, rapidtextai_v2_frequencies(), true ) ) {
        $freq = 'daily';
    }
    $status = $str( 'post_status', 'draft' );
    if ( ! in_array( $status, array( 'draft', 'pending', 'publish' ), true ) ) {
        $status = 'draft';
    }
    $topics = array_key_exists( 'topics', $in ) ? (string) $in['topics'] : (string) ( $existing['topics'] ?? '' );
    $topics = implode( "\n", array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', $topics ) ) );

    $categories = array_key_exists( 'categories', $in ) ? $in['categories'] : ( $existing['categories'] ?? array() );
    $categories = array_values( array_filter( array_map( 'intval', (array) $categories ) ) );

    return array(
        'id'              => $existing['id'] ?? '',
        'name'            => $str( 'name', 'Untitled campaign' ) ?: 'Untitled campaign',
        'enabled'         => $bool( 'enabled', false ),
        'topics'          => $topics,
        'cursor'          => (int) ( $existing['cursor'] ?? 0 ),
        'loop'            => $bool( 'loop', false ),
        'frequency'       => $freq,
        'post_status'     => $status,
        'post_type'       => 'post',
        'author'          => $int( 'author', get_current_user_id(), 0, PHP_INT_MAX ),
        'categories'      => $categories,
        'brand_id'        => $int( 'brand_id', 0, 0, PHP_INT_MAX ),
        'model'           => $str( 'model', '' ),
        'word_count'      => $int( 'word_count', 1500, 600, 5000 ),
        'article_type'    => $str( 'article_type', '' ),
        'tone'            => $str( 'tone', '' ),
        'audience'        => $str( 'audience', '' ),
        'language'        => $str( 'language', '' ),
        'cta'             => $str( 'cta', '' ),
        'research'        => $bool( 'research', true ),
        'include_faq'     => $bool( 'include_faq', true ),
        'include_sources' => $bool( 'include_sources', true ),
        'include_images'  => $bool( 'include_images', true ),
        'image_count'     => $int( 'image_count', 2, 0, 3 ),
        'featured_image'  => $bool( 'featured_image', true ),
        'internal_links'  => $bool( 'internal_links', true ),
        'created'         => $existing['created'] ?? time(),
        'last_run'        => $existing['last_run'] ?? 0,
        'last_status'     => $existing['last_status'] ?? '',
        'failures'        => (int) ( $existing['failures'] ?? 0 ),
        'migrated_from'   => $existing['migrated_from'] ?? '',
    );
}

function rapidtextai_v2_save_campaigns( array $campaigns ) {
    update_option( RAPIDTEXTAI_V2_CAMPAIGNS_OPTION, $campaigns, false );
}

function rapidtextai_v2_log_run( array $entry ) {
    $runs = get_option( RAPIDTEXTAI_V2_RUNS_OPTION, array() );
    if ( ! is_array( $runs ) ) {
        $runs = array();
    }
    array_unshift( $runs, array_merge( array( 'time' => time() ), $entry ) );
    update_option( RAPIDTEXTAI_V2_RUNS_OPTION, array_slice( $runs, 0, 60 ), false );
}

/* ───────────────────────────────────────────────────────── v2 campaigns: scheduling and run */

function rapidtextai_v2_sync_schedule( $id ) {
    wp_clear_scheduled_hook( RAPIDTEXTAI_V2_CRON_HOOK, array( $id ) );
    $campaigns = rapidtextai_v2_get_campaigns();
    if ( empty( $campaigns[ $id ] ) || empty( $campaigns[ $id ]['enabled'] ) ) {
        return;
    }
    $schedules = wp_get_schedules();
    $freq      = $campaigns[ $id ]['frequency'];
    $interval  = isset( $schedules[ $freq ]['interval'] ) ? (int) $schedules[ $freq ]['interval'] : DAY_IN_SECONDS;
    wp_schedule_event( time() + min( $interval, 10 * MINUTE_IN_SECONDS ), $freq, RAPIDTEXTAI_V2_CRON_HOOK, array( $id ) );
}

add_action( RAPIDTEXTAI_V2_CRON_HOOK, 'rapidtextai_v2_run_campaign' );
add_action( RAPIDTEXTAI_V2_RUN_NOW_HOOK, 'rapidtextai_v2_run_campaign' );

/**
 * Generate and save the next article for a campaign.
 *
 * @return array|WP_Error { post_id, title, edit_url }
 */
function rapidtextai_v2_run_campaign( $id ) {
    $campaigns = rapidtextai_v2_get_campaigns();
    if ( empty( $campaigns[ $id ] ) ) {
        return new WP_Error( 'not_found', 'Campaign not found.', array( 'status' => 404 ) );
    }
    $c    = $campaigns[ $id ];
    $lock = 'rapidtextai_v2_lock_' . md5( $id );
    if ( get_transient( $lock ) ) {
        return new WP_Error( 'busy', 'This campaign is already generating an article.', array( 'status' => 409 ) );
    }
    set_transient( $lock, 1, 7 * MINUTE_IN_SECONDS );

    $topics = rapidtextai_v2_parse_topics( $c['topics'] );
    if ( ! $topics ) {
        delete_transient( $lock );
        return new WP_Error( 'no_topics', 'This campaign has no topics left.', array( 'status' => 400 ) );
    }
    $cursor = (int) $c['cursor'];
    if ( $cursor >= count( $topics ) ) {
        if ( ! empty( $c['loop'] ) ) {
            $cursor = 0;
        } else {
            delete_transient( $lock );
            $campaigns[ $id ]['enabled']     = false;
            $campaigns[ $id ]['last_status'] = 'finished';
            rapidtextai_v2_save_campaigns( $campaigns );
            rapidtextai_v2_sync_schedule( $id );
            return new WP_Error( 'finished', 'All topics have been used. The campaign was paused.', array( 'status' => 400 ) );
        }
    }
    $item = $topics[ $cursor ];

    $req = new WP_REST_Request( 'POST', '/rapidtextai/v1/v2/article' );
    $map = array(
        'topic' => $item['topic'], 'keyword' => $item['keyword'], 'model' => $c['model'], 'word_count' => $c['word_count'],
        'article_type' => $c['article_type'], 'tone' => $c['tone'], 'audience' => $c['audience'], 'language' => $c['language'],
        'cta' => $c['cta'], 'research' => $c['research'], 'include_faq' => $c['include_faq'], 'include_sources' => $c['include_sources'],
        'include_images' => $c['include_images'], 'image_count' => $c['image_count'], 'internal_links' => $c['internal_links'],
        'brand_id' => $c['brand_id'],
    );
    foreach ( $map as $k => $v ) {
        $req->set_param( $k, $v );
    }
    $result = rapidtextai_v2_generate( get_option( 'rapidtextai_api_key', '' ), rapidtextai_v2_build_request( $req ) );

    if ( ! is_wp_error( $result ) ) {
        $result = rapidtextai_v2_save_post( $result, array(
            'status'         => $c['post_status'],
            'author'         => $c['author'],
            'categories'     => $c['categories'],
            'featured_image' => ! empty( $c['featured_image'] ),
            'fallback_title' => $item['topic'],
            'meta'           => array( '_rapidtextai_campaign' => $id ),
        ) );
    }

    $campaigns = rapidtextai_v2_get_campaigns(); // re-read: generation takes minutes
    if ( empty( $campaigns[ $id ] ) ) {
        delete_transient( $lock );
        return $result;
    }
    $campaigns[ $id ]['last_run'] = time();
    if ( is_wp_error( $result ) ) {
        $campaigns[ $id ]['failures']    = (int) $campaigns[ $id ]['failures'] + 1;
        $campaigns[ $id ]['last_status'] = 'failed';
        // Skip a topic that keeps failing so the campaign does not stall; pause after repeated trouble.
        if ( $campaigns[ $id ]['failures'] >= 3 ) {
            $campaigns[ $id ]['cursor']   = $cursor + 1;
            $campaigns[ $id ]['failures'] = 0;
        }
        if ( 'no_key' === $result->get_error_code() || in_array( (int) ( $result->get_error_data()['status'] ?? 0 ), array( 401, 402 ), true ) ) {
            $campaigns[ $id ]['enabled'] = false;
        }
        rapidtextai_v2_log_run( array( 'campaign' => $id, 'name' => $c['name'], 'topic' => $item['topic'], 'ok' => false, 'message' => $result->get_error_message() ) );
    } else {
        $campaigns[ $id ]['cursor']      = $cursor + 1;
        $campaigns[ $id ]['failures']    = 0;
        $campaigns[ $id ]['last_status'] = 'ok';
        rapidtextai_v2_log_run( array(
            'campaign' => $id, 'name' => $c['name'], 'topic' => $item['topic'], 'ok' => true,
            'post_id' => $result['post_id'], 'title' => $result['title'], 'edit_url' => $result['edit_url'],
        ) );
    }
    rapidtextai_v2_save_campaigns( $campaigns );
    if ( empty( $campaigns[ $id ]['enabled'] ) ) {
        rapidtextai_v2_sync_schedule( $id );
    }
    delete_transient( $lock );

    return is_wp_error( $result ) ? $result : array(
        'post_id'  => $result['post_id'],
        'title'    => $result['title'],
        'edit_url' => $result['edit_url'],
    );
}

function rapidtextai_v2_campaign_view( array $c ) {
    $topics               = rapidtextai_v2_parse_topics( $c['topics'] );
    $c['topics_total']    = count( $topics );
    $c['topics_left']     = max( 0, count( $topics ) - (int) $c['cursor'] );
    $c['next_topic']      = isset( $topics[ (int) $c['cursor'] ] ) ? $topics[ (int) $c['cursor'] ]['topic'] : '';
    $c['next_run']        = (int) wp_next_scheduled( RAPIDTEXTAI_V2_CRON_HOOK, array( $c['id'] ) );
    $c['running']         = (bool) get_transient( 'rapidtextai_v2_lock_' . md5( $c['id'] ) );
    return $c;
}

/** Build a v2 campaign from a classic one. The classic campaign is left exactly as it was. */
function rapidtextai_v2_campaign_from_legacy( $legacy_id ) {
    $legacy = get_option( 'rapidtextai_auto_blogging_campaigns', array() );
    if ( empty( $legacy[ $legacy_id ] ) ) {
        return null;
    }
    $l = $legacy[ $legacy_id ];
    $freq = (string) ( $l['schedule'] ?? $l['frequency'] ?? 'daily' );
    $freq = in_array( $freq, rapidtextai_v2_frequencies(), true ) ? $freq : 'daily';
    $cats = array_values( array_filter( array_map( 'intval', (array) ( $l['post_category'] ?? array() ) ) ) );
    return array(
        'name'        => ( $l['name'] ?? 'Campaign' ) . ' (Pro)',
        'topics'      => (string) ( $l['topics'] ?? '' ),
        'frequency'   => $freq,
        'post_status' => $l['post_status'] ?? 'draft',
        'author'      => (int) ( $l['post_author'] ?? get_current_user_id() ),
        'categories'  => $cats,
        'include_images' => ! empty( $l['include_images'] ),
        'enabled'     => false,
        'migrated_from' => (string) $legacy_id,
    );
}

/* ───────────────────────────────────────────────────────── SEO history (stored in WordPress) */

const RAPIDTEXTAI_SEO_HISTORY_OPTION = 'rapidtextai_seo_history';
const RAPIDTEXTAI_SEO_HISTORY_LIMIT  = 40;

function rapidtextai_seo_history_index() {
    $i = get_option( RAPIDTEXTAI_SEO_HISTORY_OPTION, array() );
    return is_array( $i ) ? $i : array();
}

/** Keep the result of a tool run on this site so it can be reopened later without spending requests. */
function rapidtextai_seo_history_add( $tool, array $result, $label, $post_id = 0 ) {
    unset( $result['original_html'], $result['usage'] );
    if ( isset( $result['updated_html'] ) && strlen( $result['updated_html'] ) > 300000 ) {
        $result['updated_html'] = '';
        $result['too_large']    = true;
    }
    $summary = array();
    switch ( $tool ) {
        case 'originality':
            $summary = array( 'score' => (int) ( $result['score'] ?? 0 ), 'matched' => (int) ( $result['matched'] ?? 0 ) );
            break;
        case 'fact-check':
            $summary = array( 'score' => (int) ( $result['summary']['score'] ?? 0 ), 'contradicted' => (int) ( $result['summary']['contradicted'] ?? 0 ) );
            break;
        case 'content-refresh':
            $summary = array( 'updated' => (int) ( $result['summary']['updated'] ?? 0 ) );
            break;
        case 'competitor-gap':
            $summary = array( 'competitors' => count( (array) ( $result['competitors'] ?? array() ) ), 'target_words' => (int) ( $result['target_words'] ?? 0 ) );
            break;
    }
    $id    = 'h' . wp_generate_password( 10, false, false );
    $post  = $post_id ? get_post( $post_id ) : null;
    $entry = array(
        'id'            => $id,
        'tool'          => $tool,
        'time'          => time(),
        'label'         => mb_substr( (string) $label, 0, 120 ),
        'post_id'       => (int) $post_id,
        'post_modified' => $post ? $post->post_modified_gmt : '',
        'summary'       => $summary,
    );
    update_option( 'rapidtextai_seo_run_' . $id, $result, false );
    $index = rapidtextai_seo_history_index();
    array_unshift( $index, $entry );
    foreach ( array_slice( $index, RAPIDTEXTAI_SEO_HISTORY_LIMIT ) as $old ) {
        delete_option( 'rapidtextai_seo_run_' . $old['id'] );
    }
    update_option( RAPIDTEXTAI_SEO_HISTORY_OPTION, array_slice( $index, 0, RAPIDTEXTAI_SEO_HISTORY_LIMIT ), false );
    return $id;
}

/* ───────────────────────────────────────────────────────── REST routes */

add_action( 'rest_api_init', function () {
    $ns   = 'rapidtextai/v1';
    $perm = 'rapidtextai_rest_permission';

    // Preferences / bootstrap -------------------------------------------------
    register_rest_route( $ns, '/v2/prefs', array(
        array(
            'methods'             => 'GET',
            'permission_callback' => $perm,
            'callback'            => function () {
                $log = get_option( RAPIDTEXTAI_V2_RUNS_OPTION, array() );
                return rest_ensure_response( array(
                    'default_engine' => rapidtextai_default_engine(),
                    'has_key'        => (bool) get_option( 'rapidtextai_api_key', '' ),
                    'legacy_count'   => rapidtextai_legacy_campaign_count(),
                    'v2_count'       => count( rapidtextai_v2_get_campaigns() ),
                    'site_url'       => home_url(),
                    'recent_runs'    => is_array( $log ) ? array_slice( $log, 0, 5 ) : array(),
                ) );
            },
        ),
        array(
            'methods'             => 'POST',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                $engine = 'classic' === $r->get_param( 'default_engine' ) ? 'classic' : 'v2';
                update_option( 'rapidtextai_default_engine', $engine );
                return rest_ensure_response( array( 'default_engine' => $engine ) );
            },
        ),
    ) );

    // Dashboard ---------------------------------------------------------------
    register_rest_route( $ns, '/v2/dashboard', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $days = max( 7, min( 90, (int) ( $r->get_param( 'days' ) ?: 30 ) ) );
            return rest_ensure_response( rapidtextai_api_request( 'GET', 'v2/dashboard?days=' . $days, null, 30 ) );
        },
    ) );

    // Brands ------------------------------------------------------------------
    register_rest_route( $ns, '/brands', array(
        array(
            'methods'             => 'GET',
            'permission_callback' => $perm,
            'callback'            => function () {
                return rest_ensure_response( rapidtextai_api_request( 'GET', 'brands', null, 30 ) );
            },
        ),
        array(
            'methods'             => 'POST',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                return rest_ensure_response( rapidtextai_api_request( 'POST', 'brands', $r->get_json_params(), 30 ) );
            },
        ),
    ) );
    register_rest_route( $ns, '/brands/(?P<id>\d+)', array(
        array(
            'methods'             => 'PUT',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                return rest_ensure_response( rapidtextai_api_request( 'PUT', 'brands/' . (int) $r['id'], $r->get_json_params(), 30 ) );
            },
        ),
        array(
            'methods'             => 'DELETE',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                return rest_ensure_response( rapidtextai_api_request( 'DELETE', 'brands/' . (int) $r['id'], null, 30 ) );
            },
        ),
    ) );

    // SEO tools ---------------------------------------------------------------
    register_rest_route( $ns, '/v2/seo/capabilities', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'callback'            => function () {
            $cached = get_transient( 'rapidtextai_seo_caps' );
            if ( is_array( $cached ) ) {
                return rest_ensure_response( $cached );
            }
            $caps = rapidtextai_api_request( 'GET', 'v2/seo/capabilities', null, 20 );
            if ( ! is_wp_error( $caps ) ) {
                set_transient( 'rapidtextai_seo_caps', $caps, 10 * MINUTE_IN_SECONDS );
            }
            return rest_ensure_response( $caps );
        },
    ) );

    register_rest_route( $ns, '/v2/seo/(?P<tool>brand-from-url|content-refresh|competitor-gap|fact-check|originality|embeds)', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $tool = $r['tool'];
            $in   = (array) $r->get_json_params();
            $body = array();

            // A post can stand in for url/html/text so the browser never has to send the article body.
            $post_id = (int) ( $in['post_id'] ?? 0 );
            $post    = null;
            if ( $post_id ) {
                $post = get_post( $post_id );
                if ( ! $post || ! current_user_can( 'edit_post', $post_id ) ) {
                    return new WP_Error( 'forbidden', 'You cannot access that post.', array( 'status' => 403 ) );
                }
            }
            $body['url'] = isset( $in['url'] ) ? esc_url_raw( $in['url'] ) : '';
            if ( 'competitor-gap' === $tool ) {
                $body = array( 'keyword' => sanitize_text_field( $in['keyword'] ?? '' ) );
            } elseif ( 'embeds' === $tool ) {
                $body = array(
                    'topic'    => sanitize_text_field( $in['topic'] ?? ( $post ? $post->post_title : '' ) ),
                    'keyword'  => sanitize_text_field( $in['keyword'] ?? '' ),
                    'headings' => array_map( 'sanitize_text_field', (array) ( $in['headings'] ?? array() ) ),
                    'tweets'   => ! empty( $in['tweets'] ),
                    'videos'   => ! empty( $in['videos'] ),
                );
            } elseif ( 'brand-from-url' !== $tool ) {
                if ( $post ) {
                    $html = $post->post_content;
                    if ( ! has_blocks( $html ) ) {
                        $html = wpautop( $html );
                    }
                    $html = do_blocks( $html );
                    $key  = 'content-refresh' === $tool ? 'html' : 'html';
                    $body = array( $key => wp_kses_post( $html ) );
                } elseif ( ! empty( $in['html'] ) ) {
                    $body = array( 'html' => wp_kses_post( $in['html'] ) );
                } elseif ( ! empty( $in['text'] ) ) {
                    $body = array( 'text' => sanitize_textarea_field( $in['text'] ) );
                } elseif ( '' === $body['url'] ) {
                    return new WP_Error( 'no_input', 'Choose a post, paste text or enter a URL.', array( 'status' => 400 ) );
                }
                if ( $post && 'content-refresh' === $tool && empty( $body['html'] ) ) {
                    return new WP_Error( 'empty_post', 'That post has no content yet.', array( 'status' => 400 ) );
                }
            }
            if ( ! empty( $in['model'] ) ) {
                $body['model'] = sanitize_text_field( $in['model'] );
            }
            $res = rapidtextai_api_request( 'POST', 'v2/seo/' . $tool, $body, 170 );
            if ( is_wp_error( $res ) ) {
                return $res;
            }
            if ( 'content-refresh' === $tool && ! empty( $res['updated_html'] ) ) {
                $res['updated_html'] = wp_kses_post( $res['updated_html'] );
            }
            if ( 'embeds' !== $tool && 'brand-from-url' !== $tool ) {
                $label = $post ? get_the_title( $post ) : ( 'competitor-gap' === $tool ? ( $in['keyword'] ?? '' ) : ( ! empty( $in['url'] ) ? $in['url'] : wp_trim_words( wp_strip_all_tags( (string) ( $in['html'] ?? $in['text'] ?? '' ) ), 8, '…' ) ) );
                $res['history_id'] = rapidtextai_seo_history_add( $tool, $res, $label, $post_id );
            }
            if ( $post && in_array( $tool, array( 'fact-check', 'originality', 'content-refresh' ), true ) ) {
                $save = $res;
                unset( $save['original_html'], $save['updated_html'] );
                update_post_meta( $post_id, '_rapidtextai_seo_' . str_replace( '-', '_', $tool ), wp_slash( wp_json_encode( array( 'at' => time(), 'result' => $save ) ) ) );
            }
            return rest_ensure_response( $res );
        },
    ) );

    // SEO history ---------------------------------------------------------------
    register_rest_route( $ns, '/v2/seo/history', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'callback'            => function () {
            return rest_ensure_response( rapidtextai_seo_history_index() );
        },
    ) );
    register_rest_route( $ns, '/v2/seo/history/(?P<id>h[A-Za-z0-9]+)', array(
        array(
            'methods'             => 'GET',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                foreach ( rapidtextai_seo_history_index() as $e ) {
                    if ( $e['id'] === $r['id'] ) {
                        $data = get_option( 'rapidtextai_seo_run_' . $e['id'], null );
                        if ( ! is_array( $data ) ) {
                            break;
                        }
                        $post = $e['post_id'] ? get_post( $e['post_id'] ) : null;
                        return rest_ensure_response( array(
                            'entry'        => $e,
                            'result'       => $data,
                            'post_missing' => $e['post_id'] && ! $post,
                            'post_changed' => $post && $e['post_modified'] && $post->post_modified_gmt !== $e['post_modified'],
                        ) );
                    }
                }
                return new WP_Error( 'not_found', 'That result is no longer stored.', array( 'status' => 404 ) );
            },
        ),
        array(
            'methods'             => 'DELETE',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                $index = array_values( array_filter( rapidtextai_seo_history_index(), function ( $e ) use ( $r ) {
                    return $e['id'] !== $r['id'];
                } ) );
                delete_option( 'rapidtextai_seo_run_' . $r['id'] );
                update_option( RAPIDTEXTAI_SEO_HISTORY_OPTION, $index, false );
                return rest_ensure_response( array( 'deleted' => true ) );
            },
        ),
    ) );

    // Improve topics (Pro campaigns) --------------------------------------------
    register_rest_route( $ns, '/v2/improve-topics', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $seed = array_slice( array_map( 'sanitize_text_field', preg_split( '/\r\n|\r|\n/', (string) $r->get_param( 'topics' ) ) ), 0, 40 );
            $seed = array_values( array_filter( array_map( 'trim', $seed ) ) );
            if ( ! $seed ) {
                return new WP_Error( 'no_topics', 'Type at least one topic or idea first.', array( 'status' => 400 ) );
            }
            $count = max( 3, min( 30, (int) ( $r->get_param( 'count' ) ?: 10 ) ) );

            $context = '';
            $brand_id = (int) $r->get_param( 'brand_id' );
            if ( $brand_id ) {
                $b = rapidtextai_api_request( 'GET', 'brands/' . $brand_id, null, 20 );
                if ( ! is_wp_error( $b ) ) {
                    $bits = array_filter( array( $b['name'] ?? '', $b['description'] ?? '', $b['audience'] ?? '', $b['product_info'] ?? '' ) );
                    $context = "About the site: " . mb_substr( implode( '. ', $bits ), 0, 800 ) . "\n";
                }
            }
            $prompt = "You plan content for a blog.\n" . $context
                . "Starting ideas from the editor (they may be rough, short or one line):\n- " . implode( "\n- ", $seed ) . "\n\n"
                . "Write exactly {$count} specific, search-friendly blog post topics that build on these ideas, covering different angles and search intents (how-to, comparison, list, beginner, advanced, mistakes, cost). Keep any idea the editor already wrote, improved. Use the same language as the ideas. No duplicates.\n"
                . "Format: one per line as: Topic title | main keyword phrase\nNo numbering, no bullets, no extra text.";

            $res = rapidtextai_api_request( 'POST', 'v1/chat/completions', array(
                'model'       => 'gpt-4o-mini',
                'messages'    => array(
                    array( 'role' => 'system', 'content' => 'You are an expert SEO content strategist.' ),
                    array( 'role' => 'user', 'content' => $prompt ),
                ),
                'max_tokens'  => 1500,
                'temperature' => 0.7,
            ), 60 );
            if ( is_wp_error( $res ) ) {
                return $res;
            }
            $text  = (string) ( $res['choices'][0]['message']['content'] ?? '' );
            $lines = array();
            foreach ( preg_split( '/\r\n|\r|\n/', $text ) as $l ) {
                $l = trim( preg_replace( '/^(\d+[\.\)]\s*|[-*•]\s*)/u', '', trim( $l ) ) );
                if ( '' === $l || false === strpos( $l, '|' ) && mb_strlen( $l ) < 8 ) {
                    continue;
                }
                $lines[] = sanitize_text_field( $l );
            }
            $lines = array_values( array_unique( $lines ) );
            if ( ! $lines ) {
                return new WP_Error( 'empty', 'No topics came back. Try again with a little more detail.', array( 'status' => 502 ) );
            }
            return rest_ensure_response( array( 'topics' => implode( "\n", array_slice( $lines, 0, $count ) ) ) );
        },
    ) );

    // Posts picker ------------------------------------------------------------
    register_rest_route( $ns, '/v2/posts', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $q = new WP_Query( array(
                'post_type'      => array( 'post', 'page' ),
                'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
                's'              => sanitize_text_field( (string) $r->get_param( 'search' ) ),
                'posts_per_page' => 20,
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'no_found_rows'  => true,
            ) );
            $out = array();
            foreach ( $q->posts as $p ) {
                $out[] = array(
                    'id'       => $p->ID,
                    'title'    => get_the_title( $p ) ?: '(no title)',
                    'status'   => $p->post_status,
                    'type'     => $p->post_type,
                    'words'    => str_word_count( wp_strip_all_tags( $p->post_content ) ),
                    'modified' => get_post_modified_time( 'c', true, $p ),
                    'url'      => 'publish' === $p->post_status ? get_permalink( $p ) : '',
                    'edit_url' => get_edit_post_link( $p->ID, 'raw' ),
                    'backup'   => (bool) get_post_meta( $p->ID, '_rapidtextai_backup', true ),
                );
            }
            return rest_ensure_response( $out );
        },
    ) );

    // Apply / restore a refreshed version ------------------------------------
    register_rest_route( $ns, '/v2/apply', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $post_id = (int) $r->get_param( 'post_id' );
            $html    = (string) $r->get_param( 'html' );
            $post    = get_post( $post_id );
            if ( ! $post || ! current_user_can( 'edit_post', $post_id ) || '' === trim( $html ) ) {
                return new WP_Error( 'bad_request', 'A post and new content are required.', array( 'status' => 400 ) );
            }
            // Keep the previous version so the change can be undone from the same screen.
            update_post_meta( $post_id, '_rapidtextai_backup', wp_slash( wp_json_encode( array( 'at' => time(), 'content' => $post->post_content ) ) ) );
            $res = wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_kses_post( $html ) ), true );
            if ( is_wp_error( $res ) ) {
                return $res;
            }
            return rest_ensure_response( array( 'ok' => true, 'edit_url' => get_edit_post_link( $post_id, 'raw' ) ) );
        },
    ) );
    register_rest_route( $ns, '/v2/restore', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $post_id = (int) $r->get_param( 'post_id' );
            $raw     = get_post_meta( $post_id, '_rapidtextai_backup', true );
            $data    = is_string( $raw ) ? json_decode( $raw, true ) : null;
            if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) || empty( $data['content'] ) ) {
                return new WP_Error( 'no_backup', 'There is no saved version to restore.', array( 'status' => 404 ) );
            }
            wp_update_post( array( 'ID' => $post_id, 'post_content' => $data['content'] ) );
            delete_post_meta( $post_id, '_rapidtextai_backup' );
            return rest_ensure_response( array( 'ok' => true ) );
        },
    ) );

    // v2 campaigns ------------------------------------------------------------
    register_rest_route( $ns, '/v2/campaigns', array(
        array(
            'methods'             => 'GET',
            'permission_callback' => $perm,
            'callback'            => function () {
                $out = array();
                foreach ( rapidtextai_v2_get_campaigns() as $c ) {
                    $out[] = rapidtextai_v2_campaign_view( $c );
                }
                return rest_ensure_response( array( 'campaigns' => $out, 'runs' => get_option( RAPIDTEXTAI_V2_RUNS_OPTION, array() ) ) );
            },
        ),
        array(
            'methods'             => 'POST',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                $in        = (array) $r->get_json_params();
                $campaigns = rapidtextai_v2_get_campaigns();
                if ( ! empty( $in['from_legacy'] ) ) {
                    $seed = rapidtextai_v2_campaign_from_legacy( sanitize_text_field( $in['from_legacy'] ) );
                    if ( ! $seed ) {
                        return new WP_Error( 'not_found', 'Classic campaign not found.', array( 'status' => 404 ) );
                    }
                    $in = array_merge( $seed, array_diff_key( $in, array( 'from_legacy' => 1 ) ) );
                }
                $c       = rapidtextai_v2_sanitize_campaign( $in );
                $id      = 'v2_' . wp_generate_password( 8, false, false );
                $c['id'] = $id;
                if ( ! empty( $in['migrated_from'] ) ) {
                    $c['migrated_from'] = sanitize_text_field( $in['migrated_from'] );
                }
                $campaigns[ $id ] = $c;
                rapidtextai_v2_save_campaigns( $campaigns );
                rapidtextai_v2_sync_schedule( $id );
                return rest_ensure_response( rapidtextai_v2_campaign_view( $c ) );
            },
        ),
    ) );
    register_rest_route( $ns, '/v2/campaigns/(?P<id>v2_[A-Za-z0-9]+)', array(
        array(
            'methods'             => 'PUT',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                $id        = $r['id'];
                $campaigns = rapidtextai_v2_get_campaigns();
                if ( empty( $campaigns[ $id ] ) ) {
                    return new WP_Error( 'not_found', 'Campaign not found.', array( 'status' => 404 ) );
                }
                $in = (array) $r->get_json_params();
                $c  = rapidtextai_v2_sanitize_campaign( $in, $campaigns[ $id ] );
                if ( ! empty( $in['reset_cursor'] ) ) {
                    $c['cursor'] = 0;
                }
                $campaigns[ $id ] = $c;
                rapidtextai_v2_save_campaigns( $campaigns );
                rapidtextai_v2_sync_schedule( $id );
                return rest_ensure_response( rapidtextai_v2_campaign_view( $c ) );
            },
        ),
        array(
            'methods'             => 'DELETE',
            'permission_callback' => $perm,
            'callback'            => function ( WP_REST_Request $r ) {
                $id        = $r['id'];
                $campaigns = rapidtextai_v2_get_campaigns();
                unset( $campaigns[ $id ] );
                rapidtextai_v2_save_campaigns( $campaigns );
                wp_clear_scheduled_hook( RAPIDTEXTAI_V2_CRON_HOOK, array( $id ) );
                return rest_ensure_response( array( 'deleted' => true ) );
            },
        ),
    ) );
    register_rest_route( $ns, '/v2/campaigns/(?P<id>v2_[A-Za-z0-9]+)/run', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $id        = $r['id'];
            $campaigns = rapidtextai_v2_get_campaigns();
            if ( empty( $campaigns[ $id ] ) ) {
                return new WP_Error( 'not_found', 'Campaign not found.', array( 'status' => 404 ) );
            }
            if ( get_transient( 'rapidtextai_v2_lock_' . md5( $id ) ) ) {
                return new WP_Error( 'busy', 'This campaign is already generating an article.', array( 'status' => 409 ) );
            }
            // sync=1 runs inside the request (used by tests and hosts that block loopback cron).
            if ( $r->get_param( 'sync' ) ) {
                $res = rapidtextai_v2_run_campaign( $id );
                return is_wp_error( $res ) ? $res : rest_ensure_response( $res );
            }
            wp_schedule_single_event( time(), RAPIDTEXTAI_V2_RUN_NOW_HOOK, array( $id ) );
            if ( function_exists( 'spawn_cron' ) ) {
                spawn_cron();
            }
            return rest_ensure_response( array( 'queued' => true ) );
        },
    ) );
} );

/* ───────────────────────────────────────────────────────── Menu */

/**
 * Pages hosted by the React admin app. The first item becomes the landing page of the RapidTextAI menu.
 */
add_action( 'admin_menu', function () {
    $pages = array(
        'rapidtextai-dashboard' => array( 'Dashboard', 'dashboard' ),
        'rapidtextai-seo-tools' => array( 'SEO Tools', 'seo-tools' ),
        'rapidtextai-brands'    => array( 'Brands', 'brands' ),
    );
    foreach ( $pages as $slug => $def ) {
        add_submenu_page(
            'rapidtextai-settings',
            $def[0],
            $def[0],
            'manage_options',
            $slug,
            function () use ( $def ) {
                if ( current_user_can( 'manage_options' ) ) {
                    rapidtextai_react_app_root( array( 'page' => $def[1] ) );
                }
            }
        );
    }
}, 998 );

add_action( 'admin_menu', function () {
    global $submenu;
    if ( empty( $submenu['rapidtextai-settings'] ) ) {
        return;
    }
    $order = array(
        'rapidtextai-dashboard',
        'rapidtextai-pro-article',
        'rapidtextai-seo-tools',
        'rapidtextai-auto-blogging',
        'rapidtextai-brands',
        'rapidtextai-chatbots',
        'rapidtextai-settings',
    );
    $by_slug = array();
    $rest    = array();
    foreach ( $submenu['rapidtextai-settings'] as $item ) {
        if ( in_array( $item[2], $order, true ) ) {
            $by_slug[ $item[2] ] = $item;
        } else {
            $rest[] = $item;
        }
    }
    if ( isset( $by_slug['rapidtextai-settings'] ) ) {
        $by_slug['rapidtextai-settings'][0] = 'Settings';
    }
    if ( isset( $by_slug['rapidtextai-auto-blogging'] ) ) {
        $by_slug['rapidtextai-auto-blogging'][0] = 'Auto Blogging';
    }
    $new = array();
    foreach ( $order as $slug ) {
        if ( isset( $by_slug[ $slug ] ) ) {
            $new[] = $by_slug[ $slug ];
        }
    }
    $submenu['rapidtextai-settings'] = array_merge( $new, $rest ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
}, 9999 );

/** Send first-time visitors of the bare menu slug to the dashboard. */
add_action( 'admin_init', function () {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['page'] ) && 'rapidtextai-settings' === $_GET['page'] && isset( $_GET['go'] ) && 'dashboard' === $_GET['go'] ) {
        wp_safe_redirect( admin_url( 'admin.php?page=rapidtextai-dashboard' ) );
        exit;
    }
} );

/** Remove everything the suite schedules when the plugin is deactivated. */
function rapidtextai_v2_clear_all_schedules() {
    foreach ( array_keys( rapidtextai_v2_get_campaigns() ) as $id ) {
        wp_clear_scheduled_hook( RAPIDTEXTAI_V2_CRON_HOOK, array( $id ) );
    }
}
