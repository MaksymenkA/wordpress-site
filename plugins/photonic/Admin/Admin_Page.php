<?php
namespace Photonic_Plugin\Admin;

abstract class Admin_Page {
	function render($header) {
		$this->start_content();
		$this->show_header($header);
		$this->render_content();
		$this->end_content();
	}

	abstract function render_content();

	function start_content() {
		?>
		<div class="photonic-wrap">
		<?php
	}

	function end_content() {
		?>
		</div>
		<?php
	}

	function show_header($header) {
		?>
		<div class="photonic-waiting"><img src="<?php echo PHOTONIC_URL.'include/images/downloading-dots.gif'; ?>" alt='waiting'/></div>
		<header class="photonic-header">
			<h1>Photonic &ndash; <?php echo $header; ?></h1>
			<div class='donate fix'>
					<ul>
						<li class='announcements'><a href='https://aquoid.com/news/'><span class="icon">&nbsp;</span>Announcements</a></li>
						<li class='support'><a href='https://wordpress.org/support/plugin/photonic/'><span class="icon">&nbsp;</span>Support</a></li>
						<li class='coffee'><a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=85LSBSV2HKSX4&source=url'><span class="icon">&nbsp;</span>Like Photonic? Buy me a coffee &hellip;</a></li>
						<li class='rate'><a href='https://wordpress.org/support/plugin/photonic/reviews/'><span class="icon">&nbsp;</span>&hellip; Or Rate it Well!</a></li>
					</ul>
			</div><!-- donate -->
		</header>
		<?php
	}
}