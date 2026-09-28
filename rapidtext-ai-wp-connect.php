<?php
/**
 * Connect this WordPress site to a RapidTextAI account.
 *
 * This site acts as the identity provider. When an administrator clicks
 * "Connect WordPress account" in the plugin settings we mint a short-lived,
 * single-use token, remember which WordPress user asked for it, and open a
 * popup on app.rapidtextai.com. RapidTextAI then calls back into this site
 * over HTTPS to read that identity - nothing about the user is taken from the
 * browser.
 *
 * Endpoints:
 *   POST rapidtextai/v1/connect/start      mint a token, return the popup URL
 *   POST rapidtextai/v1/connect/existing   mint a token for linking a legacy
 *                                          RapidTextAI account to this site
 *   POST rapidtextai/v1/connect/disconnect forget the account on this site
 *   GET  rapidtextai/v1/site-identity      public; the token is the credential
 *   GET  /?rapidtextai_verify=<token>      same payload, for hosts that block
 *                                          or rewrite /wp-json/
 *
 * @package RapidTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'RAPIDTEXTAI_CONNECT_APP_URL' ) ) {
	define( 'RAPIDTEXTAI_CONNECT_APP_URL', 'https://app.rapidtextai.com' );
}

/** How long a connection token stays valid, in seconds. */
if ( ! defined( 'RAPIDTEXTAI_CONNECT_TOKEN_TTL' ) ) {
	define( 'RAPIDTEXTAI_CONNECT_TOKEN_TTL', 600 );
}

/** Transient prefix. The full key is the prefix plus a 64 character token. */
if ( ! defined( 'RAPIDTEXTAI_CONNECT_TOKEN_PREFIX' ) ) {
	define( 'RAPIDTEXTAI_CONNECT_TOKEN_PREFIX', 'rapidtextai_connect_' );
}

/** Option holding the record of the last successful connection. */
if ( ! defined( 'RAPIDTEXTAI_CONNECT_OPTION' ) ) {
	define( 'RAPIDTEXTAI_CONNECT_OPTION', 'rapidtextai_connection' );
}

/* =========================================================================
 * Token handling
 * ====================================================================== */

/**
 * Mint a single-use token and remember who asked for it.
 *
 * The user id is stored server-side and never travels through the browser, so
 * a caller cannot ask for somebody else's identity by editing the request.
 *
 * @param int    $user_id WordPress user the token belongs to.
 * @param string $intent  'connect' or 'link'.
 * @return string|WP_Error
 */
function rapidtextai_connect_create_token( $user_id, $intent = 'connect' ) {
	$user_id = (int) $user_id;
	if ( $user_id < 1 ) {
		return new WP_Error( 'no_user', 'No WordPress user to connect.', array( 'status' => 400 ) );
	}

	$token = wp_generate_password( 64, false, false );

	$stored = set_transient(
		RAPIDTEXTAI_CONNECT_TOKEN_PREFIX . $token,
		array(
			'user_id' => $user_id,
			'intent'  => (string) $intent,
			'created' => time(),
		),
		RAPIDTEXTAI_CONNECT_TOKEN_TTL
	);

	if ( ! $stored ) {
		return new WP_Error(
			'token_store_failed',
			'This site could not store the connection token. Object caching or the database may be unavailable.',
			array( 'status' => 500 )
		);
	}

	return $token;
}

/**
 * Validate and consume a connection token.
 *
 * The token is deleted before anything else happens, including on failure, so
 * a link can never be replayed.
 *
 * @param string $token
 * @return array array( 'ok' => bool, 'error' => string, ... )
 */
