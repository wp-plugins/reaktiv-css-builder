<?php
/*
Plugin Name: Reaktiv CSS Builder
Plugin URI: http://reaktivstudios.com/plugins/
Description: Make simple CSS customizations
Version: 1.0.0
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

	Copyright 2013 Andrew Norcross

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

 // Start up the engine
class RKV_Custom_CSS_Builder {


	/**
	 * This is our constructor. There are many like it, but this one is mine.
	 *
	 * @return RKV_Custom_CSS_Builder
	 */

	public function __construct() {

		// front end
		add_action		( 'wp_enqueue_scripts',								array( $this, 'scripts_styles'			),	99		);

		// back end
		add_action		( 'plugins_loaded', 								array( $this, 'textdomain'				) 			);
		add_action		( 'admin_enqueue_scripts',							array( $this, 'admin_scripts'			)			);
		add_action		( 'admin_init', 									array( $this, 'settings'				)			);
		add_action		( 'admin_menu' ,									array( $this, 'css_edit_menu'			)			);
		add_action		( 'admin_notices',									array( $this, 'write_css'				)			);

		add_filter		( 'plugin_action_links',							array( $this, 'quick_link'				),	10,	2	);
		add_filter		( 'option_page_capability_reaktiv-custom-css',		array( $this, 'user_permission'			)			);

	}

	/**
	 * load textdomain
	 *
	 * @return void
	 */

	public function textdomain() {

		load_plugin_textdomain( 'rkvcss', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * set filename and create folder if need be for reuse
	 *
	 * @return
	 */

	static function filebase() {

		$uploads	= wp_upload_dir();
		$basedir	= $uploads['basedir'].'/custom-css/';
		$baseurl	= $uploads['baseurl'].'/custom-css/';


		// check if folder exists. if not, make it
		if ( ! is_dir( $basedir ) )
			mkdir( $basedir );

		// open the css file, or generate if one does not exist
		$blog_id	= get_current_blog_id();
		$filename	= 'reaktiv-css-'.$blog_id.'.css';

		return array(
			'dir'	=> $basedir.$filename,
			'url'	=> $baseurl.$filename,
		);

	}

	/**
	 * Load CSS
	 *
	 * @return RKV_Custom_CSS_Builder
	 */

	public function scripts_styles() {

		$file	= $this->filebase();

		if ( ! file_exists( $file['dir'] ) )
			return;

		wp_enqueue_style( 'reaktiv-custom', $file['url'], array(), null, 'all' );

	}

	/**
	 * init call CSS generation
	 *
	 * @return
	 */

	public function write_css() {

		// first check to make sure we're on our settings
		if ( !isset( $_GET['page'] ) )
			return;

		// now make sure we're actually doing our save function
		if ( !isset( $_GET['settings-updated'] ) )
			return;

		if ( $_GET['page'] !== 'reaktiv-custom-css' || $_GET['settings-updated'] !== 'true' )
			return;

		// generate the CSS
		$generate	= $this->generate_css();

		if ( $generate === true ) :
			// checks passed, display the message
			echo '<div class="updated">';
				echo '<p>'.__( 'The custom CSS has been generated.', 'rkvcss' ).'</p>';
			echo '</div>';
		else:
			// checks failed, display the message
			echo '<div class="error">';
				echo '<p>'.__( 'The custom CSS could not be generated.', 'rkvcss' ).'</p>';
			echo '</div>';
		endif;

		return;

	}

	/**
	 * actual CSS generation
	 *
	 * @return
	 */

	public function generate_css() {

		$file	= $this->filebase();

		$check	= fopen( $file['dir'], 'wb');

		if ( $check === false )
			return false;

		// get the new CSS
		$data	= get_option( 'reaktiv-custom-css' );

		$write	= trim( $data );
		fwrite( $check, $write );
		fclose( $check );

		return true;
	}

	/**
	 * Admin scripts and styles
	 *
	 * @return
	 */

	public function admin_scripts( $hook ) {

		if ( $hook == 'appearance_page_reaktiv-custom-css' ) :

			wp_enqueue_style( 'codemirror', plugins_url('lib/css/codemirror.css', __FILE__), array(), null, 'all' );
			wp_enqueue_style( 'reaktiv-css-admin', plugins_url('lib/css/reaktiv.admin.css', __FILE__), array(), null, 'all' );

			wp_enqueue_script( 'codemirror-base', plugins_url('lib/js/codemirror.js', __FILE__), array('jquery'), null, true );
			wp_enqueue_script( 'codemirror-css', plugins_url('lib/js/codemirror.css.js', __FILE__), array('jquery'), null, true );
			wp_enqueue_script( 'reaktiv-css-admin', plugins_url('lib/js/reaktiv.admin.js', __FILE__), array('jquery'), null, true );

		endif;

	}

	/**
	 * show settings link on plugins page
	 *
	 * @return
	 */

	public function quick_link( $links, $file ) {

		static $this_plugin;

		if (!$this_plugin) {
			$this_plugin = plugin_basename(__FILE__);
		}

		// check to make sure we are on the correct plugin
		if ($file == $this_plugin) {

			$settings_link	= '<a href="' . menu_page_url( 'reaktiv-custom-css', 0 ) . '">'.__( 'CSS Builder', 'rkvcss' ).'</a>';
			array_push( $links, $settings_link );

		}

		return $links;

	}

	/**
	 * Register settings
	 *
	 * @return
	 */

	public function settings() {
		register_setting( 'reaktiv-custom-css', 'reaktiv-custom-css');

	}

	/**
	 * filter user permission to allow saving without error message
	 *
	 * @return capabilities
	 */

	public function user_permission( $capability ) {

		return apply_filters( 'reaktiv_css_caps', $capability );

	}

	/**
	 * call CSS editor page
	 *
	 * @return RKV_Custom_CSS_Builder
	 */

	public function css_edit_menu() {
		add_theme_page( __( 'CSS Builder', 'rkvcss' ), __( 'CSS Builder', 'rkvcss' ), apply_filters( 'reaktiv_css_caps', 'manage_options' ), 'reaktiv-custom-css', array( $this, 'css_edit_page' ) );
	}


   /**
	 * Display CSS editor
	 *
	 * @return
	 */

	public function css_edit_page() {
		$cssdata	= get_option( 'reaktiv-custom-css' );
		?>

		<div class="wrap">
		<div class="icon32" id="icon-tools"><br></div>
		<h2><?php _e( 'Custom CSS Builder', 'rkvcss' ) ?></h2>

			<div class="reaktiv-form-wrap">

			<form class="reaktiv-custom-css" method="post" action="options.php">
				<?php settings_fields( 'reaktiv-custom-css' ); ?>

				<p><?php _e( 'Enter your CSS below and it will display on the front end of the site. Keep in mind that you may have to be more specific that the existing CSS for it to take precedent.', 'rkvcss' ); ?></p>

				<textarea name="reaktiv-custom-css" id="reaktiv-custom-css" class="widefat code"><?php echo esc_attr( $cssdata ); ?></textarea>

				<p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save CSS', 'rkvcss' ); ?>" /></p>
			</form>

			</div>

		</div>

	<?php }

/// end class
}


// Instantiate our class
$RKV_Custom_CSS_Builder = new RKV_Custom_CSS_Builder();


