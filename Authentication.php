<?php

require_once 'HTTPDigest.php';

class Authentication extends HTTPDigest {
    private $users;
    private $userfile;

    /**
     * Load users and passwords
     */
    public function __construct($userfile){
        $this->passwordsHashed = false; //FIXME change later when we add registration
        $this->nonceLife = 3*60*60;    // stay logged in 3 hours

        $this->users = array();
        $this->userfile = $userfile;

        if(!@file_exists($this->userfile)) return;

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
}

