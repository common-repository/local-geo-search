<?php

function geo_seo_getData( $key=null ) {

	$settings = get_option('geo_seo_option_name');

	$api = 'https://api.localgeosearch.com';
	$host = home_url();

	if($_SERVER['SERVER_NAME']=='www.localgeosearch.as') {
		$api = 'http://api.localgeosearch.as';
	}

	$data = array(
		'slug'			=>	isset($settings['slug']) ? $settings['slug'] : 'local',
		'api'			=>	$api,
		'organization'	=>	$settings['orgID'],
		'website'		=>	$settings['websiteID'],
		'websiteName'	=>	$settings['websiteName'],
		'token'			=>	$settings['token'],
		'fixNoTheme'	=>	isset($settings['fixNoTheme']) ? $settings['fixNoTheme'] : 0,
		'injectTitle'	=>	isset($settings['injectTitle']) ? $settings['injectTitle'] : 0,
		'host'			=>	$host
	);

	if($key===null) {
		return $data;
	}
	else {
		return $data[$key];
	}

}

function geo_seo_easyCurl($params) {

	$options = get_option('geo_seo_option_name');

	if(!isset($params['authentication'])) {
		$params['authentication'] = array(
			'basic'	=>	true,
			'user'	=>	$options['username'],
			'password'=>$options['token']
		);
	}

	if(!isset($params['fields']) || !is_array($params['fields'])) {
		$params['fields'] = array();
	}
	$fields_string = http_build_query($params['fields']);

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt($ch, CURLOPT_URL, $params['url']);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

	if(isset($params['header'])) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $params['header']);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	}

	if(isset($params['authentication'])) {
		if(!isset($params['authentication']['basic']) || $params['authentication']['basic']!=true) {
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
		}
		curl_setopt($ch, CURLOPT_USERPWD, $params['authentication']['user'].':'.$params['authentication']['password']);
	}

	//execute post
	$result = curl_exec($ch);

	//capture any errors from curl
	$curl_errno = curl_errno($ch);

	//close connection
	curl_close($ch);

	return $result;

}

function geo_seo_createView( $view, $data ) {

	$settings = geo_seo_getData();

	foreach($data as $k=>$v) {
		$$k = $v;
	}

	ob_start();
		include($view);
		$content = ob_get_contents();
	ob_end_clean();

	return $content;

}

class geoseotools {

	static public function easyCURL( $params ) {

		if(!isset($params['fields'])) {
			$params['fields'] = array();
		}

		if(is_array($params['fields'])) {
			$fields_string = http_build_query($params['fields']);
		}
		elseif(is_string($params['fields'])) {
			$fields_string = $params['fields'];
		}

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $params['url']);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

		if(isset($params['method'])) {
			if(strtolower($params['method'])=='post') {
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			}
			elseif(strtolower($params['method'])=='get') {
				curl_setopt($ch, CURLOPT_URL, $params['url'].'?'.$fields_string);
			}
			elseif(strtolower($params['method'])=='put') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			}
			elseif(strtolower($params['method'])=='delete') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
			}
		}
		//assume post for legacy
		else {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		}


		if(isset($params['header'])) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $params['header']);
			curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		}
		else {
			curl_setopt($ch, CURLOPT_HEADER, false);
		}

		if(isset($params['authentication'])) {
			if(!isset($params['authentication']['basic']) || $params['authentication']['basic']!=true) {
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
			}
			curl_setopt($ch, CURLOPT_USERPWD, $params['authentication']['user'].':'.$params['authentication']['password']);
		}

		//execute post
		$result = curl_exec($ch);

		//capture any errors from curl
		$curl_errno = curl_errno($ch);

		//close connection
		curl_close($ch);

		return $result;

	}


	//tools
	static public function url_origin($s, $use_forwarded_host=false) {
		$ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
		$sp = strtolower($s['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
		$port = $s['SERVER_PORT'];
		$port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
		$host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
		$host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}

	static public function full_url($s, $use_forwarded_host=false) {
		return geoseotools::url_origin($s, $use_forwarded_host) . $s['REQUEST_URI'];
	}

	static public function sid( $unsafeString ) {
		$unsafeString = str_replace( '/', '', strtolower($unsafeString));
		preg_match_all("/[a-z0-9-]+/", str_replace( ' ', '-', strtolower($unsafeString)),$matches);
		$sid = str_replace('--','-',implode('',$matches[0]));
		return $sid;
	}

}