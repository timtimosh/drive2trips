<?php

/*
 * @author mrtimosh@gmail.com
 */
namespace parser;

use \Sunra\PhpSimple\HtmlDomParser;
use \model\Post;
use \model\Page;

class Trip_Post_Parser extends \parser\Parser_Abstract{


  
    static $city_to_search = 'Киев';
    public $parsed_data = [];
    

    public function start(){       
       $pages = Page::all(array('conditions' =>'html LIKE "%'.static::$city_to_search.'%" '));
       foreach ($pages as $key => $page) {
           
           if(!$this->isTopicFromSearchedCityByHTML($page->html)){continue;}
           $this->findAuthorCityAndSaveIt($page->html, $page->url);
        }
    }
    
    private function getMarkName(string $url):string{
        $url = rtrim($url, '/');
        $last_slash = explode('/', $url);
        return $last_slash[count($last_slash)-3];
        
    }
 
    public function findAuthorCityAndSaveIt(string $html, string $page_url) {
        
        //TODO make one func for this
        //<div class="c-post-preview__title">
        preg_match_all('/<div class=\"c\-post\-preview__title\">(.*?)<\/div>/s',$html,$estimates);
        foreach ($estimates[0] as $key => $topic_link_html) {
            $post[$key] = $topic_link_html;
        }
        $estimates = array();
        preg_match_all('/<div class=\"c\-car\-card__info ">(.*?)<\/div>/s',$html,$estimates);
        foreach ($estimates[0] as $key => $author_from_html) {
            $user_from[$key] = $author_from_html;
        }
        $estimates = array();
        preg_match_all('/<div class=\"c\-car\-card__owner \">(.*?)<\/div>/s',$html,$estimates);
        foreach ($estimates[0] as $key => $author_link_html) {
            $post_info_html[] = array("topic_link_html"=>$post[$key], "author_from" => $user_from[$key], "author"=>$author_link_html);
        }
        //end TODO
        if (empty($post_info_html)){return;}
        
        foreach ($post_info_html as $element) {
            
            $author_name = $element['author'];
            $topic = strip_tags($element['topic_link_html']);
            
            preg_match_all('/href=\"(.*?)\"/s',$element['topic_link_html'],$estimates);
       
            $topic_link = $estimates[1][0];
    
            if (!$this->isTopicFromSearchedCityByHTML($element['author_from'])) {
                continue;
            }

            $data = [
                "author" => $author_name,
                "title" => $topic,
                "url" => $this->urlNormalize($topic_link),
                "html"=>"",
                "car_mark"=>$this->getMarkName($page_url),
                "location"=>static::$city_to_search
            ];
         
            if(empty(Post::find_by_url_and_location($data['url'],$data['location']))){
                Post::create($data);
            }
           
//            echo "||| url where i found it $url ||| ";
            echo $author_name . ": " . $topic;
//            var_dump($topic);
//            var_dump($topic_link);
           echo '<br><hr><br>';
        }

        
    }

 private function isTopicFromSearchedCityByHTML(string $html): bool {
     //return true;
            if (strpos($html, static::$city_to_search) !== false) {
                return true;
            }
        
        return false;
    }
    
    private function isTopicFromSearchedCityByID(string $id): bool {
        //   https://www.drive2.ru/ajax/info/?type=u&id=288230376151852470&tail=1471019

        $content = $this->getPageHtml("https://www.drive2.ru/ajax/info/?type=u&id=$id");

        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (strpos($json['html'], static::$city_to_search) !== false) {
                return true;
            }
        }

        return false;
    }

   
}
