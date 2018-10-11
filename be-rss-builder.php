<?php
/**
 * Plugin Name: BE RSS Builder
 * Description: Allows you to build custom RSS feeds for email marketing
 * Version:     1.0.0
 * Author:      Bill Erickson
 * Author URI: https://www.billerickson.net
 * Plugin URI: https://github.com/billerickson/be-rss-builder/
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 2, as published by the
 * Free Software Foundation.  You may NOT assume that you can use any other
 * version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.
 *
 * @package    BE_RSS_Builder
 * @since      1.0.0
 * @copyright  Copyright (c) 2018, Bill Erickson
 * @license    GPL-2.0+
 */

class BE_RSS_Builder {

	public $settings = array();

	public $settings_key = 'be_filter';

	public $settings_page = 'be_rss_builder';

	public function __construct() {

		// Settings Page
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_menu', array( $this, 'maybe_redirect' ) );

		// Customize RSS Feed
		add_action( 'pre_get_posts', array( $this, 'rss_query' ) );
		add_filter( 'the_title_rss', array( $this, 'the_title_rss' ) );
		add_filter( 'the_excerpt_rss', array( $this, 'image_in_rss_content' ) );
		add_filter( 'the_content_feed', array( $this, 'image_in_rss_content' ) );
		add_action( 'rss2_ns', array( $this, 'rss_media_namespace' ) );
		add_filter( 'wp_calculate_image_srcset', array( $this, 'disable_srcset_in_feed' ) );
		add_action( 'rss2_item', array( $this, 'rss_media_content' ) );
	}

	/**
	 * Register settings page
	 *
	 */
	public function register_page() {
		add_options_page( 'RSS Builder', 'RSS Builder', 'manage_options', $this->settings_page, array( $this, 'page_content' ) );
	}

