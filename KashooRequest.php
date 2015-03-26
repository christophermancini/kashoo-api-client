<?php
/**
 * A simple PHP API wrapper for the Kashoo API.
 * All post vars can be found on the developer site: http://api.kashoo.com/
 * Stay up to date on Github: https://github.com/cmm324/kashoo-api-client
 *
 * Copyright 2013 Christopher Mancini <chris@cmancini.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class KashooRequestException extends Exception {}
class KashooRequest {

    /*
     * The API token you need when making a request
     */
    protected static $_user = '';

    /*
     * The API token you need when making a request
     */
    protected static $_pass = '';

    /*
     * The API token you need when making a request
     */
    protected static $_token = '';

    /*
     * The API url we're hitting.
     */
    protected $_api_url = 'https://www.kashoo.com/api/';

    /*
     * Stores the current method we're using. (POST, PUT, GET, DELETE)
     */
    protected $_method = '';

    /*
     * Stores the current api endpoint
     */
    protected $_endpoint = '';

    /*
     * Any arguments to pass to the request
     */
    protected $_parameters = array();

    /*
     * Determines whether or not the request was successful
     */
    protected $_success = false;

    /*
     * Holds the error returned from our request
     */
    protected $_error = '';

    /*
     * Holds the response after our request
     */
    protected $_response = array();

    /*
     * Initialize the class and get authtokens
     *
     * @param string $user The user credential used to login to kashoo
     * @param string $pass The password credential used to login to kashoo
     * @return null
     */
    public static function init($user, $pass) {
        self::$_user = $user;
        self::$_pass = $pass;

        $kashoo = new self('authTokens', 'POST');
        $kashoo->request();
    }

    /*
     * Set up the request object, set endpoint and method name
     *
     * @param string $endpoint
     * @param string $method
     * @return null
     */
    public function __construct($endpoint, $method = 'GET') {
        $this->_endpoint    = $endpoint;
        $this->_method      = $method;
    }

    /*
     * Set the data/arguments we're about to request with
     *
     * @return null
     */
    public function parameters($data) {
        $this->_parameters = $data;
    }

    protected function getQueryString() {
        $queryitems = array();
        foreach($this->_parameters as $key => $value) {
            $queryitems[] = $key . '=' . $value;
        }
        return implode('&', $queryitems);
    }

    protected function getPostData() {
        return json_encode($this->_parameters);
    }

    protected function getApiUrl() {
        $querystring = '';
        if(!empty($this->_parameters) && $this->_method == 'GET') {
            $querystring = $this->getQueryString();
        }
        return $this->_api_url . $this->_endpoint . '?' . $querystring;
    }

    /*
     * Determine whether or not it was successful
     *
     * @return bool
     */
    public function success() {
        return $this->_success;
    }

    /*
     * Get the error (if there was one returned from the request)
     *
     * @return string
     */
    public function getError() {
        return $this->_error;
    }

    /*
     * Get the response from the request we made
     *
     * @return array
     */
    public function getResponse() {
        return $this->_response;
    }

    /*
     * Send the request over the wire. Return result will be binary data if the FreshBooks response is
     * a PDF, array if it is a normal request.
     *
     * @return mixed
     */
    public function request($get_token = false) {
        if($this->_endpoint != 'authTokens' && !self::$_token) {
            throw new KashooRequestException('You need to call KashooRequest::init($user, $pass).');
        }

        $ch = curl_init();    // initialize curl handle

        curl_setopt($ch, CURLOPT_URL, $this->getApiUrl()); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        curl_setopt($ch, CURLOPT_TIMEOUT, 40); // times out after 40s

        if($this->_method == 'POST') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getPostData()); // add POST fields
        }

        if($this->_endpoint == 'authTokens') {
            curl_setopt($ch, CURLOPT_USERPWD, self::$_user . ':' . self::$_pass);
        } else {
            $headers = array(
                "Accept: application/json",
                "Content-Type: application/json",
                "Authorization: TOKEN uuid:" . self::$_token,
                "User-Agent: github.com/christophermancini/kashoo-api-client", 
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $this->_headers = $headers;
        }

        $this->_response = curl_exec($ch);

        if(curl_errno($ch)) {
            echo 'A cURL error occured: ' . curl_error($ch);
            return;
        } else {
            curl_close($ch);
        }

        if($this->_endpoint == 'authTokens' && !empty($this->_response)) {
            self::$_token = $this->_response;
            $this->_success = true;
        } else {
            if($this->_response) {
              $this->_success = true;
            }
            return json_decode($this->_response);
        }
    }
}
