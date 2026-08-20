<?php
/**
 * RapidTextAI Campaign Assistant
 *
 * A guided AI assistant that helps users define high-quality auto-blogging
 * campaigns by:
 *   1. Gathering WordPress site context (SEO plugin data, recent posts,
 *      taxonomy, past campaigns) — cached in a transient.
 *   2. Feeding that context to an LLM as a system prompt.
 *   3. Running a guided chat to define topic / audience / keywords / tone.
 *   4. Outputting a structured campaign object mapped to the plugin's
 *      existing campaign schema.
 *
 * LLM calls are abstracted behind RapidTextAI_LLM_Client, and SEO plugin
 * access behind RapidTextAI_SEO_Reader, so both can be extended.
 *
 * @package RapidTextAI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ───────────────────────────────────────────────────────────
 * 1. SEO plugin reader (interface + adapters)
 * ─────────────────────────────────────────────────────────── */

interface RapidTextAI_SEO_Reader {
	public function id();
	public function label();
	public function is_active();
	public function site_keywords();
	public function meta_templates();
	public function post_keyphrase( $post_id );
	public function post_seo_score( $post_id );
}

class RapidTextAI_SEO_Yoast implements RapidTextAI_SEO_Reader {
	public function id() { return 'yoast'; }
	public function label() { return 'Yoast SEO'; }
	public function is_active() { return defined( 'WPSEO_VERSION' ); }

	public function site_keywords() {
		// Yoast has no single site-wide focus keyword; leave empty (best effort).
		return array();
	}

	public function meta_templates() {
		$out = array();
		if ( class_exists( 'WPSEO_Options' ) ) {
			$out['title']       = (string) WPSEO_Options::get( 'title-home-wpseo', '' );
			$out['description'] = (string) WPSEO_Options::get( 'metadesc-home-wpseo', '' );
		}
		return $out;
	}

	public function post_keyphrase( $post_id ) {
		return class_exists( 'WPSEO_Meta' ) ? (string) WPSEO_Meta::get_value( 'focuskw', $post_id ) : '';
	}

	public function post_seo_score( $post_id ) {
		if ( ! class_exists( 'WPSEO_Meta' ) ) {
			return null;
		}
		$score = WPSEO_Meta::get_value( 'linkdex', $post_id );
		return ( '' === $score || false === $score ) ? null : (int) $score;
	}
}

class RapidTextAI_SEO_RankMath implements RapidTextAI_SEO_Reader {
	public function id() { return 'rankmath'; }
	public function label() { return 'Rank Math'; }
	public function is_active() { return class_exists( 'RankMath' ); }

	public function site_keywords() {
		return array();
	}

	public function meta_templates() {
		$out  = array();
		$opts = get_option( 'rank-math-options-titles', array() );
		if ( is_array( $opts ) ) {
			$out['title']       = isset( $opts['homepage_title'] ) ? (string) $opts['homepage_title'] : '';
			$out['description'] = isset( $opts['homepage_description'] ) ? (string) $opts['homepage_description'] : '';
		}
		return $out;
	}

	public function post_keyphrase( $post_id ) {
		return (string) get_post_meta( $post_id, 'rank_math_focus_keyword', true );
	}

	public function post_seo_score( $post_id ) {
		$score = get_post_meta( $post_id, 'rank_math_seo_score', true );
		return ( '' === $score ) ? null : (int) $score;
	}
}

class RapidTextAI_SEO_AIOSEO implements RapidTextAI_SEO_Reader {
	public function id() { return 'aioseo'; }
	public function label() { return 'All in One SEO'; }
	public function is_active() { return function_exists( 'aioseo' ); }

	public function site_keywords() {
		$out = array();
		if ( function_exists( 'aioseo' ) ) {
			try {
				$kw = aioseo()->options->searchAppearance->advanced->keywords;
				if ( is_string( $kw ) && '' !== trim( $kw ) ) {
					$out = array_filter( array_map( 'trim', explode( ',', $kw ) ) );
				}
			} catch ( Throwable $e ) {
				// ignore
			}
		}
		return array_values( $out );
	}

	public function meta_templates() {
		$out = array();
		if ( function_exists( 'aioseo' ) ) {
			try {
				$out['title']       = (string) aioseo()->options->searchAppearance->global->siteTitle;
				$out['description'] = (string) aioseo()->options->searchAppearance->global->metaDescription;
			} catch ( Throwable $e ) {
				// ignore
			}
		}
		return $out;
	}

	public function post_keyphrase( $post_id ) {
		return (string) get_post_meta( $post_id, '_aioseo_keywords', true );
	}

