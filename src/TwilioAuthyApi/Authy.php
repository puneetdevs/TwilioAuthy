<?php

/**
 * Twilio One Time Password API interface.
 *
 * @package  Twilio OTP
 * @author   Puneet Singla <puneet.devs@gmail.com>
 * @license  MIT
 */

namespace TwilioAuthy;

class Authy
{
    const VERSION = '3.1';

    protected $api_url;

    /**
     * Constructor.
     *
     * @param string $api_key Api Key
     * @param string $api_url Optional api url
     */
    public function __construct($api_key, $api_url = "https://api.authy.com", $http_handler = null)
    {
        $client_opts = [
            'base_uri'      => "{$api_url}/",
            'headers'       => ['User-Agent' => $this->__getUserAgent(), 'X-Authy-API-Key' => $api_key],
            'http_errors'   => false
        ];

        if ($http_handler != null) {
            $client_opts['handler'] = $http_handler;
        }

        $this->rest = new \GuzzleHttp\Client($client_opts);

        $this->api_url = $api_url;
        $this->default_options = ['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]];
    }

    /**
     * Register a user.
     *
     * @param  string    $email        New user's email
     * @param  string    $cellphone    New user's cellphone
     * @param  int       $country_code New user's country code. defaults to USA(1)
     * @return AuthyUser the new registered user
     */
    public function registerUser($email, $cellphone, $country_code = 1, $send_install_link = true)
    {
        $resp = $this->rest->post('protected/json/users/new', array_merge(
            $this->default_options,
            [
                'query' => [
                    'user' => [
                        "email"                     => $email,
                        "cellphone"                 => $cellphone,
                        "country_code"              => $country_code,
                        "send_install_link_via_sms" => $send_install_link,
                    ]
                ]
            ]
        ));
        $body = json_decode($resp->getBody());
        if (isset($body->user)) {
            // response is {user: {id: id}}
            return $body->user;
        }
    }

    /**
     * Verify a given token.
     *
     * @param string $authy_id User's id stored in your database
     * @param string $token    The token entered by the user
     * @param array  $opts     Array of options, for example: array("force" => "true")
     *
     * @return AuthyResponse the server response
     */
    public function verifyToken($authy_id, $token, $opts = [])
    {
        if (! array_key_exists("force", $opts)) {
            $opts["force"] = "true";
        } else {
            unset($opts["force"]);
        }

        $token = urlencode($token);
        $authy_id = urlencode($authy_id);
        $this->__validateVerify($token, $authy_id);

        $resp = $this->rest->get("protected/json/verify/{$token}/{$authy_id}", array_merge(
            $this->default_options,
            ['query' => $opts]
        ));

        return $resp;
    }

    /**
     * Request a valid token via SMS.
     *
     * @param string $authy_id User's id stored in your database
     * @param array  $opts     Array of options, for example: array("force" => "true")
     *
     * @return AuthyResponse the server response
     */
    public function requestSms($authy_id, $opts = [])
    {
        $authy_id = urlencode($authy_id);

        $resp = $this->rest->get("protected/json/sms/{$authy_id}", array_merge(
            $this->default_options,
            ['query' => $opts]
        ));
        
        $response = json_decode($resp->getBody());
        return $response;
    }

    /**
     * Cellphone call, usually used with SMS Token issues or if no smartphone is available.
     * This function needs the app to be on Starter Plan (free) or higher.
     *
     * @param string $authy_id User's id stored in your database
     * @param array  $opts     Array of options, for example: array("force" => "true")
     *
     * @return AuthyResponse the server response
     */
    public function phoneCall($authy_id, $opts = [])
    {
        $authy_id = urlencode($authy_id);
        $resp = $this->rest->get("protected/json/call/{$authy_id}", array_merge(
            $this->default_options,
            ['query' => $opts]
        ));

        $response = json_decode($resp->getBody());
        if ($response->success) {
            return $response;
        }
    }

    private function __getUserAgent()
    {
        return sprintf(
            'AuthyPHP/%s (%s-%s-%s; PHP %s)',
            Authy::VERSION,
            php_uname('s'),
            php_uname('r'),
            php_uname('m'),
            phpversion()
        );
    }

    private function __validateVerify($token, $authy_id)
    {
        $this->__validate_digit($token, "Invalid Token. Only digits accepted.");
        $this->__validate_digit($authy_id, "Invalid Authy id. Only digits accepted.");
        $length = strlen((string)$token);
        if ($length < 6 or $length > 10) {
            throw new AuthyFormatException("Invalid Token. Unexpected length.");
        }
    }

    private function __validate_digit($var, $message)
    {
        if (!is_int($var) && !is_numeric($var)) {
            throw new AuthyFormatException($message);
        }
    }
}
