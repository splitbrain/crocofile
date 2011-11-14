<?php
/*
Copyright (c) 2005 Paul James
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:
1. Redistributions of source code must retain the above copyright
   notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
3. Neither the name of the Paul James nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
SUCH DAMAGE.
*/

/** HTTP Digest authentication class */
class HTTPDigest
{

    /** The Digest opaque value (any string will do, never sent in plain text over the wire).
     * @var str
     */
    var $opaque = 'opaque';

    /** The authentication realm name.
     * @var str
     */
    var $realm = 'Realm';

    /** The base URL of the application, auth data will be used for all resources under this URL.
     * @var str
     */
    var $baseURL = '/';

    /** Are passwords stored as an a1 hash (username:realm:password) rather than plain text.
     * @var str
     */
    var $passwordsHashed = TRUE;

    /** The private key.
     * @var str
     */
    var $privateKey = 'privatekey';

    /** The life of the nonce value in seconds
     * @var int
     */
    var $nonceLife = 300;

    /** Send HTTP Auth header */
    function send()
    {
        header('WWW-Authenticate: Digest '.
            'realm="'.$this->realm.'", '.
            'domain="'.$this->baseURL.'", '.
            'qop=auth, '.
            'algorithm=MD5, '.
            'nonce="'.$this->getNonce().'", '.
            'opaque="'.$this->getOpaque().'"'
        );
        header('HTTP/1.0 401 Unauthorized');
    }

    /** Get the HTTP Auth header
     * @return str
     */
    function getAuthHeader()
    {

        // try to get the digest headers - taken from PHPMyOpenID ANDI
        if (function_exists('apache_request_headers') && ini_get('safe_mode') == false) {
                $arh = apache_request_headers();
                $hdr = $arh['Authorization'];

        } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $hdr = $_SERVER['PHP_AUTH_DIGEST'];

        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $hdr = $_SERVER['HTTP_AUTHORIZATION'];

        } elseif (isset($_ENV['PHP_AUTH_DIGEST'])) {
                $hdr = $_ENV['PHP_AUTH_DIGEST'];

        } elseif (isset($_REQUEST['auth'])) {
                $hdr = stripslashes(urldecode($_REQUEST['auth']));

        } else {
                $hdr = null;
        }

        return $hdr;
    }

    /**
     * Override
     */
    protected function getPassphrase($user){
        return false;
    }


    /** Authenticate the user and return username on success.
     * @param str[] users Array of username/password pairs
     * @return str
     */
    function authenticate()
    {
        $authorization = $this->getAuthHeader();
        if ($authorization) {
            if (substr($authorization, 0, 5) == 'Basic') {
                trigger_error('You are trying to use HTTP Basic authentication but I am expecting HTTP Digest');
                exit;
            }
            if (
                preg_match('/username="([^"]+)"/', $authorization, $username) &&
                preg_match('/nonce="([^"]+)"/', $authorization, $nonce) &&
                preg_match('/response="([^"]+)"/', $authorization, $response) &&
                preg_match('/opaque="([^"]+)"/', $authorization, $opaque) &&
                preg_match('/uri="([^"]+)"/', $authorization, $uri)
            ) {
                $username = $username[1];
                $requestURI = $_SERVER['REQUEST_URI'];
                if (strpos($requestURI, '?') !== FALSE) { // hack for IE which does not pass querystring in URI element of Digest string or in response hash
                    $requestURI = substr($requestURI, 0, strlen($uri[1]));
                }
                $passphrase = $this->getPassphrase($username);
                if (
                    $passphrase &&
                    $opaque[1] == $this->getOpaque() &&
                    $uri[1] == $requestURI &&
                    $nonce[1] == $this->getNonce()
                ) {
                    if ($this->passwordsHashed) {
                        $a1 = $passphrase;
                    } else {
                        $a1 = md5($username.':'.$this->getRealm().':'.$passphrase);
                    }
                    $a2 = md5($_SERVER['REQUEST_METHOD'].':'.$requestURI);
                    if (
                        preg_match('/qop="?([^,\s"]+)/', $authorization, $qop) &&
                        preg_match('/nc=([^,\s"]+)/', $authorization, $nc) &&
                        preg_match('/cnonce="([^"]+)"/', $authorization, $cnonce)
                    ) {
                        $expectedResponse = md5($a1.':'.$nonce[1].':'.$nc[1].':'.$cnonce[1].':'.$qop[1].':'.$a2);
                    } else {
                        $expectedResponse = md5($a1.':'.$nonce[1].':'.$a2);
                    }
                    if ($response[1] == $expectedResponse) {
                        return $username;
                    }
                }
            }
        } else {
            // this breaks the initial login phase ANDI
            #trigger_error('HTTP Digest headers not being passed to PHP by the server, unable to authenticate user');
            #exit;
        }
        return NULL;
    }

    /** Get nonce value for HTTP Digest.
     * @return str
     */
    function getNonce() {
        $time = ceil(time() / $this->nonceLife) * $this->nonceLife;
        return md5(date('Y-m-d H:i', $time).':'.$_SERVER['REMOTE_ADDR'].':'.$this->privateKey);
    }

    /** Get opaque value for HTTP Digest.
     * @return str
     */
    function getOpaque()
    {
        return md5($this->opaque);
    }

    /** Get realm for HTTP Digest taking PHP safe mode into account.
     * @return str
     */
    function getRealm()
    {
        if (ini_get('safe_mode')) {
            return $this->realm.'-'.getmyuid();
        } else {
            return $this->realm;
        }
    }

}

/* Example usage
$HTTPDigest =& new HTTPDigest();
if ($username = $HTTPDigest->authenticate(array(
    'username' => md5('username:'.$HTTPDigest->getRealm().':password')
))) {
    echo sprintf('Logged in as "%s"', $username);
} else {
    $HTTPDigest->send();
    echo 'Not logged in';
}
//*/

?>
