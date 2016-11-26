<?php

/*
 * @author mrtimosh@gmail.com
 */
namespace parser;
use \Sunra\PhpSimple\HtmlDomParser;
use parser\Parser_Abstract;

class Car_Links_Parser extends Parser_Abstract{

    public $parsed_data;
    
    public function __construct(string $url) {
        parent::__construct();
        
        $this->getMarkModelLinks($url);
        
        foreach ($this->parsed_data as $key => $url) {
            $this->parsed_data[$key] = $this->translateUrl($url);
        }
        return $this->parsed_data;
    }
    
    public function getMarkModelLinks(string $url) {

        if($this->parsed_data = $this->readSerializeData($this->getMarkName($url))){
            return;
        }
        
        $html = HtmlDomParser::str_get_html($this->getPageHtml($url));
        if (!$html) {
            throw new Exception("cant get html of url " . $url);
        }

        foreach ($html->find('.c-makes a') as $element) {       
                $car_model_link = $element->href;
                $html_subpage = HtmlDomParser::str_get_html($this->getPageHtml($car_model_link));
              
                if(!$html_subpage){ $parse_url[] = $car_model_link; continue;}
                
                foreach ($html_subpage->find('.c-makes a') as $sub_element) {
                    $submark = $sub_element->href;
                    $parse_url[] = $submark;
                }
              
        }  
        
        $this->parsed_data=$parse_url;
        $this->saveSerializeData($this->getMarkName($url),  $this->parsed_data);
    }
   
    private function getMarkName(string $url):string{
        $url = rtrim($url, '/');
        $last_slash = explode('/', $url);
        return $last_slash[count($last_slash)-1];
        
    }
    
    private function translateUrl(string $url):string {
        $url = rtrim($url, '/');
        $last_slash = explode('/', $url);
        $last = $last_slash[count($last_slash)-1];
        $last = preg_replace('/\D/', '', $last);
        $car_mark = $last_slash[count($last_slash)-2];
        return "https://www.drive2.ru/experience/$car_mark/g$last/?t=50";
    }
    
   
}