<?php
if (!class_exists('geo_seo_cacheSys')) {
class geo_seo_cacheSys {

	private static $cacheDir = '';

	private static function createCacheFolder() {
		self::$cacheDir = plugin_dir_path(__FILE__).'../cache/';
		if(!file_exists(self::$cacheDir)) {
			mkdir(self::$cacheDir);
		}
	}

	/**
     * Retrieves cached data based on the cache name and key requested
     *
     * Example Usage:
     * <code>
	 *
     * $htmlToDisplay = cacheSys::get('outputHTMLCategory', md5('filenameOutputKey'));
	 *
	 * if($htmlToDisplay===false) { //perform resource intensive logic here, eventually saving the output or data to the cache }
	 *
	 * echo $htmlToDisplay;
     * </code>
     *
     * @param string $cacheName any alpha-numeric string. used to categorize cache types.
     * @param string $key any alpha-numeric string unique within the cache category.
     *
     * @return string the contents of the cache category/key combo. FALSE if the cache is not found.
    */
	public static function get( $cacheName, $key, $maxAge=false ) {
		self::createCacheFolder();

		$content = false;
		$fileName = self::$cacheDir.$cacheName.'.'.$key.'.cache';

		//calculate expiration
		$cacheExpired = self::cacheExpired( $fileName, $maxAge );

		//get cache
		if(!$cacheExpired) {
			$content = file_get_contents($fileName);
		}

		return $content;

	}


	public static function getCategory( $category, $maxAge=false ) {
		self::createCacheFolder();

		$content = array();

		$path = self::$cacheDir;

		$handle = opendir($path);

		if($handle) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != ".." && strpos($entry, $category)!==false) {

					$cacheExpired = self::cacheExpired( $path.$entry, $maxAge );

					if(!$cacheExpired) {
						$content[] = file_get_contents( $path.$entry );
					}

				}
			}
			closedir($handle);
		}

		if(count($content)==0) {
			return false;
		}

		return $content;

	}

	private static function cacheExpired( $fileName, $maxAge ) {

		//if file does not exist, it is expired
		$cacheFileExists = file_exists($fileName);

		if(!$cacheFileExists) {
			return true;
		}

		//file exists so check based on max age
		if($maxAge===false) {
			//cache never expires
			$cacheExpired = false;
		}
		else {
			//check if it is expired
			$expirationTS = filemtime($fileName) + $maxAge;
			$cacheExpired = time() > $expirationTS;
		}

		return $cacheExpired;

	}


	/**
     * Stores content in the cache. uses the category and key variables to enable retreival
     *
     * Example Usage:
     * <code>
	 *
     * $htmlToDisplay = cacheSys::put('outputHTMLCategory', md5('filenameOutputKey'), 'content to store');
	 *
	 * if($htmlToDisplay===false) { //perform resource intensive logic here, eventually saving the output or data to the cache }
	 *
	 * echo $htmlToDisplay;
     * </code>
     *
     * @param string $cacheName any alpha-numeric string. used to categorize cache types.
     * @param string $key any alpha-numeric string unique within the cache category.
     * @param string $content any string to store in the cache
     *
     * @return boolean returns true if successful
    */
	public static function put( $cacheName, $key, $content ) {
		self::createCacheFolder();

		$fileName = self::$cacheDir.$cacheName.'.'.$key.'.cache';

		$cached = fopen($fileName, 'w');

		if($cached!==false) {
			fwrite($cached, $content);
			fclose($cached);
		}

		return true;
	}


	/**
     * Deletes all cached copies of items within a specified category.
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteCachedCategory('outputHTMLCategory');
	 *
     * </code>
     *
     * @param string $category any alpha-numeric string used to categorize cache types.
     *
     * @return boolean returns true if cache stores were deleted, false if no cache stores exist
    */
	public static function deleteCachedCategory( $category ) {
		self::createCacheFolder();

		$return = false;

		$path = self::$cacheDir;

		if($handle = opendir($path)) {

			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." && strpos($file, $category)!==false) {
					unlink($path.$file);
				}
			}

			closedir($handle);
		}

		return $return;

	}


	/**
     * Delete a specified cached copy within a specified category.
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteCachedItem('outputHTMLCategory', md5('filenameOutputKey'));
	 *
     * </code>
     *
     * @param string $category any alpha-numeric string used to categorize cache types.
     * @param string $key any alpha-numeric string used to uniquely identify the item to delete.
     *
     * @return boolean returns true if a cached copy was deleted, false if no cache existed
    */
	public static function deleteCachedItem( $category, $key ) {
		self::createCacheFolder();

		$fileName = self::$cacheDir.'/'.$category.'.'.$key.'.cache';

		if(file_exists($fileName)) {
			unlink($fileName);
			return true;
		}
		else {
			return false;
		}

	}


	/**
     * Delete a cached files with a specified string in the file name
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteCachedItems('outputHTMLCategory', 'stringInFilename');
	 *
     * </code>
     *
     * @param string $category any alpha-numeric string used to categorize cache types.
     * @param string $key any alpha-numeric string used to uniquely identify the item to delete.
     *
     * @return boolean returns true if a cached copy was deleted, false if no cache existed
    */
	public static function deleteCachedItems( $category, $key=null ) {
		self::createCacheFolder();

		$cacheFiles = ls(self::$cacheDir.'/cache/', true);

		$search = $key;
		if($key===null) {
			$search = $category;
		}

		foreach($cacheFiles as $file) {
			$strpos = strpos($file, $search);
			if($strpos!==false && $strpos>=0) {
				unlink(self::$cacheDir.$file);
			}
		}

		return true;

	}


	/**
     * Delete all cache copies stored by cacheSys
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteAllCache();
	 *
     * </code>
     *
     * @return boolean returns true if cached copies were deleted, false if no cache existed
    */
	public static function deleteAllCache( ) {
		self::createCacheFolder();

		$return = false;

		$path = self::$cacheDir;

		if ($handle = opendir($path)) {

			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					unlink($path.$file);
					$return = true;
				}
			}

			closedir($handle);
		}

		return $return;

	}


	/**
     * Sanitize the cache file key
     *
     * Example Usage:
     * <code>
	 *
     * $cacheKey = cacheSys::sanitizeKey( $dirtyString );
	 * $body	  = cacheSys::get( 'output', $cacheKey );
	 *
     * </code>
     *
     * @return string returns sanitized string
    */
	public static function sanitizeKey( $dirtyString ) {

		$a = str_replace('/', '~', trim($dirtyString,'/'));

		$b = str_replace('?mobileok=true', '', $a);

		$c = str_replace('?', '@', $b);

		return $c;

	}




}
}