	public function post_seo_score( $post_id ) {
		return null;
	}
}

class RapidTextAI_SEO_SEOPress implements RapidTextAI_SEO_Reader {
	public function id() { return 'seopress'; }
	public function label() { return 'SEOPress'; }
	public function is_active() { return defined( 'SEOPRESS_VERSION' ); }

	public function site_keywords() {
		return array();
	}

	public function meta_templates() {
		$out  = array();
		$opts = get_option( 'seopress_titles_option_name', array() );
		if ( is_array( $opts ) ) {
			$out['title']       = isset( $opts['seopress_titles_home_site_title'] ) ? (string) $opts['seopress_titles_home_site_title'] : '';
			$out['description'] = isset( $opts['seopress_titles_home_site_desc'] ) ? (string) $opts['seopress_titles_home_site_desc'] : '';
		}
		return $out;
	}

	public function post_keyphrase( $post_id ) {
		return (string) get_post_meta( $post_id, '_seopress_analysis_target_kw', true );
	}

	public function post_seo_score( $post_id ) {
		return null;
	}
}

class RapidTextAI_SEO_Reader_Factory {
	public static function detect() {
		$readers = array(
			new RapidTextAI_SEO_Yoast(),
			new RapidTextAI_SEO_RankMath(),
			new RapidTextAI_SEO_AIOSEO(),
			new RapidTextAI_SEO_SEOPress(),
		);
		foreach ( $readers as $reader ) {
			if ( $reader->is_active() ) {
				return $reader;
			}
		}
		return null;
	}
}

/* ───────────────────────────────────────────────────────────
 * 2. Campaign context builder
 * ─────────────────────────────────────────────────────────── */

class Campaign_Context_Builder {
	const CACHE_KEY = 'rapidtextai_assistant_context';
	const CACHE_TTL = HOUR_IN_SECONDS;

	public function get_context() {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$context = $this->build();
		set_transient( self::CACHE_KEY, $context, self::CACHE_TTL );
		return $context;
	}

	public function invalidate() {
		delete_transient( self::CACHE_KEY );
	}

	public function build() {
		return array(
			'site'            => $this->build_site_info(),
			'seo'             => $this->build_seo_info(),
			'existing_topics' => $this->build_recent_posts(),
			'taxonomy'        => $this->build_taxonomy(),
			'past_campaigns'  => $this->build_past_campaigns(),
		);
	}

	private function build_site_info() {
		$seo = RapidTextAI_SEO_Reader_Factory::detect();
		return array(
			'title'       => get_bloginfo( 'name' ),
			'description' => get_bloginfo( 'description' ),
			'language'    => get_locale(),
			'seo_plugin'  => $seo ? $seo->id() : null,
			'industry'    => $this->infer_industry(),
		);
	}

	private function build_seo_info() {
		$seo = RapidTextAI_SEO_Reader_Factory::detect();
		if ( ! $seo ) {
			return array( 'site_keywords' => array(), 'meta_template' => array() );
		}
		return array(
			'site_keywords' => $seo->site_keywords(),
			'meta_template' => $seo->meta_templates(),
		);
	}

	private function build_recent_posts() {
		$seo   = RapidTextAI_SEO_Reader_Factory::detect();
		$posts = get_posts( array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'numberposts' => 50,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'fields'      => 'ids',
		) );

		$out = array();
		foreach ( $posts as $post_id ) {
			$cats = wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );
			$tags = wp_get_post_tags( $post_id, array( 'fields' => 'names' ) );

