<?php

require_once 'HTTPDigest.php';

class Authentication extends HTTPDigest {
    public  $users;
    private $userfile;

    /**
     * Load users and passwords
     */
    public function __construct($userfile,$passhash){
        $this->passwordsHashed = $passhash;
        $this->nonceLife = 3*60*60;    // stay logged in 3 hours
        $this->realm = 'Crocofile';

        $this->users = array();
        $this->userfile = $userfile;

        if(!@file_exists($this->userfile) || filesize($this->userfile) == 0){
            $this->saveUser('admin',array('pass'=>'admin'));
        }

        $lines = file($this->userfile);
        foreach($lines as $line){
            $line = preg_replace('/#.*$/','',$line); //ignore comments
            $line = trim($line);
            if(empty($line)) continue;

            $row    = explode("\t",$line,2); //fixme maybe more later

            $this->users[$row[0]]['pass'] = $row[1];
        }
    }

    /**
     * Return the passphrase for the given user
     */
    protected function getPassphrase($user){
        if(isset($this->users[$user])){
            return $this->users[$user]['pass'];
        }else{
            return false;
        }
    }

    public function saveUser($user,$info){
        $user = preg_replace('/[^a-z0-9-_]+/','',strtolower($user));
        $pass = $info['pass'];
        if($this->passwordsHashed){
            $pass = $this->a1hash($user,$pass);
        }
        $this->users[$user]['pass'] = $pass;
        $this->saveUserFile();
    }

    public function saveUserFile(){
        $data  = "# <?php die()?>\n";
        $data .= "# configure users below:\n";
        foreach($this->users as $user => $info){
            $data .= $user."\t".$info['pass']."\n";
        }
        file_put_contents($this->userfile,$data);
    }
}

