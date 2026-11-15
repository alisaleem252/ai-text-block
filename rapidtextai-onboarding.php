<?php
/**
 * Onboard your website: RapidTextAI researches this site (brand, competitors, keywords, questions, gaps, outlines),
 * then the plugin creates its own campaigns from the plan and imports the first articles as WordPress drafts.
 *
 * The research runs on RapidTextAI's servers (channel "wordpress"); this file proxies the calls with the site's key
 * and does the local part once the setup is finished. Paid plans open Stripe's own checkout page in a new tab, which
 * sends the person back to this page with ?session_id=.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'RAPIDTEXTAI_ONBOARDING_OPTION', 'rapidtextai_onboarding' );
define( 'RAPIDTEXTAI_ONBOARDING_SKIPPED_OPTION', 'rapidtextai_onboarding_skipped' );

function rapidtextai_onboarding_page_url() {
    return admin_url( 'admin.php?page=rapidtextai-onboard' );
}

/** Local state: { id, started, finished, campaigns: {plan_type: campaign_id}, drafts: {key: post_id} }. */
function rapidtextai_onboarding_state() {
    $s = get_option( RAPIDTEXTAI_ONBOARDING_OPTION, array() );
    $s = is_array( $s ) ? $s : array();
    return array_merge( array( 'id' => '', 'started' => 0, 'finished' => 0, 'campaigns' => array(), 'drafts' => array() ), $s );
}

function rapidtextai_onboarding_save_state( array $s ) {
    update_option( RAPIDTEXTAI_ONBOARDING_OPTION, $s, false );
}

function rapidtextai_onboarding_id() {
    $id = (string) rapidtextai_onboarding_state()['id'];
    return preg_match( '/^[a-z0-9]{16}$/', $id ) ? $id : '';
}

/** The state the page needs on load (also returned after each local change). */
function rapidtextai_onboarding_local_view() {
    $s         = rapidtextai_onboarding_state();
    $campaigns = rapidtextai_v2_get_campaigns();
    $made      = array();
    foreach ( (array) $s['campaigns'] as $type => $cid ) {
        if ( ! empty( $campaigns[ $cid ] ) ) {
            $made[] = rapidtextai_v2_campaign_view( $campaigns[ $cid ] );
        }
    }
    $drafts = array();
    foreach ( (array) $s['drafts'] as $key => $post_id ) {
        $post = get_post( (int) $post_id );
        if ( $post ) {
            $drafts[] = array(
                'post_id'  => (int) $post_id,
                'title'    => get_the_title( $post ),
                'status'   => $post->post_status,
                'edit_url' => get_edit_post_link( $post, 'raw' ),
            );
        }
    }
    return array(
        'id'        => rapidtextai_onboarding_id(),
        'finished'  => (int) $s['finished'],
        'campaigns' => $made,
        'drafts'    => $drafts,
    );
}

/** Turn the server's "Title;keyword;;;words" topic into the campaign box line "Title | keyword". */
function rapidtextai_onboarding_topic_line( $topic ) {
    $parts   = explode( ';', (string) $topic );
    $title   = trim( str_replace( '|', '-', (string) ( $parts[0] ?? '' ) ) );
    $keyword = trim( str_replace( '|', '-', (string) ( $parts[1] ?? '' ) ) );
    if ( '' === $title ) {
        return '';
    }
    return '' !== $keyword ? $title . ' | ' . $keyword : $title;
}

/**
 * Create the campaigns and import the drafts. Safe to call again: what was already made is skipped.
 *
 * @return array|WP_Error the local view
 */
function rapidtextai_onboarding_import() {
    $id = rapidtextai_onboarding_id();
    if ( '' === $id ) {
        return new WP_Error( 'no_setup', 'Start onboarding first.', array( 'status' => 400 ) );
    }
    // Stripe's return tab and the tab that opened it can both finish at once: only one imports.
    if ( get_transient( 'rapidtextai_onboarding_importing' ) ) {
        return new WP_Error( 'importing', 'Adding your campaigns and drafts to WordPress…', array( 'status' => 409 ) );
    }
    set_transient( 'rapidtextai_onboarding_importing', 1, 10 * MINUTE_IN_SECONDS );
    $result = rapidtextai_onboarding_import_now( $id );
    delete_transient( 'rapidtextai_onboarding_importing' );
    return $result;
}

