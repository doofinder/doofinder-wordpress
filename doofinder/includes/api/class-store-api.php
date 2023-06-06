<?php

namespace Doofinder\WP\Api;

use Doofinder\WP\Log;
use Doofinder\WP\Multilanguage\Multilanguage;
use Doofinder\WP\Settings;
use Exception;

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
            $primary_language = get_locale();
            if ($this->language->get_languages() != null) {
                $primary_language = $this->language->get_base_language();
            }
            $primary_language = $this->format_language_code($primary_language);

            $domain = str_ireplace('www.', '', parse_url(get_bloginfo('url'), PHP_URL_HOST));

            $store_data = [
                "name" =>  get_bloginfo('name'),
                "platform" => "wordpress",
                "primary_language" => $primary_language,
                // "skip_indexation" => true,
                "search_engines" => [],
                "sector" => Settings::get_sector()
            ];

            if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
                $product_datatype = $this->get_product_datatype();
                $currency = get_woocommerce_currency();
            } else {
                $product_datatype = [];
                $currency = "EUR";
            }

            foreach ($api_keys as $item) {
                if ($item['hash'] === 'no-hash') {
                    //Prioritize the locale code
                    $code = $item['lang']['locale'] ?? $item['lang']['code'] ?? $primary_language;
                    $code = $this->format_language_code($code);
                    // Prepare search engine body
                    $this->log->log('Wizard Step 2 - Prepare Search Enginge body : ');

                    $store_data["search_engines"][] = [
                        'name' => $domain . ($code ? ' (' . strtoupper($code) . ')' : ''),
                        'language' => $code,
                        'currency' => $currency,
                        'site_url' => get_bloginfo('url'),
                        'datatypes' => [
                            [
                                "name" => "post",
                                "preset" => "post",
                                "datasources" => [
                                    [
                                        "type" => "wordpress",
                                        "options" => [
                                            "feed_type" => "post",
                                            "url" =>  get_bloginfo('url')
                                        ]
                                    ]
                                ]
                            ],
                            $product_datatype
                            
                        ]
                    ];
                }
            }

            $this->log->log("store_data: ");
            $this->log->log($store_data);

            return $this->sendRequest("plugins/create-store", $store_data);
        }
    }

    private function sendRequest( $endpoint, $body ) {
		$data = [
			'headers' => [
                'Authorization' => "Token {$this->api_key}"
            ],
			'method'  => 'POST',
            'body' => json_encode($body),
        ];
        $url = "{$this->api_host}/{$endpoint}";
        $this->log->log( "Making a request to: $url");
		$response = wp_remote_request($url, $data);

		if (!is_wp_error($response)) {
			$response_body = wp_remote_retrieve_body($response);
			$decoded_response = json_decode($response_body, true);
			return $decoded_response;
		} else {
			$error_message = $response->get_error_message();
			throw new Exception("Error #{$error_message} creating store structure.", $response->get_error_code());
		}
	}


    public function format_language_code($code)
	{
		return str_replace('_', '-', $code);
	}

    public function get_product_datatype() {
        return [
            "name" => "product",
            "preset" => "product",
            "datasources" => [
                [
                    "type" => "wordpress",
                    "options" => [
                        "feed_type" => "product",
                        "url" =>  get_bloginfo('url')
                    ]
                ]
            ]
        ];
    }
}
