<?php

function geo_seo_createGenericSiteMapData() {

	$settings = geo_seo_getData();

	//take sitemap data up to 12 hours old...
	if(!isset($settings['website']) || $settings['website']=='') {
		return array();
	}
	else {
		$sitemapRawData = geo_seo_cacheSys::get('sitemap'.$settings['website'],'sitemapjson', 43200);
	}

	if($sitemapRawData===false) {

		$fullURL = geoseotools::full_url($_SERVER);

		$params = array(
			'url'			=> $settings['api'].'/pluginhtml/route',
			'method'		=> 'post',
			'fields'		=>	array(
				'url'		=>	$settings['host'],
				'slug'		=>	$settings['slug'],
				'urlType'	=>	'rewrite',
				'fullURL'	=>	$fullURL,
				'format'	=>	'json'
			),
			'authentication'=> array(
				'basic'		=>	true,
				'user'		=>	'api',
				'password'	=>	$settings['token']
			)
		);

		$rawRSP = geoseotools::easyCURL($params);
		$json = json_decode($rawRSP, true);

		if($json['status']=='OK') {
			delete_option( 'geo_seo_error' );
			$sitemapRawData = $json['data']['sitemap'];
			geo_seo_cacheSys::put('sitemap'.$settings['website'],'sitemapjson', json_encode($sitemapRawData));
		}
		else {
			delete_option( 'geo_seo_error' );
			add_option( 'geo_seo_error', $json['data']['msg'], '', false );
			$sitemapRawData = array();
		}

	}
	else {
		$sitemapRawData = json_decode($sitemapRawData, true);
	}

	return $sitemapRawData;

}

function geo_seo_yoastCanonicalTag( $canonical ) {
	$slug = geo_seo_getData('slug');

	$pAr = explode('/', trim($_SERVER['REQUEST_URI'], '/ '));

	if($slug==$pAr[0]) {
		$canonical = false;
	}

	return $canonical;
}

function geo_seo_allinoneCanonicalTag( $canonical ) {
	$slug = geo_seo_getData('slug');

	$pAr = explode('/', trim($_SERVER['REQUEST_URI'], '/ '));

	if($slug==$pAr[0]) {
		$canonical = false;
	}

	return $canonical;
}

function WPSEOsitemap_url( $url ) {
	global $wpseo_sitemaps;

	if (isset( $wpseo_sitemaps->renderer ) && method_exists( $wpseo_sitemaps->renderer, 'sitemap_url' )) {
		return $wpseo_sitemaps->renderer->sitemap_url( $url );
	}

	if (method_exists( $wpseo_sitemaps, 'sitemap_url' )) {
		return $wpseo_sitemaps->sitemap_url( $url );
	}

	return '';
}

//add pages to sitemap
function geo_seo_yoastSitemap( $content ) {
	$urls = geo_seo_createGenericSiteMapData();

	foreach( $urls as $data ) {
		$url = array(
			'loc' => $data['url'],
			'pri' => $data['priority'],
			'chf' => $data['frequency'],
			'mod' => $data['modification_date']
		);

		$content .= WPSEOsitemap_url( $url );

	}

	return $content;
}

function geo_seo_allinoneSitemapAddlPages() {
	$fullURL = geoseotools::full_url($_SERVER);

	 if(strpos($fullURL,'sitemap.xml')===false) {
		return $option;
	}
	if (empty( $option )) {
		$option = Array();
	}

	$settings = geo_seo_getData();

	$urls = geo_seo_createGenericSiteMapData();

	$previousSlugs = geo_seo_cacheSys::getCategory('previousslug'.$settings['website']);

	//remove old geo seo stuff
	foreach($option as $urlIndex=>$entry) {

		//remove old slugs
		if($previousSlugs!==false) {
			foreach($previousSlugs as $pSlug) {
				if(strpos($urlIndex, '/'.$pSlug.'/')!==false) {
					unset($option[$urlIndex]);
				}
			}
		}

		//remove any under the current slug
		if(strpos($urlIndex, '/'.$settings['slug'].'/')!==false) {
			unset($option[$urlIndex]);
		}

	}

	foreach( $urls as $data ) {

		$option[] = array(
			'loc' => $data['url'],
			'priority' => $data['priority'],
			'changefreq' => $data['frequency'],
			'lastmod'  => $data['modification_date']
		);

	}

	return $option;
}

function geo_seo_allinoneSitemap( $option ) {

	$fullURL = geoseotools::full_url($_SERVER);

	 if(strpos($fullURL,'sitemap.xml')===false) {
		return $option;
	}

    if( !empty( $option ) && !empty( $option['modules'] ) && !empty( $option['modules']['aiosp_sitemap_options'])) {

		$settings = geo_seo_getData();

		$urls = geo_seo_createGenericSiteMapData();

		$previousSlugs = geo_seo_cacheSys::getCategory('previousslug'.$settings['website']);

		//remove old geo seo stuff
		foreach($option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'] as $urlIndex=>$entry) {

			//remove old slugs
			if($previousSlugs!==false) {
				foreach($previousSlugs as $pSlug) {
					if(strpos($urlIndex, '/'.$pSlug.'/')!==false) {
						unset($option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'][$urlIndex]);
					}
				}
			}

			//remove any under the current slug
			if(strpos($urlIndex, '/'.$settings['slug'].'/')!==false) {
				unset($option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'][$urlIndex]);
			}

		}

		foreach( $urls as $data ) {

			$option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'][ $data['url'] ] = array(
				'prio' => $data['priority'],
				'freq' => $data['frequency'],
				'mod'  => $data['modification_date']
			);

		}

        if ( empty( $option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'] ) ) {
            $option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'] = array();
        }

//        foreach( $my_sitemap_entries as $k => $v ) {
//            $option['modules']['aiosp_sitemap_options']['aiosp_sitemap_addl_pages'][$k] = $v;
//        }

		//can't delete because this filter gets called 4 to 5 times on each page load. the last time it's called is the one that sticks. stupid, stupid stuff.
		//geo_seo_cacheSys::deleteCachedCategory('previousslug');

    }

    return $option;

}
