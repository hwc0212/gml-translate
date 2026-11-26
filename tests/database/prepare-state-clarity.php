<?php
/** Dedicated fresh CI database; no production targets or plugin activation. */
$name=getenv('GML_TEST_DB_NAME');
if(PHP_SAPI!=='cli' || getenv('GML_DATABASE_TESTS')!=='1' || $name!=='gml_regression_state_clarity'){fwrite(STDERR,"Explicit isolated state-clarity database required.\n");exit(2);}
$host=getenv('GML_TEST_DB_HOST')?:'127.0.0.1:3306';
$port=3306;
if(preg_match('/^(.+):(\d+)$/',$host,$match)){$host=$match[1];$port=(int)$match[2];}
mysqli_report(MYSQLI_REPORT_OFF);
$db=@mysqli_connect($host,getenv('GML_TEST_DB_USER')?:'root',getenv('GML_TEST_DB_PASSWORD')?:'gml-regression-only','',$port);
if(!$db || !mysqli_query($db,'CREATE DATABASE gml_regression_state_clarity CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci')){
    fwrite(STDERR,"Could not create the fresh state-clarity test database.\n");
    exit(1);
}
mysqli_close($db);
$core=getenv('GML_TEST_CORE_DIR');
if(!$core || !is_file($core.'/tests/database/setup.php')){fwrite(STDERR,"Core test setup required.\n");exit(2);}
require $core.'/tests/database/setup.php';
