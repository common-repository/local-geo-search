<?php
if (!class_exists('GEOSEOSettingsPage'))
{
class GEOSEOSettingsPage
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
		add_action( 'admin_notices',  array( $this, 'error_messages' )  );

		//ajax actions
			//add javascript to footer
			add_action( 'admin_enqueue_scripts', array( $this, 'lgs_enqueue') );
			//callback method
			add_action( 'wp_ajax_lgs_clear_cache', array( $this, 'lgs_clear_cache') );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
		add_menu_page( 'Local Geo Search', 'Local Geo Search', 'manage_options', 'geo_seo_admin', array( $this, 'create_admin_page' ), 'dashicons-location', 6 );

    }

	public function error_messages() {
		settings_errors('geo_seo_error');
	}

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'geo_seo_option_name' );
        ?>
        <div class="wrap">
            <h2>Local Geo Search Settings</h2>
			<a href="<?php echo home_url(); ?>/<?php echo $this->options['slug']; ?>">View Created Pages</a>
			&nbsp;&nbsp;
			<a href="https://manage.localgeosearch.com">Edit terms, locations, and content</a>
			&nbsp;&nbsp;
			<a href="https://manage.localgeosearch.com">View Analytics</a>
			&nbsp;&nbsp;
			<a href="#lgs-clear-cache" class="lgs-clear-cache">Clear LGS Cache</a>
			<hr>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'geo_seo_option_name' );
                do_settings_sections( 'geo_seo_admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        register_setting(
            'geo_seo_option_name', // Option group
            'geo_seo_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'geo_seo_login_section', // ID
            'Authenticate with website token', // Title
            array( $this, 'geo_seo_login_section' ), // Callback
            'geo_seo_admin' // Page
        );

			add_settings_field(
				'token',
				'Token',
				array( $this, 'field_token_callback' ),
				'geo_seo_admin',
				'geo_seo_login_section'
			);

			add_settings_field(
				'websiteName',
				'',
				array( $this, 'field_websiteName_callback' ),
				'geo_seo_admin',
				'geo_seo_login_section',
				[
					'class' => 'hidden'
				]
			);
			add_settings_field(
				'websiteID',
				'',
				array( $this, 'field_websiteID_callback' ),
				'geo_seo_admin',
				'geo_seo_login_section',
				[
					'class' => 'hidden'
				]
			);
			add_settings_field(
				'orgID',
				'',
				array( $this, 'field_orgID_callback' ),
				'geo_seo_admin',
				'geo_seo_login_section',
				[
					'class' => 'hidden'
				]
			);


        add_settings_section(
            'geo_seo_plugin_section', // ID
            'Plugin Options', // Title
            array( $this, 'section_plugin_callback' ), // Callback
            'geo_seo_admin' // Page
        );

			add_settings_field(
				'slug', // ID
				'Slug', // Title
				array( $this, 'field_slug_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_plugin_section' // Section
			);

			add_settings_field(
				'field_dummyPostId_callback', // ID
				'Override Page/Post ID', // Title
				array( $this, 'field_dummyPostId_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_plugin_section' // Section
			);


        add_settings_section(
            'geo_seo_themetweak_section', // ID
            'Theme Settings', // Title
            array( $this, 'section_themetweak_callback' ), // Callback
            'geo_seo_admin' // Page
        );

			add_settings_field(
				'fixNoTheme', // ID
				'White pages?', // Title
				array( $this, 'field_fixNoTheme_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_themetweak_section' // Section
			);

			add_settings_field(
				'injectTitle', // ID
				'No titles?', // Title
				array( $this, 'field_injectTitle_callback' ), // Callback
				'geo_seo_admin', // Page
				'geo_seo_themetweak_section' // Section
			);




    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
		//try log in

			$apiURL = geo_seo_getData('api');
			$data = geo_seo_getData();

			$params = array(
				'url'=>$apiURL.'/pluginhtml/auth',
				'fields'		=>	array(
					'url'		=>	$data['host'],
					'slug'		=>	$data['slug']
				),
				'authentication'=>array(
					'basic'	=>	true,
					'user'		=>	'api',
					'password'	=>	$input['token']
				)
			);

			$user = json_decode(geo_seo_easyCurl($params),true);

			if($user['status']=='OK') {

				delete_option( 'geo_seo_error' );

				$input['websiteName'] = $user['data']['website']['name'];
				$input['websiteID'] = $user['data']['website']['id'];
				$input['orgID'] = $user['data']['website']['org_id'];
				$input['slug'] = geoseotools::sid( $input['slug'] );

				add_settings_error(
					'geo_seo_error',
					'login-msg',
					__('Authenticated successfully with Local GEO Search website '.$user['data']['website']['name']),
					'updated'
				);

			}
			else {
				$input['websiteName'] = null;
				$input['websiteID'] = null;
				$input['orgID'] = null;

				add_settings_error(
					'geo_seo_error',
					'login-msg',
					__($user['data']['msg']),
					'error'
				);
			}

			if($data['slug']!=$input['slug']) {
				geo_seo_cacheSys::put('previousslug'.$input['websiteID'], $data['slug'], $data['slug']);
				geo_seo_cacheSys::deleteCachedItem('sitemap'.$input['websiteID'], 'sitemapjson');
			}


        return $input;
    }

    /**
     * Print the Section text
     */
	public function geo_seo_login_section()
    {
		$data = geo_seo_getData();

		$errorMessage = get_option( 'geo_seo_error' );

		if(isset($data['website']) && $data['website']!=null) {
			if($errorMessage!==false) {
				print '<div style="font-weight:bold;color:red;">This is a problem that prevented a user from seeing a Local GEO Search page: '.$errorMessage.'</div>';
			}
			else {
				print '<div style="font-weight:bold; color:#7ad03a;">Successfully authenticated with Local GEO Search website '.$data['websiteName'].'</div>';
			}
		}
		else {
			print '<div style="font-weight:bold;color:red;">Your plugin isn\'t authenticated yet. Enter the Local GEO Search website token and click Save.</div>';
		}

    }

	public function section_plugin_callback()
    {
        print 'Define the URL slug that your Local GEO Search pages will be created under. This should not be a page that exists on your site, Local Geo Search will create it for you.';
    }

	public function section_themetweak_callback()
    {
        print 'Use these options if your Local Geo Search pages do not match the rest of your site.';
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function field_token_callback()
    {
        printf(
            '<input type="token" id="token" name="geo_seo_option_name[token]" value="%s" />',
            isset( $this->options['token'] ) ? esc_attr( $this->options['token']) : ''
        );
    }

    /**
     * Get the settings option array and print one of its values
     */
    public function field_dummyPostId_callback()
    {
        printf(
            '<input type="text" id="dummyPostId" name="geo_seo_option_name[dummyPostId]" value="%s" />',
            isset( $this->options['dummyPostId'] ) ? esc_attr( $this->options['dummyPostId']) : ''
        );
    }

    public function field_slug_callback()
    {
        printf(
            home_url().'/<input type="text" id="slug" name="geo_seo_option_name[slug]" value="%s" />',
            isset( $this->options['slug'] ) ? esc_attr( $this->options['slug']) : 'local'
        );
    }

	public function field_fixNoTheme_callback()
    {
		?>
            <input type="checkbox" id="fixNoTheme" name="geo_seo_option_name[fixNoTheme]" value="1" <?php checked(1, $this->options['fixNoTheme'], true) ?> />
			Check this if your Local Geo Search pages are appearing without your site theme
        <?php
    }

	public function field_injectTitle_callback()
    {
		?>
            <input type="checkbox" id="injectTitle" name="geo_seo_option_name[injectTitle]" value="1" <?php checked(1, $this->options['injectTitle'], true) ?> />
			Check this if no titles are appearing on your Local Geo Search pages
        <?php
    }

	public function field_websiteName_callback() {
		printf(
            '<input type="hidden" id="websiteName" name="geo_seo_option_name[websiteName]" value="%s" />',
            isset( $this->options['websiteName'] ) ? esc_attr( $this->options['websiteName']) : ''
        );
	}

	public function field_websiteID_callback() {
		printf(
            '<input type="hidden" id="websiteID" name="geo_seo_option_name[websiteID]" value="%s" />',
            isset( $this->options['websiteID'] ) ? esc_attr( $this->options['websiteID']) : ''
        );
	}

	public function field_orgID_callback() {
		printf(
            '<input type="hidden" id="orgID" name="geo_seo_option_name[orgID]" value="%s" />',
            isset( $this->options['orgID'] ) ? esc_attr( $this->options['orgID']) : ''
        );
	}


	//ajax methods
	function lgs_enqueue($hook) {
		if( $hook != 'toplevel_page_geo_seo_admin') {
			// Only applies to dashboard panel
			return;
		}

		wp_enqueue_script( 'ajax-script', plugins_url( '/js/lgs-admin.js', __FILE__ ), array('jquery') );

		// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
		wp_localize_script( 'ajax-script', 'ajax_object',
            array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
				//add additional data to pass to script here
			)
		);

	}

	function lgs_clear_cache() {
		global $wpdb; // this is how you get access to the database

		geo_seo_cacheSys::deleteAllCache();

		echo 'lgs cache cleared';

		wp_die(); // this is required to terminate immediately and return a proper response
	}



}
}

if( is_admin() ) {
    $my_settings_page = new GEOSEOSettingsPage();
}