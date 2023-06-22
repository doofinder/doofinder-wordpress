<?php

namespace Doofinder\WP\Api;

use Doofinder\WP\Helpers;
use Doofinder\WP\Setup_Wizard;
use Doofinder\WP\Log;
use Doofinder\WP\Multilanguage\Multilanguage;
use Doofinder\WP\Multilanguage\No_Language_Plugin;
use Doofinder\WP\Settings;
use Exception;
use WP_Application_Passwords;

defined('ABSPATH') or die();

class Store_Api
{

    /**
     * Instance of a class used to log to a file.
     *
     * @var Log
     */
    private $log;

    /**
     * Instance of a class used to log to a file.
     *
     * @var Multilanguage
     */
    private $language;

    /**
     * API Host
     *
     * @var string
     */
    private $api_host;

    /**
     * API Key
     *
     * @var string
     */
    private $api_key;

    /**
     * APP Credentials option name
     *
     * @var string
     */
    public static $credentials_option_name = 'doofinder_for_wp_app_credentials';


    public function __construct()
    {
        // Get global disable_api_calls flag
        $this->log = new Log('stores-api.txt');

        $this->api_key = Settings::get_api_key();
        $this->api_host = Settings::get_api_host();

        $this->log->log('-------------  API HOST ------------- ');
        $this->log->log($this->api_host);

        $this->language = Multilanguage::instance();
    }

    /**
     * Create a Store, Search Engine and Datatype
     *
     * @param array  $api_keys
     *
     * @return mixed
     */
    public function create_store($api_keys)
    {
        if (is_array($api_keys)) {
            $store_payload = self::build_store_payload($api_keys);
            $this->log->log("store_data: ");
            $this->log->log($store_payload);
            return $this->sendRequest("plugins/create-store", $store_payload);
        }
    }

    /**
     * Sends a request to update the store options with the api password and to create any missing datatype
     * Payload example:
     * $payload = array(
     *    'store_options' => array(
     *        'url' => 'http://pedro-wordpress.ngrok.doofinder.com',
     *        'api_pass' => 'G41cXNeVoX4JGL2bhvbcMlQ4',
     *        'api_user' => 'pedro'
     *    ),
     *    'search_engines' => array(
     *        'fde92a8f364b8d769262974e95d82dba' => array(
     *          'feed_type' => 'post',
     *          'url' => 'http://pedro-wordpress.ngrok.doofinder.com'
     *        )
     *    )
     * )
     * @return void
     */
    public function normalize_store_and_indices()
    {
        $wizard = Setup_Wizard::instance();
        $api_keys = Setup_Wizard::are_api_keys_present($wizard->process_all_languages, $wizard->language);
        $store_payload = $this->build_store_payload($api_keys);

        $payload = [
            'store_options' => $store_payload['options']
        ];

        foreach ($store_payload['search_engines'] as $search_engine) {
            $lang = Helpers::get_language_from_locale($search_engine['language']);

            //If the installation is not multilanguage, replace the lang with ''
            if (is_a($this->language, No_Language_Plugin::class)) {
                $lang = '';
            }

            $se_hashid = Settings::get_search_engine_hash($lang);
            $payload['search_engines'][$se_hashid] = $search_engine['datatypes'][0]['datasources'][0]['options'];
        }

        $this->sendRequest("plugins/wordpress/normalize-indices/", $payload);
    }

    /**
     * Send a POST request with the given $body to the given $endpoint.
     *
     * @param string $endpoint The endpoint url.
     * @param array $body The array containing the payload to be sent.
     * @return void
     */
    private function sendRequest($endpoint, $body)
    {
        $data = [
            'headers' => [
                'Authorization' => "Token {$this->api_key}",
                'Content-Type' => 'application/json; charset=utf-8'
            ],
            'body' => json_encode($body),
            'method'      => 'POST',
            'data_format' => 'body',
        ];

        $url = "{$this->api_host}/{$endpoint}";
        $this->log->log("Making a request to: $url");
        $response = wp_remote_post($url, $data);

        if (!is_wp_error($response)) {
            $response_body = wp_remote_retrieve_body($response);
            $decoded_response = json_decode($response_body, true);
            return $decoded_response;
        } else {
            $error_message = $response->get_error_message();
            throw new Exception("Error #{$error_message} creating store structure.", $response->get_error_code());
        }
    }

