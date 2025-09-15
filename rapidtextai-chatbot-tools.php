<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Simple registry for PHP callback tools used by the chatbot.
 */

global $rapidtextai_tool_callbacks;
$rapidtextai_tool_callbacks = [];

/**
 * Register a callback that the chatbot can invoke.
 *
 * @param string   $name     Unique tool name.
 * @param callable $callback Function to execute when the tool is called.
 */
function rapidtextai_register_tool_callback( $name, $callback ) {
    global $rapidtextai_tool_callbacks;
    $rapidtextai_tool_callbacks[ $name ] = $callback;
}

/**
 * Execute a registered tool callback.
 *
 * @param string $name Tool name.
 * @param array  $args Arguments passed from the chatbot.
 *
 * @return mixed|\WP_Error
 */
function rapidtextai_execute_tool( $name, $args = [] ) {
    global $rapidtextai_tool_callbacks;

    if ( isset( $rapidtextai_tool_callbacks[ $name ] ) && is_callable( $rapidtextai_tool_callbacks[ $name ] ) ) {
        return call_user_func( $rapidtextai_tool_callbacks[ $name ], $args );
    }

    return new WP_Error( 'rapidtextai_tool_not_found', __( 'Tool not registered', 'rapidtextai' ) );
}

/**
 * Persist a PHP code snippet so it can be used as a tool later.
 *
 * @param string $name     Unique snippet name.
 * @param string $php_code PHP code for the callback body. It should return a value.
 */
function rapidtextai_save_tool_snippet( $name, $php_code ) {
    $snippets = get_option( 'rapidtextai_tool_snippets', [] );
    $snippets[ $name ] = $php_code;
    update_option( 'rapidtextai_tool_snippets', $snippets );
}

/**
 * Load saved snippets and register them as callbacks.
 */
function rapidtextai_load_tool_snippets() {
    $snippets = get_option( 'rapidtextai_tool_snippets', [] );

    foreach ( $snippets as $name => $php_code ) {
        $callback = function( $args ) use ( $php_code ) {
            return eval( $php_code );
        };
        rapidtextai_register_tool_callback( $name, $callback );
    }
}
add_action( 'init', 'rapidtextai_load_tool_snippets' );

/**
 * Example booking tool callback.
 *
 * @param array $args {
 *     @type string $name Guest name.
 *     @type string $date Booking date in YYYY-MM-DD format.
 * }
 *
 * @return array
 */
function rapidtextai_book_room_tool( $args ) {
    $name = sanitize_text_field( $args['name'] ?? '' );
    $date = sanitize_text_field( $args['date'] ?? '' );

    if ( empty( $name ) || empty( $date ) ) {
        return [
            'status'  => 'error',
            'message' => __( 'Missing name or date', 'rapidtextai' ),
        ];
    }

    // This is only a demonstration. In a real scenario you would interact with your booking system.
    return [
        'status'  => 'success',
        'message' => sprintf( __( 'Booked %1$s on %2$s', 'rapidtextai' ), $name, $date ),
    ];
}
rapidtextai_register_tool_callback( 'book_room', 'rapidtextai_book_room_tool' );

