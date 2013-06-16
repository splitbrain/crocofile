<?php
require 'inc/Configuration.php';
$CONF = new Configuration('conf/settings.conf.php');

// No auth? no nothing!
require 'inc/Authentication.php';
$AUTH = new Authentication('conf/users.conf.php',$CONF->get('passhash'));
$REALUSER = $AUTH->authenticate();
if(!$REALUSER){
    $AUTH->send();
    die('You need to authenticate!');
}
// Let Admin work as someone else
$USER = $REALUSER;
if($REALUSER == 'admin' && isset($_REQUEST['workas'])){
    if(isset($AUTH->users[$_REQUEST['workas']])){
        $USER = $_REQUEST['workas'];
    }
}


// GUI less actions
$DO = (isset($_REQUEST['do']) ? $_REQUEST['do'] : '');
switch($DO){
    case 'up':
        require 'inc/fileuploader.php';
        $uploader = new qqFileUploader(array(), $CONF->get('uploadsize'));
        $result   = $uploader->handleUpload($CONF->get('uploaddir').'/'.$USER.'/');
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        exit();
    case 'download':
        require 'inc/SendFile.php';
        $file = $_REQUEST['file'];
        $file = preg_replace('/[\/\\\\]+/','',$file);
        $file = $CONF->get('uploaddir').'/'.$USER.'/'.$file;
        $file = new SendFile($file);
        $file->send();
        exit();
    case 'zip':
        require 'inc/zipstream.php';
        $zip = new ZipStream("$USER.zip",array('large_file_size'=>1024*1024));
        $files = glob($CONF->get('uploaddir').'/'.$USER.'/*');
        foreach($files as $file){
            $zip->add_file_from_path(basename($file), $file, array('time'=>filemtime($file)));
        }
        $zip->finish();
        exit;
    case 'delete':
        $file = $_REQUEST['file'];
        $file = urldecode($file);
        $file = preg_replace('/[\/\\\\]+/','',$file);
        $file = $CONF->get('uploaddir').'/'.$USER.'/'.$file;
        unlink($file);
        break;
    case 'useredit':
        if($USER == 'admin'){
            $AUTH->saveUser($_REQUEST['user'],$_REQUEST['info']);
        }
        break;
}

// GUI actions
require 'inc/GUI.php';
header('Content-Type: text/html; charset=utf-8');
$GUI = new GUI($CONF,$REALUSER,$USER,$AUTH);
$GUI->header();
switch ($DO){
    case 'userlist':
    case 'useredit':
        if($USER == 'admin'){
            $GUI->userlist();
            break;
        }
    case 'upload':
        $GUI->uploadform();
        break;
    default:
        $GUI->filelist();
}
$GUI->footer();

