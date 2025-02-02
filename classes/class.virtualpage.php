<?php

	/*
	 * Virtual Themed Page class
	 *
	 * This class implements virtual pages for a plugin.
	 *
	 * It is designed to be included then called for each part of the plugin
	 * that wants virtual pages.
	 *
	 * It supports multiple virtual pages and content generation functions.
	 * The content functions are only called if a page matches.
	 *
	 * The class uses the theme templates and as far as I know is unique in that.
	 * It also uses child theme templates ahead of main theme templates.
	 *
	 * Example code follows class.
	 *
	 * August 2013 Brian Coogan
	 *
	 */




	// There are several virtual page classes, we want to avoid a clash!
	//
	//
	if (!class_exists('geoseo_Virtual_Themed_Pages')) {

		class geoseo_Virtual_Themed_Pages {

			public $title = '';
			public $body = '';
			public $slug = '';
			public $dummyPostId = -1;
			private $vpages = array();  // the main array of virtual pages
			private $mypath = '';
			private $hitCount = 0;
			public $blankcomments = "views/view.blankcomments.php";

			function __construct($plugin_path = null, $blankcomments = null) {
				if (empty($plugin_path)) {
					$plugin_path = dirname(__FILE__);
				}
				$this->mypath = $plugin_path;

				if(!empty($blankcomments)) {
					$this->blankcomments = $blankcomments;
				}

				// Virtual pages are checked in the 'parse_request' filter.
				// This action starts everything off if we are a virtual page
				add_action('parse_request', array(&$this, 'vtp_parse_request'));
			}

			function add($virtual_regexp, $contentfunction) {
				$this->vpages[$virtual_regexp] = $contentfunction;
			}

			// Check page requests for Virtual pages
			// If we have one, call the appropriate content generation function
			//
			function vtp_parse_request(&$wp) {
				global $wp;
				$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
				$path = str_replace( home_url(), '', $current_url);

				$pAr = explode('/', trim($path, '/ '));

				$matched = 0;
				foreach ($this->vpages as $regexp => $func) {
					if($regexp==$pAr[0]) {
						$matched = 1;
						break;
					}
				}
				// Do nothing if not matched
				if (!$matched) {
					return;
				}

				remove_action('wp_head', 'rel_canonical');

				$settings = get_option('geo_seo_option_name');
				if(!isset($settings['fixNoTheme']) || $settings['fixNoTheme']!=1) {
				//prevent random 301 redirects to the homepage
				remove_filter('template_redirect', 'redirect_canonical');

				// setup hooks and filters to generate virtual movie page
				add_action('template_redirect', array(&$this, 'template_redir'));
				}

				add_filter('the_posts', array(&$this, 'vtp_createdummypost'));

				//remove post thumbnail (only shows up when there is a post with an id=1 that also has a featured image)
				add_filter('get_post_metadata', array(&$this,'remove_post_thumbnail'), true, 4);

				// we also force comments removal; a comments box at the footer of
				// a page is rather meaningless.
				// This requires the blank_comments.php file be provided
				add_filter('comments_template', array(&$this, 'disable_comments'), 11);

				// Call user content generation function
				// Called last so it can remove any filters it doesn't like
				// It should set:
				//    $this->body   -- body of the virtual page
				//    $this->title  -- title of the virtual page
				//    $this->template  -- optional theme-provided template
				//          eg: page
				//    $this->subtemplate -- optional subtemplate (eg movie)
				// Doco is unclear whether call by reference works for call_user_func()
				// so using call_user_func_array() instead, where it's mentioned.
				// See end of file for example code.
				//$this->template = $this->subtemplate = null;
				$this->title = null;
				unset($this->body);
				call_user_func_array($func, array(&$this, $path));

				if (!isset($this->body)) {
					$this->body = '';
				}

				// Removes potential 404 status
				$wp->query_vars['error'] = '';

				return($wp);
			}

			function remove_post_thumbnail($metadata, $object_id, $meta_key, $single) {

				//Return false if the current filter is that of a post thumbnail. Otherwise, return the original $content value.
				return ( isset($meta_key) && '_thumbnail_id' === $meta_key && $object_id==1 ) ? false : $metadata;

			}

			function filter_pagetitle($title) {
				if ($this->dummyPostId > 0) {
					return $this->title;
				}
				//check if its a blog post
				if (!is_single()) {
					return $this->title;
				}

				//if you get here then its a blog post so change the title
				global $wp_query;
				if (isset($wp_query->post->post_title)){
					return $wp_query->post->post_title;
				}

				//if wordpress can't find the title return the default
				return $this->title;
			}

			// Setup a dummy post/page
			// From the WP view, a post == a page
			//
			function vtp_createdummypost($posts) {
				//don't return the content again for pages after the first
				if($this->hitCount>0) {
					return $posts;
				}

				$this->hitCount++;

				// have to create a dummy post as otherwise many templates
				// don't call the_content filter
				global $wp, $wp_query;

				//limit the virtual page to only show one post
				$wp_query->posts_per_page = 1;

				//create a fake post intance
				$p = new stdClass;

				$settings = get_option('geo_seo_option_name');

				if(isset($settings['dummyPostId']) && $settings['dummyPostId'] != '') {
					$this->dummyPostId = (int)$settings['dummyPostId'];
				}

				//force change of title - fixes issue on sites with Jupiter theme that have a post or page with id=1
				add_filter('pre_get_document_title', array(&$this,'filter_pagetitle'), 100000);

				if(isset($settings['injectTitle']) && $settings['injectTitle']==1) {
					$this->body = '<h1>'.$this->title.'</h1>'.$this->body;
				}

				// fill $p with everything a page in the database would have
				$p->ID = $this->dummyPostId;
				$p->post_author = 1;
				$p->post_date = current_time('mysql');
				$p->post_date_gmt = current_time('mysql', $gmt = 1);
				$p->post_content = $this->body;
				$p->post_title = $this->title;
				$p->post_excerpt = '';
				$p->post_status = 'publish';
				$p->comment_status = 'closed';
				$p->ping_status = 'open';
				$p->post_password = '';
				//$p->post_name = $this->slug; // slug
				$p->post_name = $_SERVER['REQUEST_URI']; // slug
				$p->to_ping = '';
				$p->pinged = '';
				$p->modified = $p->post_date;
				$p->modified_gmt = $p->post_date_gmt;
				$p->post_content_filtered = '';
				$p->post_parent = 0;
				//$p->guid = get_home_url('/'.$p->post_name); // use url instead?
				$p->guid = home_url($_SERVER['REQUEST_URI']); // use url instead?
				$p->menu_order = 0;
				$p->post_type = 'page';
				$p->post_mime_type = '';
				$p->comment_count = 0;
				$p->filter = 'raw';
				$p->ancestors = array(); // 3.6

				// reset wp_query properties to simulate a found page
				$wp_query->is_page = TRUE;
				$wp_query->is_singular = TRUE;
				$wp_query->is_single = TRUE;
				$wp_query->is_home = FALSE;
				$wp_query->is_archive = FALSE;
				$wp_query->is_category = FALSE;
				$wp_query->is_attachment = false;

				//stop page from throwing 404 error
				unset($wp_query->query['error']);
				$wp->query = array();
				$wp_query->query_vars['error'] = '';
				$wp_query->is_404 = FALSE;

				//manage comment options
				$wp_query->comment_count = 0;
				// -1 for current_comment displays comment if not logged in!
				$wp_query->current_comment = null;

				//tell wordpress a post exists
				$wp_query->current_post = -1;
				$wp_query->found_posts = 1;
				$wp_query->post_count = 1;

				$wp_query->post = $p;
				$wp_query->posts = array($p);
				$wp_query->queried_object = $p;
				$wp_query->queried_object_id = $p->ID;

				return array($p);
			}

			// Virtual Movie page - tell wordpress we are using the given
			// template if it exists; otherwise we fall back to page.php.
			//
			// This func gets called before any output to browser
			// and exits at completion.
			//
			function template_redir() {
				//    $this->body   -- body of the virtual page
				//    $this->title  -- title of the virtual page
				//    $this->template  -- optional theme-provided template eg: 'page'
				//    $this->subtemplate -- optional subtemplate (eg movie)
				//
				update_post_meta( -1, '_layout', 'full' );

				if (!empty($this->template) && !empty($this->subtemplate)) {
					// looks for in child first, then master:
					//    template-subtemplate.php, template.php
					get_template_part($this->template, $this->subtemplate);
				} elseif (!empty($this->template)) {
					// looks for in child, then master:
					//    template.php
					get_template_part($this->template);
				} elseif (!empty($this->subtemplate)) {
					// looks for in child, then master:
					//    template.php
					get_template_part($this->subtemplate);
				} else {
					get_template_part('page');
				}

				// It would be possible to add a filter for the 'the_content' filter
				// to detect that the body had been correctly output, and then to
				// die if not -- this would help a lot with error diagnosis.

				exit;
			}

			// Some templates always include comments regardless, sigh.
			// This replaces the path of the original comments template with a
			// empty template file which returns nothing, thus eliminating
			// comments reliably.
			function disable_comments($file) {
				if (file_exists($this->blankcomments)) {
					return($this->mypath . '/' . $this->blankcomments);
				}
				return($file);
			}

		}

		// class
		// Example code - you'd use something very like this in a plugin
		//
		if (0) {
			// require 'BC_Virtual_Themed_pages.php';
			// this code segment requires the WordPress environment

			$vp = new Virtual_Themed_Pages_BC();
			$vp->add('#/mypattern/unique#i', 'mytest_contentfunc');

			// Example of content generating function
			// Must set $this->body even if empty string
			function mytest_contentfunc($v, $url) {
				// extract an id from the URL
				$id = 'none';
				if (preg_match('#unique/(\d+)#', $url, $m))
					$id = $m[1];
				// could wp_die() if id not extracted successfully...

				$v->title = "My Virtual Page Title";
				$v->body = "Some body content for my virtual page test - id $id\n";
				$v->template = 'page'; // optional
				$v->subtemplate = 'billing'; // optional
			}

		}
	}