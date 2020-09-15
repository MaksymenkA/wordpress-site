<?php
namespace Photonic_Plugin\Structures;

class Photo {
	var $id, $thumbnail, $main_image, $tile_image, $download, $alt_title, $main_page, $title, $description, $video, $mime, $provider;

	/**
	 * @return mixed
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * @param mixed $id
	 */
	public function set_id($id) {
		$this->id = $id;
	}

	/**
	 * @return mixed
	 */
	public function get_thumbnail() {
		return $this->thumbnail;
	}

	/**
	 * @param mixed $thumbnail
	 */
	public function set_thumbnail($thumbnail) {
		$this->thumbnail = $thumbnail;
	}

	/**
	 * @return mixed
	 */
	public function get_main_image() {
		return $this->main_image;
	}

	/**
	 * @param mixed $main_image
	 */
	public function set_main_image($main_image) {
		$this->main_image = $main_image;
	}

	/**
	 * @return mixed
	 */
	public function get_tile_image() {
		return $this->tile_image;
	}

	/**
	 * @param mixed $tile_image
	 */
	public function set_tile_image($tile_image) {
		$this->tile_image = $tile_image;
	}

	/**
	 * @return mixed
	 */
	public function get_download() {
		return $this->download;
	}

	/**
	 * @param mixed $download
	 */
	public function set_download($download) {
		$this->download = $download;
	}

	/**
	 * @return mixed
	 */
	public function get_alt_title() {
		return $this->alt_title;
	}

	/**
	 * @param mixed $alt_title
	 */
	public function set_alt_title($alt_title) {
		$this->alt_title = $alt_title;
	}

	/**
	 * @return mixed
	 */
	public function get_main_page() {
		return $this->main_page;
	}

	/**
	 * @param mixed $main_page
	 */
	public function set_main_page($main_page) {
		$this->main_page = $main_page;
	}

	/**
	 * @return mixed
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @param mixed $title
	 */
	public function set_title($title) {
		$this->title = $title;
	}

	/**
	 * @return mixed
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * @param mixed $description
	 */
	public function set_description($description) {
		$this->description = $description;
	}

	/**
	 * @return mixed
	 */
	public function get_video() {
		return $this->video;
	}

	/**
	 * @param mixed $video
	 */
	public function set_video($video) {
		$this->video = $video;
	}

	/**
	 * @return mixed
	 */
	public function get_mime() {
		return $this->mime;
	}

	/**
	 * @param mixed $mime
	 */
	public function set_mime($mime) {
		$this->mime = $mime;
	}

	/**
	 * @return mixed
	 */
	public function get_provider() {
		return $this->provider;
	}

	/**
	 * @param mixed $provider
	 */
	public function set_provider($provider) {
		$this->provider = $provider;
	}

}