function rapidtextai_connect_identity_payload( $token ) {
	$token = (string) $token;

	if ( '' === $token || strlen( $token ) > 200 ) {
		return array( 'ok' => false, 'error' => 'invalid_token' );
	}

	$key  = RAPIDTEXTAI_CONNECT_TOKEN_PREFIX . $token;
	$data = get_transient( $key );

	if ( ! is_array( $data ) ) {
		return array( 'ok' => false, 'error' => 'invalid_token' );
	}

	delete_transient( $key );

	$user_id = isset( $data['user_id'] ) ? (int) $data['user_id'] : 0;
	if ( $user_id < 1 ) {
		return array( 'ok' => false, 'error' => 'no_user' );
	}

	$user = get_userdata( $user_id );
	if ( ! $user ) {
		return array( 'ok' => false, 'error' => 'no_user' );
	}

	// The button lives on a manage_options screen, but permissions can change
	// between minting the token and using it.
	if ( ! user_can( $user, 'manage_options' ) ) {
		return array( 'ok' => false, 'error' => 'insufficient_role' );
	}

	$intent = isset( $data['intent'] ) ? (string) $data['intent'] : 'connect';

	update_option(
		RAPIDTEXTAI_CONNECT_OPTION,
		array(
			'site_url'      => home_url(),
			'site_name'     => get_bloginfo( 'name' ),
			'wp_user_id'    => (int) $user->ID,
			'wp_user_login' => $user->user_login,
			'wp_user_email' => $user->user_email,
			'intent'        => $intent,
			'connected_at'  => time(),
		)
	);

	return array(
		'ok'        => true,
		'error'     => '',
		'site_url'  => home_url(),
		'site_name' => get_bloginfo( 'name' ),
		'intent'    => $intent,
		'user'      => array(
			'id'           => (int) $user->ID,
			'login'        => $user->user_login,
			'email'        => $user->user_email,
			'display_name' => $user->display_name,
		),
	);
}

/* =========================================================================
 * Public identity endpoints
 * ====================================================================== */

add_action( 'rest_api_init', 'rapidtextai_connect_register_routes' );

/**
 * Register the connect routes.
 *
 * /site-identity has to be reachable without a WordPress session: the caller
 * is RapidTextAI's server and the one-time token is the credential.
 */
function rapidtextai_connect_register_routes() {
	$admin_only = 'rapidtextai_rest_permission';

	register_rest_route(
		'rapidtextai/v1',
		'/site-identity',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'args'                => array(
				'token' => array(
					'required' => true,
					'type'     => 'string',
				),
			),
			'callback'            => 'rapidtextai_connect_rest_site_identity',
		)
	);

	register_rest_route(
		'rapidtextai/v1',
		'/connect/start',
		array(
			'methods'             => 'POST',
			'permission_callback' => $admin_only,
			'callback'            => 'rapidtextai_connect_rest_start',
		)
	);

	register_rest_route(
		'rapidtextai/v1',
		'/connect/existing',
		array(
			'methods'             => 'POST',
			'permission_callback' => $admin_only,
			'callback'            => 'rapidtextai_connect_rest_existing',
		)
	);

	register_rest_route(
		'rapidtextai/v1',
		'/connect/disconnect',
		array(
			'methods'             => 'POST',
			'permission_callback' => $admin_only,
			'callback'            => 'rapidtextai_connect_rest_disconnect',
		)
	);

	register_rest_route(
		'rapidtextai/v1',
		'/connect/status',
		array(
			'methods'             => 'GET',
			'permission_callback' => $admin_only,
			'callback'            => 'rapidtextai_connect_rest_status',
		)
	);
}

/**
 * Hand back the WordPress identity behind a token.
 *
 * Always HTTP 200 with an `ok` flag, so the caller can tell "this token is
 * spent" apart from "this site is not running the plugin".
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function rapidtextai_connect_rest_site_identity( WP_REST_Request $request ) {
	$payload = rapidtextai_connect_identity_payload( $request->get_param( 'token' ) );

	$response = rest_ensure_response( $payload );
	$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
	$response->header( 'X-Robots-Tag', 'noindex' );

	return $response;
}

/**
 * Fallback for hosts that block, rewrite or disable /wp-json/.
 *
 * Serves the same payload from /?rapidtextai_verify=<token>. Hooked to init so
 * no theme output gets in the way.
 */
add_action( 'init', 'rapidtextai_connect_maybe_serve_identity', 1 );

function rapidtextai_connect_maybe_serve_identity() {
	if ( empty( $_GET['rapidtextai_verify'] ) ) {
		return;
	}

	$token   = sanitize_text_field( wp_unslash( $_GET['rapidtextai_verify'] ) );
	$payload = rapidtextai_connect_identity_payload( $token );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );

	echo wp_json_encode( $payload );
	exit;
}

/* =========================================================================
 * Admin-facing endpoints used by the settings screen
 * ====================================================================== */

