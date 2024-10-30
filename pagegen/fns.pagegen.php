<?php
function geo_seo_pageNew() {
	$slug = geo_seo_getData('slug');
	$vp = new geoseo_Virtual_Themed_Pages();
	$vp->add($slug, 'geo_seoMagic');
}

function geo_seoMagic($v, $url) {

	$settings = geo_seo_getData();

	$fullURL = geoseotools::full_url($_SERVER);

	$params = array(
		'url'			=> $settings['api'].'/pluginhtml/route',
		'method'		=> 'post',
		'fields'		=>	array(
			'url'		=>	$settings['host'],
			'slug'		=>	$settings['slug'],
			'urlType'	=>	'rewrite',
			'fullURL'	=>	$fullURL
		),
		'authentication'=> array(
			'basic'		=>	true,
			'user'		=>	'api',
			'password'	=>	$settings['token']
		)
	);

	$html = geoseotools::easyCURL($params);

	if(trim($html)=='') {
		geo_seoThrowErrorPage();
	}
	else {

		//if this parses as valid json when routing a url, it means we got an API error back
		$checkforErrors = json_decode($html, true);
		if($checkforErrors!=null) {
			$html = '';

			//save error message so we can pull it out on the admin site
			delete_option( 'geo_seo_error' );
			add_option( 'geo_seo_error', $checkforErrors['data']['msg'], '', false );

			geo_seoThrowErrorPage();
			return;
		}
		else {
			delete_option( 'geo_seo_error' );
		}

		//pull the first h1 tag out and use it for the title
		preg_match('/<h1.*?>(.*?)<\/h1>/i', $html, $match);
		if(isset($match[1]) && $match[1]!='') {
			$v->title = $match[1];
			$html = preg_replace('/<h1.*?>(.*?)<\/h1>/i', '', $html, 1);
		}

		//build the rest of the content of the page
		$v->body = $html;
		$v->template = 'page';
		$v->slug = $url;
	}

}

function geo_seoThrowErrorPage() {
	status_header(404);
	nocache_headers();
	include( get_404_template() );
	exit;
}