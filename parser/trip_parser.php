<?php

/*
 * @author mrtimosh@gmail.com
 */
namespace parser;

use \Sunra\PhpSimple\HtmlDomParser;
use \model\Post;
use \model\Page;

class Trip_Parser extends \parser\Parser_Abstract{


  
    static $city_to_search = 'Киев';
    public $parsed_data = [];
    

    public function start(){       
        // Requests in parallel with callback functions.
   
        $url = ['https://www.drive2.ru/r/volkswagen/'];

        $trip = new \parser\Car_Links_Parser($url[0]);
        $current_class = $this;
        $this->multi_curl->success(function($instance) use ($current_class){
            
            $data = [           
                "url" => $instance->url,
                "html" => $instance->response,
            ];
            Page::create($data);
            
           if ($next_page_to_parse = $current_class->nextPageToParse($instance->response)){
                $current_class->findAuthorCityAndSaveIt($instance->response, $instance->url);
                $current_class->multi_curl->addGet($next_page_to_parse);
           }
            // echo 'call to "' . $instance->url . '" was successful.' . "\n";
            //echo 'response:' . "\n";
            //var_dump($instance->response);
            
                   
        });
        

        foreach ($trip->parsed_data as $key => $model_url) {
            $this->multi_curl->addGet($model_url);
        }
        
        $this->multi_curl->start();
       
    }
    
 
    public function findAuthorCityAndSaveIt(string $html, string $url) {

        $html = HtmlDomParser::str_get_html($html);
  
        foreach ($html->find('.c-block-card') as $element) {
            $element_html = HtmlDomParser::str_get_html($element->outertext);
            
            $topic = $author = $author_html = $topic_link= '';
            
            foreach ($element_html->find('.c-post-preview__title a.c-link') as $title_element) {
                $topic = $title_element->outertext;
                $topic_link = $title_element->href;
            }
            /* link end */

            /* author */
            foreach ($element_html->find('.c-car-card__info') as $cardinfo) {
                $author_html = $cardinfo->innertext;
            }
            /* author end */

            if (!$this->isTopicFromSearchedCityByHTML($author_html)) {
                continue;
            }

            $data = [
                "author" => strip_tags($author_html),
                "title" => strip_tags($topic),
                "url" => strip_tags($topic_link),
            ];
            
            if(!Post::find_by_url($data['url'])){
                Post::create($data);
            }
           
//            echo "||| url where i found it $url ||| ";
            echo $author_html . ": " . $topic;
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

    private function nextPageToParse(string $html) {
        //lets go to db and see if we already grab this page
        $next_page_to_parse = false;
        $html = HtmlDomParser::str_get_html($html);
        foreach ($html->find('.c-pager__link') as $element) {
            $rel = $element->getAttribute('rel');
            if ($rel == 'next') {
                $next_page_to_parse = 'https://www.drive2.ru' . $element->href;
            }
        }
        if(!$next_page_to_parse){ return false; }
        
        $row = Page::find_by_url($next_page_to_parse); //where url_where_we_find_this_article = '$next_page_to_parse'
          
        if($row){
            $this->findAuthorCityAndSaveIt($row->html, $next_page_to_parse);
            return $this->nextPageToParse($row->html);   
        }
        else{
            return $next_page_to_parse;
        }
        
        
        return false;
    }

}
