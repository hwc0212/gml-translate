<?php
require_once __DIR__ . '/../bootstrap-mock.php';
require_once __DIR__ . '/../../gml-translate.php';

GML_Translate_Test_State::reset();
GML_Translate_Test_State::$options['gml_translation_enabled'] = true;
gml_test_assert( GML_Translation_State::multilingual_enabled(), 'legacy enabled state keeps multilingual output on' );
gml_test_assert( GML_Translation_State::ai_translation_enabled(), 'legacy enabled state is inherited for AI during upgrade' );
gml_test_assert( ! GML_Translation_State::ai_available(), 'AI is unavailable without credentials' );
gml_test_assert( GML_Translation_State::multilingual_enabled(), 'missing credentials do not disable multilingual output' );

GML_Translation_State::set_ai_translation_enabled( false );
gml_test_assert( ! GML_Translation_State::ai_translation_enabled(), 'AI can be disabled independently' );
gml_test_assert( GML_Translation_State::multilingual_enabled(), 'disabling AI preserves multilingual output' );

GML_Gemini_API::save_api_key( 'test-only-key', 'gemini' );
GML_Translation_State::set_ai_translation_enabled( true );
gml_test_assert( GML_Translation_State::ai_available(), 'AI becomes available with explicit switch and credentials' );

GML_Translation_State::set_multilingual_enabled( false );
gml_test_assert( ! GML_Translation_State::multilingual_enabled(), 'multilingual site can be disabled explicitly' );
gml_test_assert( GML_Translation_State::ai_available(), 'site output and provider availability remain separate states' );
gml_test_assert( ! GML_Translation_State::work_enabled(), 'workers do not run while multilingual output is disabled' );

echo "OK test-translation-state\n";