	/**
	 * If settings array is in URL, redirect to hash-based URL
	 *
	 */
	public function maybe_redirect() {

		if( ! is_admin() )
			return;

		if( empty( $_GET['page'] ) || $_GET['page'] !== $this->settings_page || empty( $_GET[ $this->settings_key ] ) || ! is_array( $_GET[ $this->settings_key ] ) )
			return;

		$hash = $this->get_settings_hash( $_GET[ $this->settings_key ] );
		$redirect_url = add_query_arg(
			array(
				$this->settings_key => $hash,
				'page' => 'be_rss_builder',
			),
			admin_url( 'options-general.php' )
		);
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Build the settings page
	 *
	 */
	public function page_content() {
		$this->settings = $this->get_feed_data();

		echo '<div class="wrap">';
		echo '<h1>RSS Builder</h1>';
		$url = $this->get_feed_url();
		echo '<p>Feed URL: <a href="' . $url . '">' . $url . '</a></p>';
		echo '<h2>Customize</h2>';

		echo '<form class="rss-builder" method="GET" action="' . admin_url( 'options-general.php' ) . '">';
		echo '<input type="hidden" name="page" value="' . $this->settings_page . '" />';

		// Category
		$category_settings = array(
			'label' => 'Limit to category:',
			'name'  => 'category',
			'empty' => 'All Categories',
			'options' => array()
		);
		$terms = get_terms( array( 'taxonomy' => 'category' ) );
		foreach( $terms as $term ) {
			$category_settings['options'][ $term->slug ] = $term->name;
		}
		$this->build_dropdown( $category_settings );

		// Orderby
		$this->build_dropdown( array(
			'label' => 'Order by',
			'name' => 'orderby',
			'empty' => 'Most Recent',
			'options' => array(
				'rand' => 'Random',
				'meta_value_num' => 'Most Shares',
			)
		));

		// Posts Per Page
		$this->build_dropdown( array(
			'label' => 'How Many',
			'name' => 'posts_per_page',
			'empty' => '(Default)',
			'options' => array(
				'1' => '1',
				'5' => '5',
				'10' => '10',
				'20' => '20',
			)
		));

		// Offset
		$this->build_dropdown( array(
			'label' => 'Offset',
			'name'  => 'offset',
			'empty' => '0',
			'options' => array(
				'0' => '0',
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5'
			)
		));

		// Date Query
		$this->build_dropdown( array(
			'label' => 'Published After',
			'name' => 'date',
			'empty' => '(All Posts)',
			'options' => array(
				'1 day ago' => '1 day ago',
				'1 month ago' => '1 month ago',
				'1 year ago' => '1 year ago',
			)
		));

		// Thumbnail Size
		$this->build_dropdown( array(
			'label' => 'Thumbnail Size',
			'name'  => 'image_size',
			'empty' => '(Default)',
			'options' => $this->image_size_options()
		));

		// Exclude "no newsletter" tag
		$this->build_checkbox( array(
			'label' => 'Exclude posts tagged "no-newsletter"',
			'name' => 'no_newsletter',
		));

		// Form Submit
		echo '<p><button type="submit" class="button">Get Feed URL</button>';

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Get Feed URL with hashed settings
	 *
	 */
	public function get_feed_url() {
		$feed = get_bloginfo( 'rss2_url' );
		$hash = $this->get_hash();
		return add_query_arg( 'be_filter', $hash, $feed );
	}

	/**
	 * Get hash based on current settings
	 *
	 */
	public function get_hash() {
		$hash = false;
		$settings = !empty( $_GET[ $this->settings_key ] ) ? $_GET[ $this->settings_key ] : false;
		if( is_array( $settings ) ) {
			$settings = array_filter( $settings );
			$hash = $this->get_settings_hash( $settings );
		} elseif( !empty( $settings ) ) {
			$hash = $settings;
		}

		return $hash;
	}

	/**
	 * Convert settings array into a hash
	 *
	 */
	public function get_settings_hash( $settings = array() ) {

		if( empty( $settings ) )
			return;

		$hash = sha1( serialize( $settings ) );

		// Save settings as option
		$feeds = maybe_unserialize( get_option( 'be_rss_builder' ) );
		if( empty( $feeds ) || ! array_key_exists( $hash, $feeds ) ) {
			$feeds[ $hash ] = $settings;
			update_option( 'be_rss_builder', maybe_serialize( $feeds ), false );
		}

		return $hash;
	}

	/**
	 * Convert hash into settings array
	 * Settings are stored as a serialized option in database.
	 * see get_settings_hash() above for how it is saved.
	 */
	public function get_feed_data() {
		$hash = !empty( $_GET[ $this->settings_key ] ) ? $_GET[ $this->settings_key ] : false;
		if( empty( $hash ) )
			return array();

		// Backwards compatibility, before we used hashes we passed full settings array in URL
		if( is_array( $hash ) )
			return $hash;

		$feeds = maybe_unserialize( get_option( 'be_rss_builder' ) );
		if( array_key_exists( $hash, $feeds ) )
			return $feeds[ $hash ];
		else
			return array();
	}

	/**
	 * Build a dropdown menu on settings page
	 *
	 */
	public function build_dropdown( $args ) {

		$current = $this->field_current( $args );

		echo '<p>';
		$this->field_label( $args );
		echo '<select name="' . $this->field_name( $args ) . '">';

		if( !empty( $args['empty'] ) )
			echo '<option value=""' . selected( false, $current, false ) . '>' . $args['empty'] . '</option>';

		foreach( $args['options'] as $value => $label ) {
			echo '<option value="' . $value . '"' . selected( $value, $current, false ) . '>' . $label . '</option>';
		}
		echo '</select></p>';
	}

	/**
	 * Build a checkbox on settings page
	 *
	 */
	public function build_checkbox( $args ) {
		echo '<p>';
		echo '<input type="checkbox" name="' . $this->field_name( $args ) . '"' . checked( $this->field_current( $args ), 'on', false ) . ' />';
		$this->field_label( $args );
		echo '</p>';
	}

	/**
	 * Get current value for field
	 *
	 */
	public function field_current( $args ) {
		return !empty( $this->settings[ $args['name'] ] ) ? esc_attr( $this->settings[ $args['name'] ] ) : false;
	}

	/**
	 * Build label for setting
	 *
	 */
	public function field_label( $args ) {
		if( !empty( $args['label'] ) )
			echo '<label style="margin-right: 10px;" for="' . $this->field_name( $args ) . '">' . $args['label'] . '</label>';
	}

	/**
	 * Get field name
	 *
	 */
	public function field_name( $args ) {
		return $this->settings_key . '[' . $args['name'] . ']';
	}

	/**
	 * Image size options
	 *
	 */
	public function image_size_options() {
		global $_wp_additional_image_sizes;
		$sizes = array(
			'thumbnail' => array(
				'width'   => intval( get_option( 'thumbnail_size_w' ) ),
				'height'  => intval( get_option( 'thumbnail_size_h' ) ),
				'crop'    => 1,
			),
		);
		$sizes = array_merge( $sizes, $_wp_additional_image_sizes );

		$output = array();
		foreach( $sizes as $size => $settings ) {
			$output[ $size ] = $size . ' (' . $settings['width'] . 'x' . $settings['height'] . ')';
		}

		return $output;
	}

	/**
	 * Customize the RSS Feed query based on feed settings
	 *
	 */
	public function rss_query( $query ) {
		if( $query->is_main_query() && ! is_admin() && $query->is_feed() ) {

			$settings = $this->get_feed_data();
			if( empty( $settings ))
				return;

			// Category
			if( !empty( $settings['category'] ) ) {
				$query->set( 'category_name', esc_attr( $settings['category'] ) );
			}

			// Orderby
			if( !empty( $settings['orderby'] ) ) {
				$query->set( 'orderby', esc_attr( $settings['orderby'] ) );
				if( 'meta_value_num' == $settings['orderby'] )
					$query->set( 'meta_key', 'shared_counts_total' );
			}

			// Posts per page
			if( !empty( $settings['posts_per_page'] ) )
				$query->set( 'posts_per_page', intval( $settings['posts_per_page'] ) );

			// Offset
			if( !empty( $settings['offset'] ) )
				$query->set( 'offset', intval( $settings['offset'] ) );

			// Date
			if( !empty( $settings['date'] ) ) {
				$date_query = array(
					array(
						'after' => $settings['date']
					)
				);
				$query->set( 'date_query', $date_query );
			}

			// No Newsletter tag
			if( !empty( $settings['no_newsletter'] ) && 'on' == $settings['no_newsletter'] ) {
				$no_newsletter_term_id = apply_filters( 'ea_no_newsletter_term_id', 18790 );
				$query->set( 'tag__not_in', array( $no_newsletter_term_id ) );
			}
		}
	}

	/**
	 * Customize the post title based on "RSS Post Title" metabox
	 *
	 */
	public function the_title_rss( $title ) {
		$alt_title = ea_cf( 'ea_rss_title' );
		return !empty( $alt_title ) ? $alt_title : $title;
	}

	/**
	 * Images in RSS Content
	 *
	 */
	public function image_in_rss_content( $content ) {

		$settings = $this->get_feed_data();
		if( !empty( $settings ) )
			return $content;

		global $post;
		if ( has_post_thumbnail( $post->ID ) ){

			$image_size = 'featured';
			$settings = $this->get_feed_data();
			if( !empty( $settings ) && !empty( $settings['image_size'] ) )
				$image_size = esc_attr( $settings['image_size'] );

			$content = get_the_post_thumbnail( $post->ID, $image_size ) . $content;
		}
		return $content;
	}

	/**
	 * Add media namespace
	 *
	 */
	function rss_media_namespace() {
	  echo 'xmlns:media="http://search.yahoo.com/mrss/"'."\n";
	}

	/**
	 * Disable srcset in feed
	 *
	 */
	function disable_srcset_in_feed( $display ) {
		if( is_feed() )
			$display = false;
		return $display;
	}

	/**
	 * Add media content
	 *
	 */
	function rss_media_content() {
		global $post;
		if( has_post_thumbnail( $post->ID ) ) {

			$image_size = 'featured';
			$settings = $this->get_feed_data();
			if( !empty( $settings ) && !empty( $settings['image_size'] ) )
				$image_size = esc_attr( $settings['image_size'] );

			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), $image_size );
			echo '<media:content url="' . $image[0] . '" width="' . $image[1] . '" height="' . $image[2] . '" medium="image" />';

		}
	}
}
new BE_RSS_Builder;
