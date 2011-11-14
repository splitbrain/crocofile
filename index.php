<?php

// No auth? no nothing!
require 'Authentication.php';
$AUTH = new Authentication('users.conf.php');
$USER = $AUTH->authenticate();
if(!$USER){
    $AUTH->send();
    die('You need to authenticate!');
}

require 'Configuration.php';
$CONF = new Configuration();

// GUI less actions
switch($_REQUEST['do']){
    case 'up':
        require 'fileuploader.php';
        $uploader = new qqFileUploader(array(), $CONF->get('uploadsize'));
        $result   = $uploader->handleUpload($CONF->get('uploaddir').'/'.$USER.'/');
        echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);
        exit();
    case 'download':
        require 'SendFile.php';
        $file = $_REQUEST['file'];
        $file = preg_replace('/[\/\\\\]+/','',$file);
        $file = $CONF->get('uploaddir').'/'.$USER.'/'.$file;
        $file = new SendFile($file);
        $file->send();
        exit();
    case 'useredit':
        if($USER == 'admin'){
            $AUTH->saveUser($_REQUEST['user'],$_REQUEST['info']);
        }
        break;
}

// GUI actions
require 'GUI.php';
$GUI = new GUI($CONF,$USER,$AUTH);
$GUI->header();
switch ($_REQUEST['do']){
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