			$out[] = array(
				'title'      => get_the_title( $post_id ),
				'keyphrase'  => $seo ? $seo->post_keyphrase( $post_id ) : '',
				'seo_score'  => $seo ? $seo->post_seo_score( $post_id ) : null,
				'categories' => is_array( $cats ) ? array_values( $cats ) : array(),
				'tags'       => is_array( $tags ) ? array_values( $tags ) : array(),
				'date'       => get_the_date( 'Y-m-d', $post_id ),
			);
		}
		return $out;
	}

	private function build_taxonomy() {
		return array(
			'categories' => $this->build_terms( 'category' ),
			'tags'       => $this->build_terms( 'post_tag' ),
		);
	}

	private function build_terms( $taxonomy ) {
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 100,
		) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $term ) {
			$out[] = array( 'name' => $term->name, 'count' => (int) $term->count );
		}
		return $out;
	}

	private function build_past_campaigns() {
		$campaigns = get_option( 'rapidtextai_auto_blogging_campaigns', array() );
		if ( ! is_array( $campaigns ) ) {
			return array();
		}
		$out = array();
		foreach ( $campaigns as $campaign_id => $campaign ) {
			$out[] = array(
				'id'       => (string) $campaign_id,
				'name'     => isset( $campaign['name'] ) ? $campaign['name'] : '',
				'topics'   => isset( $campaign['topics'] ) ? $campaign['topics'] : '',
				'schedule' => isset( $campaign['schedule'] ) ? $campaign['schedule'] : '',
				'model'    => isset( $campaign['model'] ) ? $campaign['model'] : '',
				'tone'     => isset( $campaign['tone'] ) ? $campaign['tone'] : '',
			);
		}
		return $out;
	}

	private function infer_industry() {
		if ( class_exists( 'WooCommerce' ) ) {
			return 'ecommerce';
		}
		$cats = get_categories( array(
			'hide_empty' => false,
			'number'     => 1,
			'orderby'    => 'count',
			'order'      => 'DESC',
		) );
		if ( ! empty( $cats ) ) {
			return (string) $cats[0]->name;
		}
		return '';
	}
}

/* ───────────────────────────────────────────────────────────
 * 3. LLM client (interface + RapidTextAI stream implementation)
 * ─────────────────────────────────────────────────────────── */

interface RapidTextAI_LLM_Client {
	/**
	 * Stream a chat completion.
	 *
	 * @param array    $messages  OpenAI-format messages.
	 * @param callable $on_chunk  Optional callback( $delta, $accumulated ).
	 * @param array    $options   Optional overrides (model, temperature, max_tokens).
	 * @return string Accumulated completion text.
	 */
	public function stream_chat( array $messages, $on_chunk = null, array $options = array() );
}

class RapidTextAI_LLM_Client_HTTP implements RapidTextAI_LLM_Client {
	private $api_key;

	public function __construct( $api_key ) {
		$this->api_key = $api_key;
	}

	public function stream_chat( array $messages, $on_chunk = null, array $options = array() ) {
		$url  = 'https://app.rapidtextai.com/openai/v1/chat/completions-stream?gigsixkey=' . urlencode( $this->api_key );
		$body = wp_json_encode( array_merge( array(
			'model'       => isset( $options['model'] ) ? $options['model'] : 'gpt-3.5-turbo',
			'messages'    => $messages,
			'stream'      => true,
			'temperature' => isset( $options['temperature'] ) ? $options['temperature'] : 0.7,
			'max_tokens'  => isset( $options['max_tokens'] ) ? $options['max_tokens'] : 2000,
		), $options ) );

		$accumulated = '';
		$buffer      = '';

		$ch = curl_init( $url );
		curl_setopt_array( $ch, array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_HTTPHEADER     => array(
				'Content-Type: application/json',
				'Accept: text/event-stream',
			),
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_TIMEOUT        => 300,
			CURLOPT_CONNECTTIMEOUT => 30,
			CURLOPT_WRITEFUNCTION  => function ( $curl, $data ) use ( &$accumulated, &$buffer, $on_chunk ) {
				$buffer .= $data;
				while ( ( $pos = strpos( $buffer, "\n\n" ) ) !== false ) {
					$block  = substr( $buffer, 0, $pos );
					$buffer = substr( $buffer, $pos + 2 );

					foreach ( explode( "\n", $block ) as $line ) {
						if ( strpos( $line, 'data: ' ) !== 0 ) {
							continue;
						}
						$payload = trim( substr( $line, 6 ) );
						if ( '[DONE]' === $payload ) {
							continue;
						}
						$chunk = json_decode( $payload, true );
						if ( ! is_array( $chunk ) ) {
							continue;
						}
						$content = isset( $chunk['choices'][0]['delta']['content'] ) ? $chunk['choices'][0]['delta']['content'] : '';
						if ( '' !== $content ) {
							$accumulated .= $content;
							if ( is_callable( $on_chunk ) ) {
								$on_chunk( $content, $accumulated );
							}
						}
					}
				}
				return strlen( $data );
			},
		) );

		curl_exec( $ch );
		curl_close( $ch );

		return $accumulated;
	}
}

/* ───────────────────────────────────────────────────────────
 * 4. System prompt
 * ─────────────────────────────────────────────────────────── */

