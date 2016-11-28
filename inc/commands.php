<?php

namespace Cyclone;

use WP_CLI, WP_CLI_Command;

/**
 * WC Cyclone - WooCommerce data generating commands.
 */
class Commands extends WP_CLI_Command {

	/**
	 * WC Cyclone Unsplash Application ID.
	 * @var string
	 */
	public $appID = '801f59c8e4af01ce9a6d677efc6a9ce55296a4aa627c6e4b1a866a7f8ad8cf96';

	/**
	 * Seed WC Cyclone with data to use when creating products.
	 *
	 * Set the Unsplash APP ID for WC Cyclone to use.
	 * 
	 * ## EXAMPLES
	 *
	 *     wp cyclone unsplash
	 *
	 * @when before_wp_load
	 */
	public function unsplash( $args, $assoc_args ) {
		echo 'Unsplash App ID: ';
		$input = fgets( STDIN );
		update_option('wc_cyclone_unsplash_app', $input);
	}

	/**
	 * Seed WC Cyclone with data to use when creating products.
	 * 
	 * ## OPTIONS
	 *
	 * [--type=<type>]
	 * : The type of resource to seed.
	 * ---
	 * default: books
	 * options:
	 *  - books
	 *  - food
	 * 
	 * ## EXAMPLES
	 *
	 *     wp cyclone seed
	 *
	 * @when before_wp_load
	 */
	public function seed( $args, $assoc_args ) {
		$app = get_option( 'wc_cyclone_unsplash_app' ) ? get_option( 'wc_cyclone_unsplash_app' ) : $this->appID;

		WP_CLI::line( 'We are going to download a collection of images from Unsplash now. Please be patient...' );

		/**
		 * Client.
		 */
		\Crew\Unsplash\HttpClient::init([
		    'applicationId' => $app,
		]);

		/**
		 * Type to seed.
		 */
		$type = $assoc_args['type'];

		/**
		 * Determine collection ID based on type.
		 */
		switch($type) {
			case 'books';
			default;
				$collectionID = 228444;
				break;
			case 'food';
				$collectionID = 140489;
				break;
		}

		// get collection
		try {
			$collection = \Crew\Unsplash\Collection::find($collectionID);
		} catch(\Crew\Unsplash\Exception $e) {
			WP_CLI::error( "No! Collection doesn\'t exist for " . $collectionID . "." );
			return;
		}

		// make directory
		$path = plugin_dir_path( __FILE__ ) . '../data/products/images/' . $type;
		if (! file_exists($path)) {
			mkdir($path);
		}

		// calculate page data
		$total = $collection->total_photos;
		$pages = ceil($total / 30);

		// create progress bar
		$progress = \WP_CLI\Utils\make_progress_bar( 'Downloading images', $total );

		// go through each page
		for($x = 1; $x < $pages; $x++) {
			// get page of photos from collection
			$photos = $collection->photos($x, 30);

			// go through each photo & download
			foreach($photos as $photo) {
				$url = $photo->urls['regular'];
				$imagePath = $path . '/' . $photo->id . '.jpg';
				if (! file_exists($imagePath)) {
					copy($url, $imagePath);
				}
				// progress +1
				$progress->tick();
			}
		}

		// progress done
		$progress->finish();

		WP_CLI::success( 'All done! ' . $total . ' "' . $type . '" images were downloaded. You can now generate products.' );

		// Update the 'seed' option so we know data has been seeded
		update_option( 'wc_cyclone_data_seeded', true );
	}

	/**
	 * Generates fake orders using the WC Order Simulator plugin.
	 *
	 * ## OPTIONS
	 *
	 * <amount>
	 * : The amount of orders to generate.
	 *
	 * [--from=<from>]
	 * : The number of days from now to start creating orders from
	 * 
	 * [--customers=<customers>]
	 * : Whether or not to create customers too - defaults to false.
	 * ---
	 * default: 180
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cyclone orders 200 --from=50
	 *
	 * @when before_wp_load
	 */
	public function orders( $args, $assoc_args ) {
		global $wpdb;

		// handle args
		list( $amount ) = $args;
		$from = isset($assoc_args['from']) ? $assoc_args['from'] : 90;

		// Chances of it being an existing customer, new customer or guest
		$chances = apply_filters('wc_cyclone_order_customer_chances', [
			'existing' => 25,
			'new' => 60,
			'guest' => 15,
		]);

		// create progress bar
		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating orders', $amount );

		for($x = 0; $x < $amount; $x++) {
			// Figure out the customer type
			$customer = Helpers::getRandomWeightedElement($chances);
			switch($customer) {
				case 'existing';
					// Get random customer ID where ID is not 1 (presumed admin ID)
					$customer = intval($wpdb->get_var("SELECT ID FROM $wpdb->users WHERE id <> 1 ORDER BY RAND() LIMIT 1"));
					break;
				case 'new';
					$customer = true;
					break;
				case 'guest';
				default;
					$customer = false;
					break;
			}

			Generate::order( $from, $customer );

			// +1 for progress
			$progress->tick();
		}

		// progress finished
		$progress->finish();

		// success message
		WP_CLI::success( $amount . ' orders generated from ' . $from . ' days ago!' );
	}

	/**
	 * Generates fake customers using the WC Order Simulator plugin.
	 *
	 * ## OPTIONS
	 *
	 * <amount>
	 * : The amount of customers to generate.
	 *
	 * [--from=<from>]
	 * : The number of days from now to start creating customers from
	 * ---
	 * default: 180
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cyclone customers 200 --from=50
	 *
	 * @when before_wp_load
	 */
	public function customers( $args, $assoc_args ) {
		// handle args
		list( $amount ) = $args;
		$from = isset( $assoc_args['from'] ) ? $assoc_args['from'] : 90;

		// create progress bar
		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating customers', $amount );

		for($x = 0; $x < $amount; $x++) {
			Generate::customer( $from );

			// progress +1
			$progress->tick();
		}

		// progress done
		$progress->finish();

		// success message
		WP_CLI::success( $amount . ' customers generated from ' . $from . ' days ago!' );
	}

	/**
	 * Generates fake products using the WC Product Generator plugin.
	 *
	 * ## OPTIONS
	 *
	 * <amount>
	 * : The amount of products to generate.
	 *
	 * [--type=<type>]
	 * : The type of products to seed
	 * ---
	 * default: books
	 * options:
	 *  - books
	 *  - food
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp cyclone products 10 --type=food
	 *
	 * @when before_wp_load
	 */
	public function products( $args, $assoc_args ) {
		// first check data has been seeded
		if ( ! get_option( 'wc_cyclone_data_seeded' ) ) {
			WP_CLI::error( 'You need to seed data first. Please run `wp cyclone seed --type=books` where type is either books or food to get started.' );
		}

		// handle args
		list( $amount ) = $args;
		$type = isset($assoc_args['type']) ? $assoc_args['type'] : 'food';

		// create progress bar
		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating products', $amount );

		$success = 0;
		for($x = 0; $x < $amount; $x++) {
			$generate = Generate::product( $type );
			if ( $generate ) {
				$success++;
			}

			// progress +1
			$progress->tick();
		}

		// progress done
		$progress->finish();

		// success message
		WP_CLI::success( $success . '/' . $amount . ' products generated!' );
	}
	
}

WP_CLI::add_command( 'cyclone', 'Cyclone\Commands' );