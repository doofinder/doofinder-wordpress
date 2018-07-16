<?php

namespace Doofinder\WP\Search;

use Doofinder\WP\Api\Search\Client;
use Doofinder\WP\Api\Search\Results;
use Doofinder\WP\Log;
use Doofinder\WP\Settings;

class Doofinder_Search {

	/**
	 * Is the class set up, everything working
	 * and we can perform the search?
	 *
	 * @var bool
	 */
	private $working = true;

	/**
	 * We'll use it to log information if anything
	 * goes wrong.
	 *
	 * @var Log
	 */
	private $log;

	/**
	 * Doofinder API Client used to perform the search.
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * Results of the performed search.
	 *
	 * @var Results
	 */
	private $results;

	/**
	 * How many posts per page are there in the results.
	 *
	 * @var int
	 */
	private $per_page;

	/**
	 * How many posts was there in the results in total.
	 *
	 * @var int
	 */
	private $found_posts;

	/**
	 * Total number of pages of results
	 *
	 * @var int
	 */
	private $pages;

	/**
	 * List of IDs of posts returned by Doofinder.
	 *
	 * @var int[]
	 */
	private $ids = array();

	public function __construct() {
		$this->log = new Log();

		$api_key = Settings::get_api_key();
		$hash    = Settings::get_search_engine_hash();

		if ( ! $api_key || ! $hash ) {
			$this->working = false;
			$this->log->log( 'API Keys are missing.' );

			return;
		}

		try {
			$this->client = new Client( $hash, $api_key );
		} catch ( \Exception $exception ) {
			$this->working = false;
			$this->log->log( $exception );
		}

	}

	/**
	 * Check the status of the search.
	 *
	 * If this returns true that means everything is ok and
	 * it's safe to perform search.
	 *
	 * @return bool
	 */
	public function is_ok() {
		return $this->working;
	}

	/**
	 * Perform a Doofinder search, grab results and extract
	 * from the results all data that will be interesting
	 * to other classes (list of post ids, number of pages
	 * of results, etc).
	 *
	 * @param string $query
	 * @param int    $page
	 * @param int    $per_page
	 */
	public function search( $query, $page = 1, $per_page = 10 ) {
		$this->per_page = $per_page;

		// Doofinder API throws exceptions when anything goes wrong./
		// We don't actually need to handle this in any way. If search
		// throws an exception, the list of IDs will be empty
		// and Internal Search will display empty list of results.
		try {
			$this->results = $this->client->query( $query, $page, array(
				'rpp' => $per_page,
			) );

			$this->extract_ids();
			$this->calculate_totals();
		} catch ( \Exception $exception ) {
			$this->log->log( $exception );
		}
	}

	/**
	 * Retrieve the list of ids of posts returned by the search.
	 *
	 * @return int[]
	 */
	public function get_ids() {
		return $this->ids;
	}

	/**
	 * Retrieve the number of posts found by Doofinder.
	 *
	 * @return int
	 */
	public function get_total_posts() {
		return $this->found_posts;
	}

	/**
	 * How many pages of posts did Doofinder find?
	 *
	 * @return int
	 */
	public function get_total_pages() {
		return $this->pages;
	}

	/**
	 * Go through the results from Doofinder and generate
	 * a list of post IDs based on the returned results.
	 */
	private function extract_ids() {
		$results   = $this->results->getResults();
		$this->ids = array();
		foreach ( $results as $result ) {
			$this->ids[] = $result['id'];
		}
	}

	/**
	 * Determine how many posts the search returned and how many
	 * pages of results there are.
	 */
	private function calculate_totals() {
		$this->found_posts = $this->results->getProperty( 'total' );
		$this->pages       = ceil( $this->found_posts / $this->per_page );
	}
}
