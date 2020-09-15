<?php

/**
 * Utility class of various static functions
 *
 * @since      1.1.4
 * @package    Themify_Updater_Version
 * @author     Themify
 */

if ( !class_exists('Themify_Updater_Version') ) :
	
class Themify_Updater_Version {
	
	private $versions_url = 'https://versions.themify.me/versions.xml';
	private $bVersions_url;
	private $versions_xml;
	private $cache;
	private $filename = 'versions';
	private $upload_dir;
	private $file;

	function __construct () {
		$this->bVersions_url = Themify_Updater_utils::$uri . '/'. $this->filename .'/'. $this->filename .'.xml';
		$this->cache = new Themify_Updater_Cache();
		$this->upload_dir = wp_upload_dir();
        $this->upload_dir = rtrim($this->upload_dir['basedir'], '/') . '/themify-updater';
        $this->file = $this->upload_dir . '/'. $this->filename .'.xml';
		$this->get_xml();
	}

    /**
     * @param string $name
     * @return string
     */
    public function remote_version($name = '') {
        $version = '';

        if (is_object($this->versions_xml)) {
            $query = '//version[@name="' . $name . '"]';
            $elements = $this->versions_xml->xpath($query);
            if ( !empty($elements) ) {
                foreach ($elements as $field) {
                    $version = (string) $field;
                    break;
                }
            }
        }
        return $version;
    }

    private function get_xml() {

        if ( ! $this->check_file() ) {
            $this->fetch_file();
        }

        $content = file_get_contents( $this->file );

        $this->versions_xml = !empty($content) ? $content : null;

        unset($content);

		if ( trim($this->versions_xml) && function_exists('simplexml_load_string') ) {
            $this->versions_xml = simplexml_load_string($this->versions_xml);
        } elseif ( !function_exists('simplexml_load_string') ) {
			$notification = Themify_Updater_Notifications::get_instance();
			$notification -> add_notice( __('<b>Themify Updater</b> required PHP simplexml. please install it for plugin\'s complete functionality.', 'themify-updater'), 'error');
		}
    }

    /**
     * @return bool
     */
    private function check_file() {
        if ( ! is_file( $this->file ) ) {
            return false;
        }

        $cTime = $this->cache->get($this->filename . '_fetchTime');

        if ( !$cTime ) return false;

        return true;
    }

    private function fetch_file() {
        if ( ! is_dir( $this->upload_dir ) && !wp_mkdir_p( $this->upload_dir ) && Themify_Updater_utils::is_admin_penal() ) {
            $notification = Themify_Updater_Notifications::get_instance();
            $dir = dirname($this->upload_dir);
            $notification -> add_notice( sprintf(
                __('<b>Themify Updater</b>: %s is not writable. failed to create subdirectory.', 'themify-updater'),
                $dir
            ), 'error');
            return;
        }

        $key = $this->filename . '_fetchTime';
        $cTime = $this->cache->get($key);
        $request = new Themify_Updater_Requests();

        $xml = $request->get($this->versions_url);

		if ( empty($xml) ) { // fallback versions.xml file.
			$xml = $request->get($this->bVersions_url);
		}

        $tmp = $this->put_contents( $this->file, $xml );

        if ($tmp) {
            $this->cache->set($key, time(), 12 * HOUR_IN_SECONDS);
        }

        if ( !$tmp && Themify_Updater_utils::is_admin_penal() ) {
            $notification = Themify_Updater_Notifications::get_instance();
            $dir = dirname($this->upload_dir);
            $notification -> add_notice( sprintf(
                    __('Themify Updater: %s is not writable.', 'themify-updater'),
                    $dir
                ), 'error', false);
        }
    }

    /**
     * @param $name
     * @param $attr
     * @param bool $return_value
     * @return bool|string
     */
    public function has_attribute($name, $attr, $return_value = false) {

        $ret = false;
        $value = '';

        if (is_object($this->versions_xml)) {
            $query = '//version[@name="' . $name . '"]';
            $elements = $this->versions_xml->xpath($query);
            if ( !empty($elements) ) {
                foreach ($elements as $field) {
                    $value = isset($field[$attr]) ? (string) $field[$attr] : '';
                    $ret = empty($value) ? false : true;
                    break;
                }
            }
        }

        return $return_value ? $value : $ret;
    }

    /**
     * @param string $query
     * @return array
     */
    public function run_query ($query ) {
        if (is_object($this->versions_xml)) {
            $elements = $this->versions_xml->xpath($query);
            return $elements;
        }
        return array();
    }

	/**
	 * this function is used to check for update. it can also be used to check if product is themify's
	 *
	 * @param string $name
	 * @param string $version
	 *
	 * @return mixed
	 */
    public function is_update_available($name = '', $version = '1.0') {

        $new_version = $this->remote_version($name);

        return version_compare($version, $new_version, '<');
    }
	
	/**
	 * this function is used to check if themify server is accessable. 
	 *
	 * @return bool
	 */
	public function test_server_access() {
		$request = new Themify_Updater_Requests();
        $xml = $request->get($this->versions_url);

		if ( !empty($xml) ) {
			return true;
		}

		return false;
	}
	
	public function put_contents( $file, $contents ) {
		$fp = @fopen( $file, 'wb' );
		if ( ! $fp ) {
			return false;
		}

		mbstring_binary_safe_encoding();

		$data_length = strlen( $contents );

		$bytes_written = fwrite( $fp, $contents );

		reset_mbstring_encoding();

		fclose( $fp );

		if ( $data_length !== $bytes_written ) {
			return false;
		}

		$chmod = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : ( 0644 & ~ umask() );
		chmod( $file, $chmod );

		return true;
	}
}
endif;
