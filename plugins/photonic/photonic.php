<?php
/**
 * Plugin Name: Photonic Gallery & Lightbox for Flickr, SmugMug, Google Photos & Others
 * Plugin URI: https://aquoid.com/plugins/photonic/
 * Description: Extends the native gallery to support Flickr, SmugMug, Google Photos, Zenfolio and Instagram. JS libraries like Swipebox, Fancybox, PhotoSwipe, Magnific, Colorbox, PrettyPhoto, Image Lightbox, Featherlight, Lightcase and Lightgallery are supported. Photos are displayed in grids of square or circular thumbnails, or slideshows, or justified or masonry or random mosaic layouts. The plugin also extends all layout options to a regular WP gallery.
 * Version: 2.45
 * Author: Sayontan Sinha
 * Author URI: https://mynethome.net/
 * License: GNU General Public License (GPL), v3 (or newer)
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: photonic
 *
 * Copyright (c) 2011 - 2020 Sayontan Sinha. All rights reserved.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

use Photonic_Plugin\Core\Photonic;

if (!defined('PHOTONIC_VERSION')) {
	define('PHOTONIC_VERSION', '2.45');
}

define('PHOTONIC_PATH', __DIR__);

if (!defined('PHOTONIC_URL')) {
	define('PHOTONIC_URL', plugin_dir_url(__FILE__));
}

$upload_dir = wp_upload_dir();
if (!defined('PHOTONIC_UPLOAD_DIR')) {
	define('PHOTONIC_UPLOAD_DIR', trailingslashit($upload_dir['basedir']).'photonic');
}

if (!defined('PHOTONIC_UPLOAD_URL')) {
	define('PHOTONIC_UPLOAD_URL', trailingslashit($upload_dir['baseurl']).'photonic');
}

require_once(PHOTONIC_PATH.'/Core/Photonic.php');

add_action('admin_init', 'photonic_utilities_init'); // Delaying the start from 10 to 100 so that CPTs can be picked up
add_action('init', 'photonic_init', 0); // Delaying the start from 10 to 100 so that CPTs can be picked up

function photonic_init() {
	global $photonic;
	$photonic = new Photonic();
}

function photonic_utilities_init() {
	require_once(PHOTONIC_PATH.'/Core/Utilities.php');
}