    /**
     * Generates the create-store payload
     *
     * @param array $api_keys The list of search engine ids
     * @return void
     */
    public function build_store_payload($api_keys)
    {
        $primary_language = get_locale();
        if ($this->language->get_languages() != null) {
            $primary_language = $this->language->get_base_locale();
        }

        $primary_language = Helpers::format_locale_to_hyphen($primary_language);
        $domain = str_ireplace('www.', '', parse_url(get_bloginfo('url'), PHP_URL_HOST));

        $store_options =  self::get_store_options();
        if (is_null($store_options)) {
            throw new Exception("Invalid store options");
        }

        $callback_urls = self::get_callback_urls($api_keys, $primary_language);
        $currency = is_plugin_active('woocommerce/woocommerce.php') ? get_woocommerce_currency() : "EUR";

        $store_payload = [
            "name" =>  get_bloginfo('name'),
            "platform" => "wordpress",
            "primary_language" => $primary_language,
            // "skip_indexation" => true,
            "search_engines" => [],
            "sector" => Settings::get_sector(),
            "callback_urls" => $callback_urls,
            "options" => $store_options
        ];

        if (!Multilanguage::$is_multilang) {
            $api_keys = [
                '' => [
                    'hash' => 'no-hash'
                ]
            ];
        }

        foreach ($api_keys as $item) {
            //Prioritize the locale code
            $code = $item['lang']['locale'] ?? $item['lang']['code'] ?? $primary_language;
            $code = Helpers::format_locale_to_hyphen($code);
            $lang = Helpers::get_language_from_locale($code);

            // Prepare search engine body
            $this->log->log('Wizard Step 2 - Prepare Search Enginge body : ');
            $search_engine = [
                'name' => $domain . ($code ? ' (' . strtoupper($code) . ')' : ''),
                'language' => $code,
                'currency' => $currency,
                'site_url' => $this->language->get_home_url($lang),
                'datatypes' => [
                    $this->get_datatype($lang)
                ]
            ];

            $store_payload["search_engines"][] = $search_engine;
        }
        return $store_payload;
    }

    public function get_datatype($language)
    {
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            return $this->get_product_datatype($language);
        } else {
            return $this->get_post_datatype($language);
        }
    }

    /**
     * Generates the product datatype structure.
     *
     * @return array The product datatype structure.
     */
    public function get_product_datatype($language)
    {
        return [
            "name" => "product",
            "preset" => "product",
            "datasources" => [
                [
                    "type" => "wordpress",
                    "options" => [
                        "feed_type" => "product",
                        "url" => $this->language->get_home_url($language)
                    ]
                ]
            ]
        ];
    }

    /**
     * Generates the post datatype structure.
     *
     * @return array The post datatype structure.
     */
    public function get_post_datatype($language)
    {
        return [
            "name" => "post",
            "preset" => "generic",
            "datasources" => [
                [
                    "type" => "wordpress",
                    "options" => [
                        "feed_type" => "post",
                        "url" =>  $this->language->get_home_url($language)
                    ]
                ]
            ]
        ];
    }

    private function get_callback_urls($api_keys, $primary_language)
    {
        $callback_urls = [];
        $currency = 'EUR';
        foreach ($api_keys as $item) {
            $code = $item['lang']['locale'] ?? $item['lang']['code'] ?? $primary_language;
            $code = Helpers::format_locale_to_hyphen($code);
            $callback_urls[$code][$currency] = get_bloginfo('url') . '/wp-json/doofinder/v1/indexation-status/?token=' . $this->api_key;
        }
        return $callback_urls;
    }

    /**
     * Generates an api_password and returns the store options.
     *
     * @return void
     */
    private static function get_store_options()
    {
        $password_data = self::create_application_credentials();
        if (!is_null($password_data)) {
            return [
                "url" => get_bloginfo('url'),
                'api_pass' => $password_data['api_pass'],
                'api_user' => $password_data['api_user']
            ];
        }
        return NULL;
    }

    /**
     * This method checks if there is an application password set.
     *
     * @return boolean
     */
    public static function has_application_credentials()
    {
        return WP_Application_Passwords::application_name_exists_for_user(get_current_user_id(), 'doofinder');
    }

    /**
     * Creates a new application password.
     * If a password exists, it deletes it and creates a new password.
     *
     * We store the user_id and the uuid in order to know which application
     * password we must delete.
     *
     * @return array Array containing api_user and api_pass
     */
    public static function create_application_credentials()
    {
        $user_id = get_current_user_id();
        $user = get_user_by('id',  $user_id);
        $credentials_option_name = self::$credentials_option_name . "_" . get_current_blog_id();
        $credentials = get_option($credentials_option_name);
        $password_data = NULL;
        $app_name = 'doofinder_' . get_current_blog_id();

        if (is_array($credentials) && array_key_exists('user_id', $credentials) &&  array_key_exists('uuid', $credentials)) {
            WP_Application_Passwords::delete_application_password($credentials['user_id'], $credentials['uuid']);
        }

        if (!WP_Application_Passwords::application_name_exists_for_user($user_id, $app_name)) {
            $app_pass = WP_Application_Passwords::create_new_application_password($user_id, array('name' => $app_name));
            $credentials = [
                'user_id' => $user_id,
                'uuid' => $app_pass[1]['uuid']
            ];
            update_option($credentials_option_name, $credentials);

            $password_data = [
                'api_user' => $user->data->user_login,
                'api_pass' => $app_pass[0]
            ];
        }
        return $password_data;
    }
}