function rapidtextai_onboarding_import_now( $id ) {
    $plans = rapidtextai_api_request( 'GET', 'v2/onboarding/' . $id . '/plugin/plans', null, 30 );
    if ( is_wp_error( $plans ) ) {
        return $plans;
    }
    $state     = rapidtextai_onboarding_state();
    $campaigns = rapidtextai_v2_get_campaigns();

    foreach ( (array) ( $plans['plans'] ?? array() ) as $plan ) {
        $type = sanitize_key( (string) ( $plan['type'] ?? '' ) );
        if ( '' === $type || ( ! empty( $state['campaigns'][ $type ] ) && ! empty( $campaigns[ $state['campaigns'][ $type ] ] ) ) ) {
            continue;
        }
        $lines = array();
        $words = 0;
        foreach ( (array) ( $plan['topics'] ?? array() ) as $t ) {
            $line = rapidtextai_onboarding_topic_line( $t );
            if ( '' !== $line ) {
                $lines[] = $line;
                $w       = (int) ( explode( ';', (string) $t )[4] ?? 0 );
                $words   = $words ?: $w;
            }
        }
        if ( ! $lines ) {
            continue;
        }
        $c = rapidtextai_v2_sanitize_campaign( array(
            'name'        => (string) ( $plan['name'] ?? 'Campaign' ),
            'enabled'     => false,
            'topics'      => implode( "\n", $lines ),
            'frequency'   => in_array( $plan['schedule'] ?? '', rapidtextai_v2_frequencies(), true ) ? $plan['schedule'] : 'weekly',
            'post_status' => 'draft',
            'brand_id'    => (int) ( $plans['brand_id'] ?? 0 ),
            'model'       => (string) ( $plans['model'] ?? '' ),
            'word_count'  => $words ?: 1500,
            'tone'        => (string) ( $plans['tone'] ?? '' ),
            'audience'    => (string) ( $plans['audience'] ?? '' ),
            'language'    => (string) ( $plans['language'] ?? '' ),
            'article_type' => 'guide',
            'include_faq' => true,
            'outlines'    => (array) ( $plan['outlines'] ?? array() ),
            'source_ids'  => ! empty( $plans['source_id'] ) ? array( (int) $plans['source_id'] ) : array(),
        ) );
        $cid                        = 'v2_' . wp_generate_password( 8, false, false );
        $c['id']                    = $cid;
        $c['onboarding']            = $id;
        $campaigns[ $cid ]          = $c;
        $state['campaigns'][ $type ] = $cid;
    }
    rapidtextai_v2_save_campaigns( $campaigns );
    rapidtextai_onboarding_save_state( $state );

    $drafts = rapidtextai_api_request( 'GET', 'v2/onboarding/' . $id . '/plugin/drafts', null, 30 );
    if ( ! is_wp_error( $drafts ) ) {
        foreach ( (array) ( $drafts['drafts'] ?? array() ) as $d ) {
            $key = sanitize_text_field( (string) ( $d['key'] ?? '' ) );
            if ( '' === $key || ( ! empty( $state['drafts'][ $key ] ) && get_post( (int) $state['drafts'][ $key ] ) ) ) {
                continue;
            }
            $saved = rapidtextai_v2_save_post( array(
                'title'            => sanitize_text_field( (string) ( $d['title'] ?? '' ) ),
                'slug'             => sanitize_title( (string) ( $d['slug'] ?? '' ) ),
                'body_html'        => wp_kses_post( (string) ( $d['html'] ?? '' ) ),
                'meta_description' => sanitize_text_field( (string) ( $d['meta_description'] ?? '' ) ),
                'tags'             => array_map( 'sanitize_text_field', array_slice( (array) ( $d['tags'] ?? array() ), 0, 10 ) ),
                'warnings'         => array(),
                'quality'          => array(),
                'sources'          => array(),
            ), array(
                'status'         => 'draft',
                'author'         => get_current_user_id(),
                'featured_image' => true,
                'meta'           => array( '_rapidtextai_onboarding' => $key ),
            ) );
            if ( ! is_wp_error( $saved ) ) {
                $state['drafts'][ $key ] = (int) $saved['post_id'];
                rapidtextai_onboarding_save_state( $state ); // images can take a while; keep what is done
            }
        }
    }

    $state['finished'] = $state['finished'] ?: time();
    rapidtextai_onboarding_save_state( $state );
    return rapidtextai_onboarding_local_view();
}

