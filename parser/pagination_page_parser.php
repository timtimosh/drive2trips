<?php

/*
 * @author mrtimosh@gmail.com
 */
namespace parser;

use \Sunra\PhpSimple\HtmlDomParser;
use \model\Post;
use \model\Page;

class Trip_Page_Parser extends \parser\Parser_Abstract{

    public function start(){       
        // Requests in parallel with callback functions.
        $current_class = $this;
        $this->multi_curl->success(function($instance) use ($current_class){
            $current_class->parser_logger->log($instance->url." was successfuly parsed");
            if(!$pagination_next_link_html = $this->getNextLinkFromPaginationHtml($instance->response)){$pagination_next_link_html = "";}
            $data = [           
                "url" => $instance->url,
                "html" => //$this->paginationHtml(
                        $instance->response,
                "pagination_html"=> $pagination_next_link_html
                       //), //$instance->response,
            ];
            Page::create($data);
            
            if($next_page_to_parse = $current_class->nextPageToParse($data['pagination_html'])){
                $current_class->multi_curl->addGet($next_page_to_parse);
            }
            echo 'call to "' . $instance->url . '" was successful and saved to db.' . "\n";
            //echo 'response:' . "\n";
            //var_dump($instance->response);
               echo "<hr>";
                   
        });
        
        $this->multi_curl->error(function($instance) use ($current_class) {
            $current_class->errors_logger->log("Error: (" . $instance->url . ") " . $instance->errorCode . ": " . $instance->errorMessage);
            echo 'call to "' . $instance->url . '" was unsuccessful.' . "\n";
            echo 'error code: ' . $instance->errorCode . "\n";
            echo 'error message: ' . $instance->errorMessage . "\n";
            echo "<hr>";
        });
        
        $urls_list = ['https://www.drive2.ru/r/renault/','https://www.drive2.ru/r/skoda/', 'https://www.drive2.ru/r/lada/', 'https://www.drive2.ru/r/volkswagen/'];

        foreach ($urls_list as $key => $url) {
                $trip = new \parser\Car_Links_Parser($url);
                foreach ($trip->parsed_data as $key => $model_url) {
                     //where url_where_we_find_this_article = '$next_page_to_parse'
                 // echo '$next_page_to_parse: '.$next_page_to_parse;
                    if($row = Page::findPageByItsUrl($model_url)){ 
                        if(!$model_url = $this->nextPageToParse($row->pagination_html)){continue;}
                      
                    }
                       
                    $this->multi_curl->addGet($model_url);
                }

         }
        
        $this->multi_curl->start();
       
    }
    
    private function getMarkName(string $url):string{
        $url = rtrim($url, '/');
        $last_slash = explode('/', $url);
        return $last_slash[count($last_slash)-3];
        
    }

    private function nextPageToParse(string $page_pagination_html) {
   
        //lets go to db and see if we already grab this page
        while (true){
            if(empty($page_pagination_html)) { return false; }
            //rel="next" href="
             //$next_page_to_parse = $this->getNextLinkFromPaginationHtml($page_pagination_html);
            
            
            if($row = Page::findPageByItsUrl($page_pagination_html)){       
                $page_pagination_html = $row->pagination_html;           
            }
            else
                return $page_pagination_html;
            }  
    }
    
    
    private function paginationHtml(string $html):string{
        
        $result = preg_match_all('/<p class=\"o\-ibc c\-pager\">(.*?)<\/p>/s',$html,$estimates);
        if($result){
            return htmlspecialchars_decode($estimates[0][0]);
        }
        
        return "";
    }
    
    private function getNextLinkFromPaginationHtml(string $html){
        if ($page_pagination_html = $this->paginationHtml($html)){
            $result = preg_match_all('/rel=\"next\" href=\"(.*?)\">/s',$page_pagination_html,$estimates);
            if(!$result){ return false; }
            return $this->urlNormalize(htmlspecialchars_decode($estimates[1][0]));
        }    
    }
}
