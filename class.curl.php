<?php
/*
 * A class that abstracts php libcurl, especially for use in RESTful API's
 */
class Curl {
    private $curl_handle;
    private $url;
    private $data;
    private $info;

    public $error=NULL;

    /*
     * Instantiate new Curl object:
     * $c = new Curl();
     *
     * Instantiate new Curl object, and open a url:
     * $c = new Curl($url);
     */

    function __construct ($url=NULL) {
        $this->curl_handle = curl_init ($url);
        if (!($this->setopt (CURLOPT_RETURNTRANSFER, TRUE))) {
            $this->error = "Cannot set RETURNTRANSFER option";
            return;
        } else if (!($this->setopt (CURLOPT_FOLLOWLOCATION, TRUE))) {
            $this->error = "Cannot set FOLLOWLOCATION option";
            return;
        } else if (!($this->setopt (CURLOPT_MAXREDIRS, 10))) {
            $this->error = "Cannot set MAXREDIRS option";
            return;
        } else if (!($this->setopt(CURLOPT_SSLVERSION,3))) {
            $this->error = "Cannot set SSLVERSION option";
            return FALSE;
        } else if (!($this->setopt(CURLOPT_SSL_VERIFYPEER, FALSE))) {
            $this->error = "Cannot set SSL_VERIFYPEER option";
            return FALSE;
        } else if (!($this->setopt(CURLOPT_SSL_VERIFYHOST, 2))) {
            $this->error = "Cannot set SSL_VERIFYHOST option";
            return FALSE;
        }
        return;
    }

    /*
     * Retrieve the body data from the Curl object (typically after a read)
     */

    function get_data () {
        return $this->data;
    }

    /*
     * Retrieve the info data from the Curl object (typically after a read)
     *
     * To see what it contains, refer to the output of curl_getinfo() http://www.php.net/manual/en/function.curl-getinfo.php
     */

    function get_info () {
        return $this->info;
    }

    /*
     * Set a curl option
     *
     * To see what can be set, refer to curl_setopt() http://www.php.net/manual/en/function.curl-setopt.php
     */

    function setopt ($opt, $val) {
        if (!(curl_setopt ($this->curl_handle, $opt, $val))) {
            $this->error = "Cannot set ".$opt." option";
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /*
     * Set the url to read from (or change it, if it was set previously
     *
     * If the object was instantiated without a url, this needs to be called before attempting a read
     */

    function set_url ($url) {
        $this->url = $url;
        return ($this->setopt (CURLOPT_URL, $url));
    }

    /*
     * Utility function - converts an associative array of key/value pairs into a url query string
     *
     * if input is not an array, it is returned as is
     */

    function convert_params ($params) {
        if (is_array ($params)) {
            foreach ($params as $key=>$val) {
                $params_str .= $key."=".$val."&";
            }
            rtrim ($params_str, "&");
        } else {
            $params_str = $params;
        }

        return $params_str;
    }

    /*
     * Set the user and password options for basic auth, and also the CURLAUTH_HTTPAUTH option to CURLAUTH_BASIC
     */

    function auth_basic ($username, $password) {
        if (!($this->setopt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC ))) {
            $this->error = "Cannot set HHTPAUTH option";
            return FALSE;
        } else if (!($this->setopt(CURLOPT_USERPWD, $username.":".$password))) {
            $this->error = "Cannot set USERPWD option";
            return FALSE;
        }

        return TRUE;
    }

    /*
     * Perform GET or POST request on the configured url, with the set of parameters supplied as either a query string,
     * or an associative array of key/value pairs
     *
     * Returns the body data returned by the request
     */

    function read ($params=NULL, $method="GET") {
        if ($method == "GET") {
            if (!($this->setopt (CURLOPT_HTTPGET, TRUE))) {
                $this->error = "Cannot set HTTP method to GET";
                return FALSE;
            }

            if (($params != NULL) && (!($this->set_url ($this->url."?".$this->convert_params ($params))))) {
                $this->error = "Cannot set url with GET parameters added";
                return FALSE;
            }
        } else if ($method == "POST") {
            if (!($this->setopt (CURLOPT_POST, TRUE))) {
                $this->error = "Cannot set HTTP method to POST";
                return FALSE;
            }

            if (($params != NULL) && (!($this->setopt (CURLOPT_POSTFIELDS, $this->convert_params ($params))))) {
                $this->error = "Cannot set POSTFIELDS";
                return FALSE;
            }
        } else {
            $this->error = "Method ".$method." not implemented";
            return FALSE;
        }

        $this->data = curl_exec ($this->curl_handle);
        $this->info = curl_getinfo ($this->curl_handle);

        return $this->data;
    }

    /*
     * Closes the curl handle
     */

    function close () {
        curl_close ($this->curl_handle);
    }

    /*
     * Destructor
     */

    function __destruct () {
        $this->close ();
    }
}
?>