/** Pass a RapidTextAI error through, or hand back the data. */
function rapidtextai_onboarding_respond( $res ) {
    return is_wp_error( $res ) ? $res : rest_ensure_response( $res );
}

function rapidtextai_onboarding_need_id() {
    $id = rapidtextai_onboarding_id();
    return '' === $id ? new WP_Error( 'no_setup', 'Start onboarding first.', array( 'status' => 400 ) ) : $id;
}

add_action( 'rest_api_init', function () {
    $ns   = 'rapidtextai/v1';
    $perm = 'rapidtextai_rest_permission';

    // The current setup (null when none), plus what was made here.
    register_rest_route( $ns, '/onboarding', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'callback'            => function () {
            $out = array( 'onboarding' => null, 'local' => rapidtextai_onboarding_local_view() );
            $id  = rapidtextai_onboarding_id();
            if ( '' !== $id && get_option( 'rapidtextai_api_key', '' ) ) {
                $res = rapidtextai_api_request( 'GET', 'v2/onboarding/' . $id, null, 30 );
                if ( is_wp_error( $res ) ) {
                    $status = (int) ( $res->get_error_data()['status'] ?? 0 );
                    if ( in_array( $status, array( 401, 404 ), true ) ) {
                        rapidtextai_onboarding_save_state( array() ); // another account or expired: start again
                        $out['local'] = rapidtextai_onboarding_local_view();
                        return rest_ensure_response( $out );
                    }
                    return $res;
                }
                $out['onboarding'] = $res['onboarding'] ?? null;
                $out['running']    = ! empty( $res['running'] );
            }
            return rest_ensure_response( $out );
        },
    ) );

    // Start (or resume) research on this site. The site address and the return page are always this site's own.
    register_rest_route( $ns, '/onboarding/start', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            if ( ! $r->get_param( 'restart' ) && '' !== rapidtextai_onboarding_id() ) {
                $res = rapidtextai_api_request( 'GET', 'v2/onboarding/' . rapidtextai_onboarding_id(), null, 30 );
                if ( ! is_wp_error( $res ) ) {
                    return rest_ensure_response( array( 'onboarding' => $res['onboarding'], 'resumed' => true ) );
                }
            }
            $res = rapidtextai_api_request( 'POST', 'v2/onboarding/start', array(
                'site_url'  => home_url( '/' ),
                'channel'   => 'wordpress',
                'admin_url' => rapidtextai_onboarding_page_url(),
            ), 60 );
            if ( is_wp_error( $res ) ) {
                return $res;
            }
            rapidtextai_onboarding_save_state( array( 'id' => (string) $res['onboarding']['id'], 'started' => time() ) );
            delete_option( RAPIDTEXTAI_ONBOARDING_SKIPPED_OPTION );
            return rest_ensure_response( array( 'onboarding' => $res['onboarding'], 'resumed' => ! empty( $res['resumed'] ) ) );
        },
    ) );

    register_rest_route( $ns, '/onboarding/steps/(?P<step>brand|competitors|keywords|questions|gaps|outlines|campaigns)/run', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $id = rapidtextai_onboarding_need_id();
            if ( is_wp_error( $id ) ) {
                return $id;
            }
            return rapidtextai_onboarding_respond( rapidtextai_api_request( 'POST', 'v2/onboarding/' . $id . '/steps/' . $r['step'] . '/run',
                array( 'chain' => (bool) $r->get_param( 'chain' ) ), 30 ) );
        },
    ) );

    // Edits on a finished step: brand fields, competitors to keep, keywords to keep.
    register_rest_route( $ns, '/onboarding/steps/(?P<step>brand|competitors|keywords)', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $id = rapidtextai_onboarding_need_id();
            if ( is_wp_error( $id ) ) {
                return $id;
            }
            $in   = (array) $r->get_json_params();
            $body = array();
            if ( isset( $in['brand'] ) && is_array( $in['brand'] ) ) {
                $body['brand'] = array_map( function ( $v ) {
                    return is_array( $v ) ? array_map( 'sanitize_text_field', array_map( 'strval', $v ) ) : sanitize_textarea_field( (string) $v );
                }, array_intersect_key( $in['brand'], array_flip( array( 'name', 'description', 'voice_description', 'tone', 'audience', 'language' ) ) ) );
            }
            if ( isset( $in['keep'] ) && is_array( $in['keep'] ) ) {
                $body['keep'] = array_values( array_map( 'sanitize_text_field', array_map( 'strval', $in['keep'] ) ) );
            }
            return rapidtextai_onboarding_respond( rapidtextai_api_request( 'POST', 'v2/onboarding/' . $id . '/steps/' . $r['step'], $body, 30 ) );
        },
    ) );

    register_rest_route( $ns, '/onboarding/jobs/(?P<job>\d+)', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $id = rapidtextai_onboarding_need_id();
            if ( is_wp_error( $id ) ) {
                return $id;
            }
            return rapidtextai_onboarding_respond( rapidtextai_api_request( 'GET', 'v2/onboarding/' . $id . '/jobs/' . (int) $r['job'], null, 20 ) );
        },
    ) );

    // Write the first articles (paid from the setup's own budget).
    register_rest_route( $ns, '/onboarding/drafts', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function () {
            $id = rapidtextai_onboarding_need_id();
            if ( is_wp_error( $id ) ) {
                return $id;
            }
            return rapidtextai_onboarding_respond( rapidtextai_api_request( 'POST', 'v2/onboarding/' . $id . '/drafts', new stdClass(), 30 ) );
        },
    ) );

    register_rest_route( $ns, '/onboarding/plans', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'callback'            => function () {
            return rapidtextai_onboarding_respond( rapidtextai_api_request( 'GET', 'v2/onboarding/plans', null, 20 ) );
        },
    ) );

    // Free (or already paying): finishes now and imports. Paid: returns Stripe's checkout_url.
    register_rest_route( $ns, '/onboarding/plan', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $id = rapidtextai_onboarding_need_id();
            if ( is_wp_error( $id ) ) {
                return $id;
            }
            $res = rapidtextai_api_request( 'POST', 'v2/onboarding/' . $id . '/plan', array(
                'plan' => sanitize_key( (string) ( $r->get_param( 'plan' ) ?: 'free' ) ),
            ), 60 );
            if ( is_wp_error( $res ) ) {
                return $res;
            }
            if ( ! empty( $res['checkout_url'] ) ) {
                $url = esc_url_raw( (string) $res['checkout_url'] );
                if ( 0 !== strpos( $url, 'https://checkout.stripe.com/' ) ) {
                    return new WP_Error( 'bad_checkout', 'The checkout page could not be opened.', array( 'status' => 502 ) );
                }
                return rest_ensure_response( array( 'done' => false, 'checkout_url' => $url ) );
            }
            $local = rapidtextai_onboarding_import();
            return rest_ensure_response( array( 'done' => true, 'local' => is_wp_error( $local ) ? null : $local,
                'import_error' => is_wp_error( $local ) ? $local->get_error_message() : '' ) );
        },
    ) );

    // Back from Stripe: confirm the payment, then import.
    register_rest_route( $ns, '/onboarding/checkout', array(
        'methods'             => 'GET',
        'permission_callback' => $perm,
        'args'                => array( 'session_id' => array( 'type' => 'string', 'required' => false ) ),
        'callback'            => function ( WP_REST_Request $r ) {
            $id = rapidtextai_onboarding_need_id();
            if ( is_wp_error( $id ) ) {
                return $id;
            }
            $sid  = preg_replace( '/[^A-Za-z0-9_]/', '', (string) $r->get_param( 'session_id' ) );
            $path = 'v2/onboarding/' . $id . '/checkout/status' . ( '' !== $sid ? '?session_id=' . rawurlencode( $sid ) : '' );
            $res  = rapidtextai_api_request( 'GET', $path, null, 30 );
            if ( is_wp_error( $res ) ) {
                return $res;
            }
            if ( 'complete' !== ( $res['status'] ?? '' ) ) {
                return rest_ensure_response( array( 'status' => (string) ( $res['status'] ?? 'open' ) ) );
            }
            $local = rapidtextai_onboarding_import();
            if ( is_wp_error( $local ) && 'importing' === $local->get_error_code() ) {
                return rest_ensure_response( array( 'status' => 'importing' ) ); // the other tab is adding them; ask again
            }
            return rest_ensure_response( array( 'status' => 'complete', 'local' => is_wp_error( $local ) ? null : $local,
                'import_error' => is_wp_error( $local ) ? $local->get_error_message() : '' ) );
        },
    ) );

    // Retry the import (for example after a timeout while copying images).
    register_rest_route( $ns, '/onboarding/import', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function () {
            return rapidtextai_onboarding_respond( rapidtextai_onboarding_import() );
        },
    ) );

    // Switch on (or off) the campaigns made by onboarding.
    register_rest_route( $ns, '/onboarding/campaigns', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            $on        = (bool) $r->get_param( 'enabled' );
            $campaigns = rapidtextai_v2_get_campaigns();
            foreach ( (array) rapidtextai_onboarding_state()['campaigns'] as $cid ) {
                if ( ! empty( $campaigns[ $cid ] ) ) {
                    $campaigns[ $cid ]['enabled'] = $on;
                }
            }
            rapidtextai_v2_save_campaigns( $campaigns );
            foreach ( (array) rapidtextai_onboarding_state()['campaigns'] as $cid ) {
                rapidtextai_v2_sync_schedule( $cid );
            }
            return rest_ensure_response( rapidtextai_onboarding_local_view() );
        },
    ) );

    // "Skip, I'll set things up myself" (or "Start over" with forget=1).
    register_rest_route( $ns, '/onboarding/skip', array(
        'methods'             => 'POST',
        'permission_callback' => $perm,
        'callback'            => function ( WP_REST_Request $r ) {
            update_option( RAPIDTEXTAI_ONBOARDING_SKIPPED_OPTION, 1, false );
            if ( $r->get_param( 'forget' ) ) {
                rapidtextai_onboarding_save_state( array() );
            }
            return rest_ensure_response( array( 'skipped' => true, 'dashboard_url' => admin_url( 'admin.php?page=rapidtextai-dashboard' ) ) );
        },
    ) );
} );

