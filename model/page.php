<?php
namespace model;

class Page extends \ActiveRecord\Model{ 
    public static $all_pages;
    public function __construct(array $attributes = array(), $guard_attributes = true, $instantiating_via_find = false, $new_record = true) {
        parent::__construct($attributes, $guard_attributes, $instantiating_via_find, $new_record);
    }
    
    public static function findPageByItsUrl(string $url){
        if (empty(static::$all_pages)){
            foreach (static::find('all', array('select' => 'url, pagination_html')) as $key => $page)
            {
                
                static::$all_pages[$page->url]=$page;
            }
        }
     
        if (isset(static::$all_pages[$url])) { return static::$all_pages[$url];}
        return false;
    }
    
    private static function paginationHtml(string $html){
        
        $result = preg_match_all('/<p class=\"o\-ibc c\-pager\">(.*?)<\/p>/s',$html,$estimates);
        if($result){
            return htmlspecialchars_decode($estimates[0][0]);
        }
        
        return false;
    }
}
