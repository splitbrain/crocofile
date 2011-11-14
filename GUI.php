<?php

class GUI {
    private $conf;

    public function __construct($conf){
        $this->conf = $conf;
    }

    public function header(){
    ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <link href="fileuploader.css" rel="stylesheet" type="text/css">
            <style>
                body {font-size:13px; font-family:arial, sans-serif; width:700px; margin:100px auto;}
            </style>
        </head>
        <body>
    <?php
    }

    public function footer(){
    ?>
        </body>
        </html>
    <?php
    }

    public function uploadform(){
    ?>
        <div id="file-uploader">
            <noscript>
                <p>Please enable JavaScript to use file uploader.</p>
                <!-- or put a simple form for upload here FIXME -->
            </noscript>
        </div>

        <script src="fileuploader.js" type="text/javascript"></script>
        <script>
            function createUploader(){
                var uploader = new qq.FileUploader({
                    element: document.getElementById('file-uploader'),
                    action: 'index.php?do=upload',
                    debug: true
                });
            }

            // in your app create uploader as soon as the DOM is ready
            // don't wait for the window to load FIXME
            window.onload = createUploader;
        </script>
    <?php
    }

    public function filelist($user){
        $files = glob($this->conf['uploaddir'].'/'.$user.'/*');
        if(!count($files)){
            echo '<p>No files uploaded, yet</p>';
            return;
        }
        sort($files);
        echo '<table class="filelist">';
        echo '<tr>';
        echo '<th>File</th>';
        echo '<th>Size</th>';
        echo '<th>Uploaded at</th>';
        echo '</tr>';
        foreach($files as $file){
            $name = basename($file);
            echo '<tr>';

            echo '<td>';
            echo '<a href="index.php?file='.htmlspecialchars($name).'&amp;do=download">';
            echo htmlspecialchars($name);
            echo '</a>';
            echo '</td>';

            echo '<td>';
            echo filesize($file);
            echo '</td>';

            echo '<td>';
            echo strftime('%Y-%m-%d %H:%M',filemtime($file));
            echo '</td>';

            echo '</tr>';
        }
        echo '</table>';
    }
}
