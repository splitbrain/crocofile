<?php

class GUI {
    private $conf;
    private $user;
    private $realuser;
    private $auth;

    public function __construct($conf,$realuser,$user,$auth){
        $this->conf     = $conf;
        $this->user     = $user;
        $this->realuser = $realuser;
        $this->auth     = $auth;
    }

    public function header(){
    ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
            <title><?php echo htmlspecialchars($this->conf->get('title'))?></title>
            <link href="res/fileuploader.css" rel="stylesheet" type="text/css" />
            <link href="res/style.css" rel="stylesheet" type="text/css" />
            <link href="fileicons/fileicons.css" rel="stylesheet" type="text/css" />
            <script src="res/sorttable.js" type="text/javascript"></script>
            <link rel="shortcut icon" href="favicon.ico" />
        </head>
        <body>
        <h1>
            <img src="<?php echo $this->conf->get('icon')?>" border="0" />
            <?php echo $this->conf->get('title')?>
        </h1>
        <?php if($this->realuser == 'admin') $this->workasdropdown() ?>
        <ul class="tabs">
            <li><a href=".<?php $this->wasp('?')?>">Download</a></li>
            <li><a href="upload<?php $this->wasp('?')?>">Upload</a></li>
            <?php if($this->user == 'admin') echo '<li><a href="userlist">Users</a></li>' ?>
        </ul>
        <div class="wrap">
    <?php
    }

    public function workasdropdown(){
        echo '<form action="" class="workas">';
        echo '<label for="workas">Work as:</label> ';
        echo '<select name="workas" id="workas">';
        foreach($this->auth->users as $login => $info){
            if($login == $this->user){
                echo '<option selected="selected">';
            }else{
                echo '<option>';
            }
            echo htmlspecialchars($login);
            echo '</option>';
        }
        echo '<input type="submit" value="go" />';
        echo '</select>';
        echo '</form>';
    }

    /**
     * Print the Work-As-Parameter
     */
    private function wasp($sep='&amp;'){
        echo $this->rwasp($sep);
    }

    /**
     * Return the Work-As-Parameter
     */
    private function rwasp($sep='&amp;'){
        if($this->user != $this->realuser){
            return $sep.'workas='.rawurlencode($this->user);
        }else{
            return '';
        }
    }

    public function footer(){
    ?>
        </div>
        <div class="footer">
            powered by <a href="http://www.splitbrain.org/projects/crocofile">Crocofile</a>
        </div>
        </body>
        </html>
    <?php
    }

    public function uploadform(){
    ?>
        <p>To upload a file, click on the button below. Drag-and-drop is supported in FF, Chrome.</p>
        <p>You can upload files up to <?php echo $this->conf->size_h($this->conf->get('uploadsize'))?>.</p>
        <div id="file-uploader">
            <noscript>
                <p>Please enable JavaScript to use file uploader.</p>
                <!-- or put a simple form for upload here FIXME -->
            </noscript>
        </div>

        <script src="res/fileuploader.js" type="text/javascript"></script>
        <script>
            function createUploader(){
                var uploader = new qq.FileUploader({
                    element: document.getElementById('file-uploader'),
                    action: 'up<?php $this->wasp('?')?>',
                });
            }

            // in your app create uploader as soon as the DOM is ready
            // don't wait for the window to load FIXME
            window.onload = createUploader;
        </script>
    <?php
    }

    public function filelist(){
        $files = glob($this->conf->get('uploaddir').'/'.$this->user.'/*');
        if(!count($files)){
            echo '<p>No files uploaded, yet</p>';
            return;
        }
        sort($files);
        echo '<form action="delete" method="get">';
        if ( $this->rwasp() ) {
            echo '<input type="hidden" name="workas" value="'.str_replace( 'workas=', '', $this->rwasp('') ).'" />';
        }
        echo '<table class="filelist sortable">';
        echo '<tr>';
        echo '<th>File</th>';
        echo '<th>Size</th>';
        echo '<th>Uploaded at</th>';
        echo '<th>Action</th>';
        echo '</tr>';
        foreach($files as $file){
            $name = basename($file);
+            $file_epxlode = explode('.',$file);
+            $ext  = htmlspecialchars(array_pop($file_epxlode));
            echo '<tr>';

            echo '<td>';
            echo '<a class="file ico_'.$ext.'" href="download?file='.rawurlencode($name).$this->rwasp().'">';
            echo htmlspecialchars($name);
            echo '</a>';
            echo '</td>';

            echo '<td>';
            echo $this->conf->size_h(filesize($file));
            echo '</td>';

            echo '<td>';
            echo strftime('%Y-%m-%d %H:%M',filemtime($file));
            echo '</td>';

            echo '<td>';
            echo '<button type="submit" name="file" value="'.rawurlencode($name).'"/>delete</button>';
            echo '</td>';

            echo '</tr>';
        }
        echo '</table>';
        echo '</form>';
        echo '<div class="zip"><a href="zip'.$this->rwasp('?').'" class="file ico_zip">Download ZIP</a></div>';
    }

    public function userlist(){
        $users = $this->auth->users;
        ksort($users);

        echo '<table class="filelist">';
        echo '<tr>';
        echo '<th>User</th>';
        echo '<th>Pass</th>';
        echo '</tr>';
        foreach($users as $user => $info){
            echo '<tr>';

            echo '<td>';
            echo htmlspecialchars($user);
            echo '</td>';

            if($this->auth->passwordsHashed){
                $pass = '';
            }else{
                $pass = htmlspecialchars($info['pass']);
            }

            echo '<td>';
            echo '<form action="userlist" method="post">';
            echo '<input type="hidden" name="do"   value="useredit">';
            echo '<input type="hidden" name="user" value="'.htmlspecialchars($user).'">';
            echo '<input type="text"   name="info[pass]" value="'.$pass.'" />';
            echo '<input type="submit" value="save" />';
            echo '</form>';
            echo '</td>';

            echo '</tr>';
        }

        echo '<tr>';
        echo '<form action="userlist" method="post">';
        echo '<input type="hidden" name="do"   value="useredit">';

        echo '<td>';
        echo '<input type="text" name="user" value="">';
        echo '</td>';

        echo '<td>';
        echo '<input type="text" name="info[pass]" />';
        echo '<input type="submit" value="add user" />';
        echo '</td>';

        echo '</form>';
        echo '</tr>';
        echo '</table>';
    }
}
