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

define('APPDIR', __DIR__);

use \model\Post;
use \parser\Trip_Post_Parser;

$car_models = Post::find_by_sql('select car_mark from `posts` group by car_mark');

  foreach ($car_models as $model){
     generateHtml($model->car_mark);
  }
      
function generateHtml(string $car_model){      
$posts = Post::all(array('conditions' => array('car_mark  = "'.$car_model.'" AND location = "'.Trip_Post_Parser::$city_to_search.'" ')));
ob_start();
?>
<!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <title>Пример веб-страницы</title>
    </head>
    <body>
   
        <h1><?=Trip_Post_Parser::$city_to_search.' - '.$car_model?></h1>

        <table>
            <?php
            foreach ($posts as $post)
            {
                echo '<tr><td>' . strip_tags($post->author) . '</td><td><a href="' . $post->url . '">' . $post->title . '</a></td></tr>';
            }
            ?>
        </table>
    </body>
    </html>
<?php
$html = ob_get_contents();
ob_end_clean();
file_put_contents($file = APPDIR.'/trip/'.Trip_Post_Parser::$city_to_search.'/'.$car_model.'.html', $html);
}

