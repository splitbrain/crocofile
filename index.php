<?php

// No auth? no nothing!
require 'Authentication.php';
$Authentication = new Authentication('users.conf.php');
$USER = $Authentication->authenticate();
if(!$USER){
    $Authentication->send();
    die('You need to authenticate!');
}

// FIXME move to config file
$CONF = array(
    'uploaddir' => '/tmp/',

);

// GUI less actions
switch($_REQUEST['do']){
    case 'upload':
        require 'fileuploader.php';
        $uploader = new qqFileUploader(array(), 10*1024*1024);
        $result   = $uploader->handleUpload($CONF['uploaddir'].'/'.$USER.'/');
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        exit();
    case 'download':
        require 'SendFile.php';
        $file = $_REQUEST['file'];
        $file = preg_replace('/[\/\\\\]+/','',$file);
        $file = $CONF['uploaddir'].'/'.$USER.'/'.$file;
        $file = new SendFile($file);
        $file->send();
        exit();
}

// GUI actions
require 'GUI.php';
$GUI = new GUI($CONF);
$GUI->header();
switch ($_REQUEST['do']){
    default:
        $GUI->uploadform();
        $GUI->filelist($USER);
}
$GUI->footer();

