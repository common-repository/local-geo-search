<?php
/**
* Plugin Name: Local Geo Search
* Plugin URI: https://localgeosearch.com
* Description: Local GEO Search creates hundreds of location specific pages on your site to target your services in your market.
* Version: 2.0.3
* Author: Elite Impressions, LLC
* Author URI: https://localgeosearch.com
**/

//start session
if (!session_id()) {
    session_start();
}

if(!isset($_SESSION['geoseoPlugin'])) {
	$_SESSION['geoseoPlugin'] = array(
		'sitemap'=>array(),
		'organization'=>array(),
		'website'=>array(),
		'testing'=>false
	);
}

include_once('classes/cachesys.php');

if(isset($_GET['lgscleancache'])) {
	geo_seo_cacheSys::deleteAllCache();
}

include_once('classes/fns.tools.php');
include_once('classes/class.virtualpage.php');

include_once('sitemapgen/fns.sitemap.php');

include_once('pagegen/fns.pagegen.php');

include_once('admin/admin.geoseo.php');

/* Runs when plugin is activated */
//register_activation_hook(__FILE__,'geo_seo_install');

/* Runs on plugin deactivation*/
//register_deactivation_hook( __FILE__, 'geo_seo_uninstall' );

//make the pages work
add_action('init', 'geo_seo_pageNew');

//add pages to sitemap
//yoast
add_filter( 'wpseo_sitemap_page_content', 'geo_seo_yoastSitemap' );
add_filter( 'wpseo_canonical', 'geo_seo_yoastCanonicalTag' );
//all in one seo
add_filter( 'aioseo_sitemap_additional_pages', 'geo_seo_allinoneSitemapAddlPages' );
add_filter( 'option_aioseop_options', 'geo_seo_allinoneSitemap' );
add_filter( 'aioseo_canonical_url', 'geo_seo_allinoneCanonicalTag' );

//add link to settings page
$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'geo_seo_settings_link' );

// Add settings link on plugin page
function geo_seo_settings_link($links) {
	$settings_link = '<a href="options-general.php?page=geo_seo_admin">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
  	return $links;
}