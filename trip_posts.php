<?php
/* 
 * @author mrtimosh@gmail.com
 */

header('Content-Type: text/html; charset=utf-8');
set_time_limit(0);

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once 'vendor/autoload.php';

ActiveRecord\Config::initialize(function($cfg)
{
   $cfg->set_model_directory('model');
   $cfg->set_connections(
     array(
       'production' => 'mysql://root:121331@localhost/drive2;charset=utf8mb4'
     )
   );
    $cfg->set_default_connection('production');
});

define('APPDIR',__DIR__);

$parser_pages = new parser\Trip_Post_Parser();
$parser_pages->start();