/* ───────────────────────────────────────────────────────── Menu */

add_action( 'admin_menu', function () {
    add_submenu_page(
        'rapidtextai-settings',
        'Onboard your website',
        'Onboard your website',
        'manage_options',
        'rapidtextai-onboard',
        'rapidtextai_onboarding_page'
    );
}, 998 );

function rapidtextai_onboarding_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    $key        = get_option( 'rapidtextai_api_key', '' );
    $connection = get_option( 'rapidtextai_connection', array() );
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Stripe's return; only read and re-checked with RapidTextAI.
    $session_id = isset( $_GET['session_id'] ) ? preg_replace( '/[^A-Za-z0-9_]/', '', sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) ) : '';
    $cancelled  = isset( $_GET['checkout'] ) && 'cancelled' === sanitize_key( wp_unslash( $_GET['checkout'] ) );
    // phpcs:enable
    rapidtextai_react_app_root( array(
        'page' => 'onboard',
        'data' => array(
            'api_key_set'   => ! empty( $key ),
            'site_url'      => home_url(),
            'site_name'     => get_bloginfo( 'name' ),
            'connection'    => is_array( $connection ) ? $connection : array(),
            'local'         => rapidtextai_onboarding_local_view(),
            'skipped'       => (bool) get_option( RAPIDTEXTAI_ONBOARDING_SKIPPED_OPTION ),
            'session_id'    => $session_id,
            'cancelled'     => $cancelled,
            'page_url'      => rapidtextai_onboarding_page_url(),
            'dashboard_url' => admin_url( 'admin.php?page=rapidtextai-dashboard' ),
            'campaigns_url' => admin_url( 'admin.php?page=rapidtextai-auto-blogging' ),
        ),
    ) );
}

/** A site with no key lands on onboarding (the big "Connect your website" start) until it is skipped. */
add_action( 'admin_init', function () {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
    if ( 'rapidtextai-dashboard' !== $page || ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
        return;
    }
    if ( get_option( 'rapidtextai_api_key', '' ) || get_option( RAPIDTEXTAI_ONBOARDING_SKIPPED_OPTION ) ) {
        return;
    }
    wp_safe_redirect( rapidtextai_onboarding_page_url() );
    exit;
}, 5 );
