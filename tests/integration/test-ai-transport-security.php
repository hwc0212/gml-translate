<?php
/** Regression: provider credentials never turn a configured endpoint into a leak target. */

require_once __DIR__ . '/../bootstrap-mock.php';

if ( ! defined( 'GML_PLUGIN_DIR' ) ) {
	define( 'GML_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}

require_once GML_PLUGIN_DIR . 'includes/class-gemini-api.php';

GML_Translate_Test_State::reset();

foreach ( [
	'http://127.0.0.1:8080',
	'https://127.0.0.1',
	'https://user:pass@api.deepseek.com',
	'https://api.deepseek.com?key=secret',
	'https://api.deepseek.com/#internal',
	'https://proxy.example.com/v1',
] as $unsafe ) {
	$result = GML_AI_HTTP_Transport::validate_endpoint( $unsafe, [ 'api.deepseek.com' ] );
	gml_test_assert( is_wp_error( $result ), 'reject unsafe or non-provider endpoint: ' . $unsafe );
}

gml_test_assert(
	GML_Gemini_API::secure_base_url( 'https://proxy.example.com/v1', GML_Gemini_API::DEEPSEEK_API_BASE ) === GML_Gemini_API::DEEPSEEK_API_BASE,
	'custom proxy cannot receive the saved DeepSeek credential'
);

$redacted = GML_AI_HTTP_Transport::redact( 'Authorization: Bearer secret-token sk-1234567890 AIza0123456789abcdefgh' );
gml_test_assert( strpos( $redacted, 'secret-token' ) === false, 'Bearer token is redacted' );
gml_test_assert( strpos( $redacted, 'sk-1234567890' ) === false, 'OpenAI-style key is redacted' );
gml_test_assert( strpos( $redacted, 'AIza0123456789abcdefgh' ) === false, 'Gemini-style key is redacted' );

$transport = new GML_AI_HTTP_Transport();
$oversized = $transport->post_json(
	'https://api.deepseek.com/v1/chat/completions',
	[ 'Authorization' => 'Bearer secret' ],
	[ 'prompt' => str_repeat( 'x', GML_AI_HTTP_Transport::MAX_REQUEST_BYTES + 1 ) ],
	[ 'allowed_hosts' => [ 'api.deepseek.com' ], 'retries' => 0 ]
);
gml_test_assert( ! $oversized['ok'] && $oversized['error']['code'] === 'request_too_large', 'oversized request is rejected before HTTP' );
gml_test_assert( GML_Translate_Test_State::$http_requests === [], 'oversized request performs no HTTP call' );

GML_Translate_Test_State::$http_responses[] = [
	'response' => [ 'code' => 200 ],
	'headers'  => [],
	'body'     => wp_json_encode( [
		'candidates' => [ [ 'content' => [ 'parts' => [ [ 'text' => '你好' ] ] ] ] ],
	] ),
];
$key_test = GML_Gemini_API::test_api_key( 'AIza0123456789abcdefgh', GML_Gemini_API::ENGINE_GEMINI );
gml_test_assert( $key_test['valid'], 'Gemini credential test accepts a valid mocked response' );

$request = GML_Translate_Test_State::$http_requests[0];
gml_test_assert( strpos( $request['url'], 'key=' ) === false, 'Gemini key is not placed in URL query' );
gml_test_assert( $request['args']['headers']['x-goog-api-key'] === 'AIza0123456789abcdefgh', 'Gemini key uses request header' );
gml_test_assert( $request['args']['redirection'] === 0, 'provider redirects are disabled' );
gml_test_assert( $request['args']['reject_unsafe_urls'] === true, 'WordPress unsafe URL checks remain enabled' );
gml_test_assert( $request['args']['limit_response_size'] === GML_AI_HTTP_Transport::MAX_RESPONSE_BYTES, 'response size is bounded' );

$provider = new GML_Gemini_API();
gml_test_assert( $provider instanceof GML_AI_Provider_Interface, 'standalone adapter implements shared provider contract' );

GML_Translate_Test_State::$http_requests = [];
$bounded_provider = new GML_Gemini_API( [ 'api_key' => 'AIza0123456789abcdefgh' ] );
$too_large = $bounded_provider->generate( [ 'prompt' => str_repeat( 'x', GML_Translation_AI_Client::MAX_PROMPT_BYTES + 1 ) ] );
gml_test_assert( ! $too_large['ok'] && $too_large['error']['code'] === 'request_too_large', 'translation client rejects oversized source content' );
gml_test_assert( GML_Translate_Test_State::$http_requests === [], 'oversized translation content performs no HTTP call' );

echo "OK test-ai-transport-security\n";
