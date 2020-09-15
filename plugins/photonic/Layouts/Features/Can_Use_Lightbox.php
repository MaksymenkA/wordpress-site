<?php
namespace Photonic_Plugin\Layouts\Features;

use Photonic_Plugin\Core\Photonic;
use Photonic_Plugin\Lightboxes\Colorbox;
use Photonic_Plugin\Lightboxes\Fancybox;
use Photonic_Plugin\Lightboxes\Fancybox2;
use Photonic_Plugin\Lightboxes\Fancybox3;
use Photonic_Plugin\Lightboxes\Featherlight;
use Photonic_Plugin\Lightboxes\Image_Lightbox;
use Photonic_Plugin\Lightboxes\Lightbox;
use Photonic_Plugin\Lightboxes\Lightcase;
use Photonic_Plugin\Lightboxes\Lightgallery;
use Photonic_Plugin\Lightboxes\Magnific;
use Photonic_Plugin\Lightboxes\None;
use Photonic_Plugin\Lightboxes\PhotoSwipe;
use Photonic_Plugin\Lightboxes\PrettyPhoto;
use Photonic_Plugin\Lightboxes\Strip;
use Photonic_Plugin\Lightboxes\Swipebox;
use Photonic_Plugin\Lightboxes\Thickbox;

trait Can_Use_Lightbox {
	/**
	 * @return Lightbox
	 */
	public static function get_lightbox() {
		$map = [
			'colorbox' => 'Colorbox.php',
			'fancybox' => 'Fancybox.php',
			'fancybox2' => 'Fancybox2.php',
			'fancybox3' => 'Fancybox3.php',
			'featherlight' => 'Featherlight.php',
			'imagelightbox' => 'Image_Lightbox.php',
			'lightcase' => 'Lightcase.php',
			'lightgallery' => 'Lightgallery.php',
			'magnific' => 'Magnific.php',
			'photoswipe' => 'PhotoSwipe.php',
			'prettyphoto' => 'PrettyPhoto.php',
			'swipebox' => 'Swipebox.php',
			'strip' => 'Strip.php',
			'thickbox' => 'Thickbox.php',
			'none' => 'None.php',
		];
		$library = Photonic::$library;
		require_once(PHOTONIC_PATH.'/Lightboxes/'.$map[$library]);
		if ($library == 'colorbox') {
			$lightbox = Colorbox::get_instance();
		}
		else if ($library == 'fancybox') {
			$lightbox = Fancybox::get_instance();
		}
		else if ($library == 'fancybox2') {
			$lightbox = Fancybox2::get_instance();
		}
		else if ($library == 'fancybox3') {
			$lightbox = Fancybox3::get_instance();
		}
		else if ($library == 'featherlight') {
			$lightbox = Featherlight::get_instance();
		}
		else if ($library == 'imagelightbox') {
			$lightbox = Image_Lightbox::get_instance();
		}
		else if ($library == 'lightcase') {
			$lightbox = Lightcase::get_instance();
		}
		else if ($library == 'lightgallery') {
			$lightbox = Lightgallery::get_instance();
		}
		else if ($library == 'magnific') {
			$lightbox = Magnific::get_instance();
		}
		else if ($library == 'photoswipe') {
			$lightbox = PhotoSwipe::get_instance();
		}
		else if ($library == 'prettyphoto') {
			$lightbox = PrettyPhoto::get_instance();
		}
		else if ($library == 'swipebox') {
			$lightbox = Swipebox::get_instance();
		}
		else if ($library == 'strip') {
			$lightbox = Strip::get_instance();
		}
		else if ($library == 'thickbox') {
			$lightbox = Thickbox::get_instance();
		}
		else {
			$lightbox = None::get_instance();
		}
		return $lightbox;
	}
}