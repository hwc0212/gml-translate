<?php
if(getenv('GML_DATABASE_TESTS')!=='1'){http_response_code(404);exit;}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit;}
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH));
$base=parse_url(getenv('GML_TEST_HOME')?:'http://gml-pagination.test:8080',PHP_URL_PATH)?:'';
if($base && strpos($path,$base.'/')===0)$path=substr($path,strlen($base));
$root=getenv('GML_TEST_WP_ROOT');$file=$root.$path;
if(preg_match('/\.(css|js|png|jpe?g|gif|svg|woff2?|ttf|ico|map)$/i',$path,$m) && is_file($file) && strpos($path,'..')===false){
 $mime=['css'=>'text/css','js'=>'application/javascript','svg'=>'image/svg+xml','png'=>'image/png','ico'=>'image/x-icon'];
 header('Content-Type: '.($mime[strtolower($m[1])]??'application/octet-stream'));readfile($file);exit;
}
require $root.'/index.php';