/** Start a connect flow: sign in / register with this site's WordPress user. */
function rapidtextai_connect_rest_start( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', 'You are not allowed to connect this site.', array( 'status' => 403 ) );
	}

	$token = rapidtextai_connect_create_token( $user_id, 'connect' );
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$url = add_query_arg(
		array(
			'site'   => home_url(),
			'token'  => $token,
			'action' => 'popup',
		),
		RAPIDTEXTAI_CONNECT_APP_URL . '/wp-connect'
	);

	return rest_ensure_response(
		array(
			'url'        => $url,
			'expires_in' => RAPIDTEXTAI_CONNECT_TOKEN_TTL,
		)
	);
}

/**
 * Link this site to a RapidTextAI account that already exists.
 *
 * Opens the normal sign-in page with a pending site link attached. Whichever
 * account signs in gets this site attached to it, which is what makes a legacy
 * account keep working after the switch to WordPress sign-in.
 */
function rapidtextai_connect_rest_existing( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', 'You are not allowed to connect this site.', array( 'status' => 403 ) );
	}

	$token = rapidtextai_connect_create_token( $user_id, 'link' );
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$url = add_query_arg(
		array(
			'action'     => 'popup',
			'link_site'  => home_url(),
			'link_token' => $token,
		),
		RAPIDTEXTAI_CONNECT_APP_URL . '/log-in'
	);

	return rest_ensure_response(
		array(
			'url'        => $url,
			'expires_in' => RAPIDTEXTAI_CONNECT_TOKEN_TTL,
		)
	);
}

/** Forget the RapidTextAI account on this site. */
function rapidtextai_connect_rest_disconnect( WP_REST_Request $request ) {
	$user_id = get_current_user_id();
	if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'forbidden', 'You are not allowed to disconnect this site.', array( 'status' => 403 ) );
	}

	delete_option( 'rapidtextai_api_key' );
	delete_option( RAPIDTEXTAI_CONNECT_OPTION );

	return rest_ensure_response(
		array(
			'message' => 'This site is no longer connected to a RapidTextAI account.',
		)
	);
}

/** Current connection record plus whether an API key is present. */
function rapidtextai_connect_rest_status( WP_REST_Request $request ) {
	$record = get_option( RAPIDTEXTAI_CONNECT_OPTION, array() );

	return rest_ensure_response(
		array(
			'site_url'    => home_url(),
			'site_name'   => get_bloginfo( 'name' ),
			'api_key_set' => ( '' !== (string) get_option( 'rapidtextai_api_key', '' ) ),
			'connection'  => is_array( $record ) ? $record : array(),
		)
	);
}

/* =========================================================================
 * Linking this site to an existing account
 * ====================================================================== */

/**
 * Tell RapidTextAI that this site belongs to the account behind an API key.
 *
 * Called straight after an API key is saved. That is what makes "Connect
 * existing RapidTextAI account" attach this site to the legacy account rather
 * than leaving the site and the account unrelated.
 *
 * The connection token is minted here and redeemed by RapidTextAI in the same
 * server-to-server call, so the authorisation is the API key itself.
 *
 * @param string $api_key
 * @return array|WP_Error Decoded response body on success.
 */
function rapidtextai_connect_link_site_for_key( $api_key ) {
	$api_key = trim( (string) $api_key );

	if ( '' === $api_key ) {
		return new WP_Error( 'no_key', 'There is no API key to link this site with.' );
	}

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return new WP_Error( 'no_user', 'No WordPress user to link this site with.' );
	}

	$token = rapidtextai_connect_create_token( $user_id, 'link' );
	if ( is_wp_error( $token ) ) {
		return $token;
	}

	$response = wp_remote_post(
		RAPIDTEXTAI_CONNECT_APP_URL . '/api.php?action=link-site',
		array(
			'timeout'   => 20,
			'sslverify' => false, // Matches every other request this plugin makes.
			'body'      => array(
				'gigsixkey' => $api_key,
				'site'      => home_url(),
				'token'     => $token,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $body ) ) {
		return new WP_Error( 'bad_response', 'RapidTextAI returned an unexpected response while linking this site.' );
	}

	if ( empty( $body['response_code'] ) || 200 !== (int) $body['response_code'] ) {
		return new WP_Error(
			'link_failed',
			isset( $body['message'] ) && '' !== $body['message']
				? $body['message']
				: 'RapidTextAI could not link this site to that account.'
		);
	}

	return $body;
}
