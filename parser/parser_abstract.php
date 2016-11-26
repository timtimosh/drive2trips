<?php

namespace parser;

use \Curl\Curl;
use \Curl\MultiCurl;
use Log;


class Parser_Abstract {
    protected $errors_logger, $parser_logger;
    protected $curl, $multi_curl;
    protected $iteration;
    protected $config;
    
    public function __construct() {
        $this->errors_logger = Log::singleton('file', APPDIR.'/trip/kyiv/error.txt', 'ident');
        $this->parser_logger = Log::singleton('file', APPDIR.'/trip/kyiv/parser.txt', 'ident');
        $this->curl = new Curl();
        $this->multi_curl = new MultiCurl();
        $this->multi_curl->setTimeout(3);
        $current_class = $this;
                
        $this->multi_curl->success(function($instance) {
            echo 'call to "' . $instance->url . '" was successful.' . "\n";
            //echo 'response:' . "\n";
            //var_dump($instance->response);
        });
        
        $this->multi_curl->error(function($instance) use ($current_class) {
            $current_class->errors_logger->log("Error: (" . $instance->url . ") " . $instance->errorCode . ": " . $instance->errorMessage);
//            echo 'call to "' . $instance->url . '" was unsuccessful.' . "\n";
//            echo 'error code: ' . $instance->errorCode . "\n";
//            echo 'error message: ' . $instance->errorMessage . "\n";
        });

        $this->multi_curl->complete(function($instance) use ($current_class) {
            //echo 'call completed' . "\n";
            $current_class->iteration++;
            $current_class->parser_logger->log($instance->url." end parsing");
            if($current_class->iteration >25250){
                die('Пока хватит мучить драйв2))');
            }
        });
    }
    
    protected function getPageHtml(string $url) {

        $url = $this->urlNormalize($url);

        $curl = $this->curl;
        
        $curl->get($url);
        
       
        
        if ($curl->error) {
            $this->errors_logger->log("Error: (" . $url . ") " . $curl->errorCode . ": " . $curl->errorMessage);
           // echo 'Error: (' . $url . ') ' . $curl->errorCode . ': ' . $curl->errorMessage . "\n";
        } else {
           //  $this->parser_logger->log($url." was successfuly parsed");
           //echo 'Response:' . "\n";
           // var_dump($curl->response);
            return $curl->response;
        }
    }

    protected function urlNormalize(string $url): string {
        if (!strpos($url, "drive2.ru")) {
            $url = "https://www.drive2.ru" . $url;
        }

        return $url;
    }

    protected function saveSerializeData(string $name, array $data) { 
        // serialize your input array (say $array)
        $serializedData = serialize($data);
        // save serialized data in a text file
        file_put_contents(APPDIR.'/trip/'.$name.'.txt', $serializedData);
    }
    
     protected function readSerializeData(string $file_name) { 
         $file_name = APPDIR.'/trip/'.$file_name.'.txt';
         if (file_exists($file_name)) {
              $recoveredData = file_get_contents($file_name);
              return unserialize($recoveredData);
         }
         return false;    
    }
}
