<?php

class Configuration {
    private $conf;

    public function __construct($conffile){
        include($conffile);
        $this->conf = $conf;
        $this->conf['uploadsize'] = $this->getUploadSize();
    }

    public function get($key){
        return $this->conf[$key];
    }

    /**
     * Return the human readable size of a file
     *
     * @param       int    $size   A file size
     * @param       int    $dec    A number of decimal places
     * @author      Martin Benjamin <b.martin@cybernet.ch>
     * @author      Aidan Lister <aidan@php.net>
     * @version     1.0.0
     */
    function size_h($size, $dec = 1){
        $sizes = array('B', 'KB', 'MB', 'GB');
        $count = count($sizes);
        $i = 0;

        while ($size >= 1024 && ($i < $count - 1)) {
            $size /= 1024;
            $i++;
        }

        return round($size, $dec) . ' ' . $sizes[$i];
    }

    private function getUploadSize(){
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));

        return min($postSize,$uploadSize);
    }

    private function toBytes($str){
        $val = substr(trim($str), 0, -1);
        $unit = strtolower($str[strlen($str)-1]);
        switch($unit) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }

}