function rapidtextai_assistant_system_prompt( array $context ) {
	// Keep the prompt within a reasonable size.
	if ( isset( $context['existing_topics'] ) && is_array( $context['existing_topics'] ) && count( $context['existing_topics'] ) > 30 ) {
		$context['existing_topics'] = array_slice( $context['existing_topics'], 0, 30 );
	}
	if ( isset( $context['taxonomy']['tags'] ) && is_array( $context['taxonomy']['tags'] ) && count( $context['taxonomy']['tags'] ) > 200 ) {
		$context['taxonomy']['tags'] = array_slice( $context['taxonomy']['tags'], 0, 200 );
	}

	$json = wp_json_encode( $context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

	$prompt = <<<PROMPT
You are RapidTextAI's Campaign Assistant — an expert content strategist for WordPress blogs.

Below is the current context of the user's WordPress site as JSON. Use it; do not ignore it.

SITE_CONTEXT:
{$json}

Your goal is to help the user define one high-quality content campaign for this site.

Rules:
1. Never suggest topics or keywords that duplicate existing content (see "existing_topics"), unless the user explicitly asks for an update/refresh.
2. Infer the site niche and typical audience from the site title/description, categories, tags, and existing post topics. Mention your inference briefly when relevant.
3. Ask clarifying questions ONLY for what the context cannot answer (e.g. audience expertise level, B2B/B2C, geography, business goal, cadence, content format, topics to avoid). Do NOT ask things the context already answers.
4. When the user is ready, suggest 3-5 candidate topics. For each give: a primary keyword + 2-3 supporting keywords, and a one-line reason why it fits the audience or fills a content gap.
5. Let the user pick, tweak, reject, and iterate.
6. Each final topic must be written in the plugin's "advanced topic" format, one per line:
   Topic: [topic]; Keywords: [kw1, kw2, kw3]; Tone: [tone]; Audience: [audience]; CTA: [call to action]; Length: [word count]; Language: [language]
7. After suggesting topics, always tell the user that you can either create a brand-new campaign or update one of their existing campaigns (listed in "past_campaigns" with their id). Ask which they want; if updating, ask which campaign.
8. When the user confirms, output ONLY a JSON object inside a ```json code fence matching this campaign schema:
   For a NEW campaign:  {"name": "Short campaign name", "topics": ["Topic: ...; Keywords: ...; ..."], "schedule": "daily|weekly|twicedaily|hourly", "tone": "informative|conversational|formal|friendly|persuasive", "model": "gpt-3.5-turbo|gpt-4|gpt-5|gemini-2.0-flash|gemini-2.5-flash|claude-3-7-sonnet-latest|grok-3|glm-4.6v-flash|deepseek-v4-flash|deepseek-v4-pro", "post_status": "draft|publish|pending"}
   For an UPDATE:  add "campaign_id" set to the existing campaign's id (from "past_campaigns") and "name" set to that campaign's name. You may omit the other fields to keep the campaign's current settings.
9. Only emit the JSON after the user has confirmed their choice (create or update).

Keep replies concise and conversational. Do not invent facts about the site beyond what the context provides.
PROMPT;

	return $prompt;
}

/* ───────────────────────────────────────────────────────────
 * 5. Finalize: map assistant output to the plugin campaign schema
 * ─────────────────────────────────────────────────────────── */

function rapidtextai_assistant_finalize_campaign( $params ) {
	$campaign_id = isset( $params['campaign_id'] ) ? sanitize_text_field( $params['campaign_id'] ) : '';
	$topics      = isset( $params['topics'] ) && is_array( $params['topics'] ) ? array_filter( array_map( 'sanitize_textarea_field', $params['topics'] ) ) : array();

	$all      = get_option( 'rapidtextai_auto_blogging_campaigns', array() );
	$existing = ( '' !== $campaign_id && is_array( $all ) && isset( $all[ $campaign_id ] ) ) ? $all[ $campaign_id ] : null;

	$name = isset( $params['name'] ) && '' !== trim( (string) $params['name'] )
		? sanitize_text_field( $params['name'] )
		: ( $existing && isset( $existing['name'] ) ? $existing['name'] : '' );

	if ( '' === $name || empty( $topics ) ) {
		return new WP_Error( 'invalid_campaign', 'A campaign name and at least one topic are required.', array( 'status' => 400 ) );
	}

	if ( $existing ) {
		// Update: preserve all existing settings, applying only the changed fields.
		$campaign_params = array_merge( $existing, array(
			'name'   => $name,
			'topics' => implode( "\n", $topics ),
		) );
		foreach ( array( 'schedule', 'tone', 'model', 'post_status' ) as $key ) {
			if ( isset( $params[ $key ] ) && '' !== trim( (string) $params[ $key ] ) ) {
				$campaign_params[ $key ] = sanitize_text_field( $params[ $key ] );
			}
		}
	} else {
		// Create new with sensible defaults (saved disabled for review).
		$campaign_params = array(
			'name'                    => $name,
			'topics'                  => implode( "\n", $topics ),
			'schedule'                => isset( $params['schedule'] ) ? sanitize_text_field( $params['schedule'] ) : 'daily',
			'tone'                    => isset( $params['tone'] ) ? sanitize_text_field( $params['tone'] ) : 'informative',
			'model'                   => isset( $params['model'] ) ? sanitize_text_field( $params['model'] ) : 'gpt-3.5-turbo',
			'post_status'             => isset( $params['post_status'] ) ? sanitize_text_field( $params['post_status'] ) : 'draft',
			'enabled'                 => 0,
			'post_author'             => get_current_user_id(),
			'post_category'           => array(),
			'generate_tags'           => 1,
			'tags_count'              => 5,
			'excerpt_length'          => 55,
			'taxonomy_limit'          => 5,
			'include_images'          => 1,
			'include_featured_image'  => 0,
			'max_images'              => 5,
			'enable_logging'          => 0,
		);
	}

	// Hand off to the existing campaign save routine (defined in rapidtextai-rest-api.php).
	if ( function_exists( 'rapidtextai_rest_save_campaign' ) ) {
		$result = rapidtextai_rest_save_campaign( $campaign_params );
	} else {
		return new WP_Error( 'missing_handler', 'Campaign save handler not available.', array( 'status' => 500 ) );
	}

	if ( ! is_array( $result ) || empty( $result['id'] ) ) {
		return new WP_Error( 'save_failed', 'Could not save the campaign.', array( 'status' => 500 ) );
	}

	// Refresh the cached site context so the change is known next time.
	$builder = new Campaign_Context_Builder();
	$builder->invalidate();

	return $result;
}

/* ───────────────────────────────────────────────────────────
 * 6. REST + AJAX endpoints
 * ─────────────────────────────────────────────────────────── */

add_action( 'rest_api_init', function () {
	$ns = 'rapidtextai/v1';

	register_rest_route( $ns, '/assistant/context', array(
		'methods'             => 'GET',
		'permission_callback' => 'rapidtextai_rest_permission',
		'callback'            => function () {
			$builder = new Campaign_Context_Builder();
			return rest_ensure_response( $builder->get_context() );
		},
	) );

	register_rest_route( $ns, '/assistant/finalize', array(
		'methods'             => 'POST',
		'permission_callback' => 'rapidtextai_rest_permission',
		'callback'            => function ( WP_REST_Request $request ) {
			$params = $request->get_json_params();
			if ( ! is_array( $params ) ) {
				$params = $request->get_params();
			}
			$result = rapidtextai_assistant_finalize_campaign( $params );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
			return rest_ensure_response( $result );
		},
	) );
} );

// Streaming chat (Server-Sent Events) via admin-ajax so the dock can render tokens live.
add_action( 'wp_ajax_rapidtextai_assistant_chat', 'rapidtextai_assistant_chat_callback' );
function rapidtextai_assistant_chat_callback() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied.' ), 403 );
	}

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'rapidtextai_assistant_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
	}

	$messages_raw = isset( $_POST['messages'] ) ? wp_unslash( $_POST['messages'] ) : '';
	$messages     = json_decode( $messages_raw, true );
	if ( ! is_array( $messages ) ) {
		wp_send_json_error( array( 'message' => 'Invalid conversation.' ), 400 );
	}

	$api_key = get_option( 'rapidtextai_api_key', '' );
	if ( '' === $api_key ) {
		wp_send_json_error( array( 'message' => 'RapidTextAI API key is not configured.' ), 400 );
	}

	$builder = new Campaign_Context_Builder();
	$context = $builder->get_context();
	$system  = rapidtextai_assistant_system_prompt( $context );

	array_unshift( $messages, array( 'role' => 'system', 'content' => $system ) );

	// Disable output buffering/compression that would delay the stream.
	@ini_set( 'zlib.output_compression', '0' );
	@ini_set( 'output_buffering', 'off' );
	while ( ob_get_level() > 0 ) {
		ob_end_flush();
	}

	header( 'Content-Type: text/event-stream; charset=utf-8' );
	header( 'Cache-Control: no-cache' );
	header( 'X-Accel-Buffering: no' );

	$llm = new RapidTextAI_LLM_Client_HTTP( $api_key );
	$llm->stream_chat( $messages, function ( $delta ) {
		echo 'data: ' . wp_json_encode( array( 'content' => $delta ) ) . "\n\n";
		flush();
	} );

	echo "data: [DONE]\n\n";
	exit;
}
