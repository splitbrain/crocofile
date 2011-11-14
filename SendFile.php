<?php

class SendFile {

    public $MULTIPART_BOUNDARY = 'Upl0adB0uNDARY';
    public $HEADER_LF          = "\r\n";
    public $CHUNK_SIZE         = 16384;

    public    $mime = 'application/octet-stream';
    protected $file;
    protected $size;
    protected $time;

    public function __construct($file){
        $this->file = $file;
        $this->size = filesize($file);
        $this->time = filemtime($file);
    }

    public function send(){
        if(!file_exists($this->file)){
            header("HTTP/1.0 404 File not Found");
            print "File not found";
            exit;
        }


        header("Content-Type: ".$this->mime);
        header('Cache-Control: must-revalidate, no-transform, post-check=0, pre-check=0');
        header('Pragma: private');
        $this->conditionalRequest();
        header('Content-Disposition: attachment; filename="'.basename($this->file).'";');
        // send file contents
        $fp = @fopen($this->file,"rb");
        if($fp){
            $this->rangeRequest($fp);
        }else{
            header("HTTP/1.0 500 Internal Server Error");
            print "Could not read file - bad permissions?";
        }
    }


    /**
     * Checks and sets HTTP headers for conditional HTTP requests
     *
     * @author   Simon Willison <swillison@gmail.com>
     * @link     http://simon.incutio.com/archive/2003/04/23/conditionalGet
     * @returns  void or exits with previously header() commands executed
     */
    protected function conditionalRequest(){
        $timestamp = $this->time;

        // A PHP implementation of conditional get, see
        //   http://fishbowl.pastiche.org/archives/001132.html
        $last_modified = substr(gmdate('r', $timestamp), 0, -5).'GMT';
        $etag = '"'.md5($last_modified).'"';
        // Send the headers
        header("Last-Modified: $last_modified");
        header("ETag: $etag");
        // See if the client has provided the required headers
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
            $if_modified_since = stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE']);
        }else{
            $if_modified_since = false;
        }

        if (isset($_SERVER['HTTP_IF_NONE_MATCH'])){
            $if_none_match = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']);
        }else{
            $if_none_match = false;
        }

        if (!$if_modified_since && !$if_none_match){
            return;
        }

        // At least one of the headers is there - check them
        if ($if_none_match && $if_none_match != $etag) {
            return; // etag is there but doesn't match
        }

        if ($if_modified_since && $if_modified_since != $last_modified) {
            return; // if-modified-since is there but doesn't match
        }

        // Nothing has changed since their last request - serve a 304 and exit
        header('HTTP/1.0 304 Not Modified');

        // don't produce output, even if compression is on
        @ob_end_clean();
        exit;
    }

    /**
     * Send file contents supporting rangeRequests
     *
     * This function exits the running script
     *
     * @param ressource $fh - file handle for an already open file
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    protected function rangeRequest($fh,$mime){
        $size    = $this->size;
        $mime    = $this->mime;
        $ranges  = array();
        $isrange = false;

        header('Accept-Ranges: bytes');

        if(!isset($_SERVER['HTTP_RANGE'])){
            // no range requested - send the whole file
            $ranges[] = array(0,$size,$size);
        }else{
            $t = explode('=', $_SERVER['HTTP_RANGE']);
            if (!$t[0]=='bytes') {
                // we only understand byte ranges - send the whole file
                $ranges[] = array(0,$size,$size);
            }else{
                $isrange = true;
                // handle multiple ranges
                $r = explode(',',$t[1]);
                foreach($r as $x){
                    $p = explode('-', $x);
                    $start = (int)$p[0];
                    $end   = (int)$p[1];
                    if (!$end) $end = $size - 1;
                    if ($start > $end || $start > $size || $end > $size){
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');
                        print 'Bad Range Request!';
                        exit;
                    }
                    $len = $end - $start + 1;
                    $ranges[] = array($start,$end,$len);
                }
            }
        }
        $parts = count($ranges);

        // now send the type and length headers
        if(!$isrange){
            header("Content-Type: $mime",true);
        }else{
            header('HTTP/1.1 206 Partial Content');
            if($parts == 1){
                header("Content-Type: $mime",true);
            }else{
                header('Content-Type: multipart/byteranges; boundary='.$this->MULTIPART_BOUNDARY,true);
            }
        }

        // send all ranges
        for($i=0; $i<$parts; $i++){
            list($start,$end,$len) = $ranges[$i];

            // multipart or normal headers
            if($parts > 1){
                echo $this->HEADER_LF.'--'.$this->MULTIPART_BOUNDARY.$this->HEADER_LF;
                echo "Content-Type: $mime".$this->HEADER_LF;
                echo "Content-Range: bytes $start-$end/$size".$this->HEADER_LF;
                echo $this->HEADER_LF;
            }else{
                header("Content-Length: $len");
                if($isrange){
                    header("Content-Range: bytes $start-$end/$size");
                }
            }

            // send file content
            fseek($fh,$start); //seek to start of range
            $chunk = ($len > $this->CHUNK_SIZE) ? $this->CHUNK_SIZE : $len;
            while (!feof($fh) && $chunk > 0) {
                @set_time_limit(30); // large files can take a lot of time
                print fread($fh, $chunk);
                flush();
                $len -= $chunk;
                $chunk = ($len > $this->CHUNK_SIZE) ? $this->CHUNK_SIZE : $len;
            }
        }
        if($parts > 1){
            echo $this->HEADER_LF.'--'.$this->MULTIPART_BOUNDARY.'--'.$this->HEADER_LF;
        }

        // everything should be done here, exit
        exit;
    }

}
