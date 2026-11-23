<?php
require_once __DIR__.'/../bootstrap-mock.php';
$parser_file=getenv('GML_PARSER_UNDER_TEST')?:__DIR__.'/../../includes/vendor/gml-translation-core/src/class-html-parser.php';
require_once $parser_file;
$parser=new GML_HTML_Parser();
$cases=[];
foreach(['translate="no"','translate="NO"','class="notranslate"','class="test-exclude"','id="skip-region"'] as $rule) {
    GML_Translate_Test_State::$options['gml_exclude_selectors']=['.test-exclude','#skip-region'];
    $cases[]=['<section '.$rule.'><p>Shared sentence</p><span aria-label="Shared sentence">Shared sentence</span></section><p>Shared sentence</p>',
        '<section '.$rule.'><p>Shared sentence</p><span aria-label="Shared sentence">Shared sentence</span></section><p>Translated sentence</p>'];
    $cases[]=['<title '.$rule.'>Shared sentence</title><p>Shared sentence</p>',
        '<title '.$rule.'>Shared sentence</title><p>Translated sentence</p>'];
}
GML_Translate_Test_State::$options['gml_exclude_selectors']=['.test-exclude','#skip-region'];
$cases[]=['<pre><code data-example="</pre>">Shared sentence</code></pre><p>Shared sentence</p>','<pre><code data-example="</pre>">Shared sentence</code></pre><p>Translated sentence</p>'];
$failures=[];
foreach($cases as $n=>$case){
    $parsed=$parser->parse($case[0]);$parsed['replacements']=['Shared sentence'=>'Translated sentence'];
    $out=$parser->rebuild($parsed);
    if($out!==$case[1])$failures[]=['case'=>$n,'expected'=>$case[1],'actual'=>$out];
}
echo json_encode(['checks'=>count($cases),'failures'=>$failures],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($failures)exit(1);
echo "OK test-parser-exclusions\n";
