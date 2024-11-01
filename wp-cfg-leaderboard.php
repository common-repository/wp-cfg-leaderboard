<?php
/*
Plugin Name: WP CFG Leaderboard
Plugin URI: http://amrap42.28a.de/wp-cgf-leaderboard-wordpress-plugin/
Description: Put an affiliate leaderboard for the CrossFit Games Open 2018 on your website
Version: 1.3.1
Author: AMRAP42 - Stefan Osterburg 
Author URI:  http://amrap42.28a.de/
Text Domain: wp-cfg-leaderboard
Domain Path: /languages
License:     GPL3
 
WP CFG Leaderboard is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
any later version.
 
WP CFG Leaderboard is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with WP CFG Leaderboard. If not, see http://www.gnu.org/licenses/gpl-3.0
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
include( plugin_dir_path( __FILE__ ).'shortcodes.php');


$cfglb_plugin_version=null;
function cfglb_plugin_get_version() {
    global $cfglb_plugin_version;
	if ($cfglb_plugin_version)
		return $cfglb_plugin_version;
	if ( ! function_exists( 'get_plugins' ) )
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $plugin_folder = get_plugins( '/' . plugin_basename( dirname( __FILE__ ) ) );
    $plugin_file = basename( ( __FILE__ ) );
    return $plugin_folder[$plugin_file]['Version'];
}
class WP_CFGLB_Plugin {

	// class instance
	static $instance;
	
	// class constructor
	public function __construct() {
		self::$instance = &$this;
	
		if ( is_admin() ){ // admin actions
			add_action( 'admin_menu', array( &$this, 'plugin_menu' ) );
			add_action( 'admin_init', array( &$this, 'register_mysettings') );
			add_filter( 'plugin_action_links_' . plugin_basename(__FILE__) ,array( &$this, 'cfglb_plugin_action_links' ));
			add_action( 'admin_enqueue_scripts', array(&$this,'cfglb_admin_scripts' ));
			
			
		} else {
			// non-admin enqueues, actions, and filters	
		}
		add_action( 'plugins_loaded', array( &$this, 'cfglb_lang' ), 5 );
		add_action('wp_enqueue_scripts', array( &$this, 'enqueue_style'),10,0);		
		
		
		$this->templates = array();
		// Add a filter to the attributes metabox to inject template into the cache.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {
			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				array( $this, 'register_project_templates' )
			);
		} else {
			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates', array( $this, 'add_new_template' )
			);
		}
		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data',
			array( $this, 'register_project_templates' )
		);
		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(
			'template_include',
			array( $this, 'view_project_template')
		);
		// Add your templates to this array.
		$this->templates = array(
			'wpcfglb_page_template.php' => 'WP CFG Leaderboard white page.',
		);
	
	}

	
public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}
	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public function register_project_templates( $atts ) {
		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );
		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		}
		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');
		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );
		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );
		return $atts;
	}
	/**
	 * Checks if the template is assigned to the page
	 */
	public function view_project_template( $template ) {
		// Return the search template if we're searching (instead of the template for the first result)
		if ( is_search() ) {
			return $template;
		}
		// Get global post
		global $post;
		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}
		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[get_post_meta(
			$post->ID, '_wp_page_template', true
		)] ) ) {
			return $template;
		}
		// Allows filtering of file path
		$filepath = apply_filters( 'page_templater_plugin_dir_path', plugin_dir_path( __FILE__ ) );
		$file =  $filepath . get_post_meta(
			$post->ID, '_wp_page_template', true
		);
		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}
		// Return template
		return $template;
	}
	

	function cfglb_admin_scripts( $hook ) {
 
		if( is_admin() ) {
			wp_enqueue_style( 'wpcfg_styles', plugin_dir_url( __FILE__ ) . 'css/admin.css',array(),cfglb_plugin_get_version());
			
		}
	}	
	
	
	
	function cfglb_plugin_action_links( $links ) {
		$settings_link = '<a href="'. esc_url( get_admin_url(null, 'options-general.php?page=wp-cfg-leaderboard') ) .'">'.__('Settings','wp-cfg-leaderboard').'</a>'; 
		array_unshift( $links, $settings_link );
		return $links;
	}
	
	function enqueue_style()
	{

	wp_enqueue_script("jquery-jsGrid", plugin_dir_url( __FILE__ ) .'js/jsgrid/jsgrid.min.js', array("jquery"), cfglb_plugin_get_version(), false);
	wp_enqueue_style("jquery-jsgrid",  plugin_dir_url( __FILE__ ) .'js/jsgrid/jsgrid.min.css', array(),cfglb_plugin_get_version());
	wp_enqueue_style("jquery-jsgrid-theme",  plugin_dir_url( __FILE__ ) .'js/jsgrid/jsgrid-theme.min.css', array(),cfglb_plugin_get_version());
	wp_enqueue_style( 'wpcfg_styles_fe', plugin_dir_url( __FILE__ ) . 'css/style.css',array(),cfglb_plugin_get_version());	
	}
	
	function cfglb_lang() {
		load_plugin_textdomain( 'wp-cfg-leaderboard', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}
	public function register_mysettings() { 
		register_setting( 'wpcfg_group', 'wpcfg_affiliateid');
		register_setting( 'wpcfg_group', 'wpcfg_showpoweredby');
		register_setting( 'wpcfg_group', 'wpcfg_measurements');
	
		
	}	

	public function plugin_menu() {
		$hook = add_options_page(
			__('CFG Leaderboard Settings','wp-cfg-leaderboard'),
			__('CFG Leaderboard','wp-cfg-leaderboard'),
			'manage_options',
			'wp-cfg-leaderboard',
			array(&$this,'plugin_settings_page')
		);
	}
	/**
	 * Plugin settings page
	 */
	public function plugin_settings_page() {
	
		?>
		
		<div class="wrap">
			 <h1><?php _e('CFG Leaderboard','wp-cfg-leaderboard');?></h1>
			
			<h2 class="nav-tab-wrapper">
			<a href="#" id="wpcfg_welcome" class="nav-tab"><?php _e('About WP CFG Leaderboard','wp-cfg-leaderboard');?></a>
			<a href="#" id="nav-tab" class="nav-tab"><?php _e('General Options','wp-cfg-leaderboard');?></a>
			<a href="#" class="nav-tab"><?php _e('Shortcode Documentation','wp-cfg-leaderboard');?></a>
		</h2>
		<form method="post" action="options.php"> 
	<?php
		settings_fields( 'wpcfg_group' );
		do_settings_sections( 'wpcfg_group' );
		wp_enqueue_script( 'wp-cfg-admin-cs', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), cfglb_plugin_get_version(), true );
		
		
		?>
		
		<section class="wpcfg_welcome">
		 <h2><?php _e('About this plugin','wp-cfg-leaderboard');?></h2>
			 
			 <p><?php _e('This plugin is provided by <a href="http://amrap42.28a.de" target="_blank">AMRAP42 - Stefan Osterburg</a>.','wp-cfg-leaderboard');?></p>
			 
			 <p><strong><img valign="middle" style="padding-right: 10px;" align="left" src="<?php echo plugins_url('img/donation.png', __FILE__); ?>"><?php _e('If you like the plugin and find it valuable for your site, please support me as the author - any amount will be appreciated.','wp-cfg-leaderboard');?></strong><br>
			 <strong><a href="https://www.paypal.me/amrap42/10" target=_blank"  rel="noopener noreferrer"> <?php _e('Please send your donation via Paypal by clicking here.','wp-cfg-leaderboard');?></a></strong><br clear="all"></p>
			 
			 <p><?php _e('DISCLAIMER: The plugin and its <a href="http://amrap42.28a.de" target="_blank"  rel="noopener">author</a> are in no way associated with or endorsed by <a href="https://games.crossfit.com" target="_blank" rel="noopener noreferrer">CrossFit Corp. or CrossFit Games</a>. The plugin relies on the Website API used by the official Games Website which could be discontinued or changed at any time.','wp-cfg-leaderboard');?></p>
			 
			 <p><?php 
			 _e('If you have ideas, problems or suggestions you can find the relevant contact information on <a href=" http://amrap42.28a.de/contact/" target="_blank" rel="noopener">http://amrap42.28a.de/contact/</a>.','wp-cfg-leaderboard');
			 ?>
			
			</p>			 
			 
		</section>
		
		
		<section class="wpcfgsection">
		<h2><?php _e('General Options','wp-cfg-leaderboard');?></h2>
		<table class="form-table">
        <tr valign="top">
        <th scope="row"><?php _e('Afilliate ID','wp-cfg-leaderboard');?></th>
        <td><input type="text" name="wpcfg_affiliateid" size="40 "value="<?php echo esc_attr( get_option('wpcfg_affiliateid') ); ?>" /><br>
		</td></tr>
		
		<tr valign="top">
        <th scope="row"><?php _e('Show measurements as','wp-cfg-leaderboard');?></th>
        <td><select name="wpcfg_measurements" >
			<option value="metric" <?php if ("metric"==get_option('wpcfg_measurements')) echo "selected"; ?> >Metric</option>
			<option value="us" <?php if ("us"==get_option('wpcfg_measurements')) echo "selected"; ?> >US</option>	
		</select><br>
		</td></tr>
		
		
		<tr valign="top">
        <th scope="row"><?php _e('Show plugin info link below leaderboards','wp-cfg-leaderboard');?></th>
        <td><input type="checkbox" name="wpcfg_showpoweredby" size="40" <?php if (get_option('wpcfg_showpoweredby')) echo "checked"; ?> /><br>
		</td></tr>
		
		
		</table></section>
	
		
		<section class="wpcfgsection">
		<h2><?php _e('Documentation','wp-cfg-leaderboard');?></h2>
		<p>
		<?php _e('This plugin provides the following shortcodes and a page template to display the Leaderbaord in an empty page without all of your blog layout.','wp-cfg-leaderboard')	?></p>
		<h2>Shortcode [wpcfg_leaderboard]</h2>
		<?php _e('Output the Games leaderboard into a post or page.','wp-cfg-leaderboard'); ?>
		<h3><?php _e('Attributes','wp-cfg-leaderboard'); ?></h3>
		<ul>
		<li><strong>affiliateid</strong>: <?php _e('You can override the affiliate id setup in the plugins settings. Please note that the affiliate id must be specified in one or the other way.','wp-cfg-leaderboard');?></li>
		<li><strong>addcolumns</strong>: <?php _e('Comma seperated list of additional columns to display that are hidden by default. Possible values: age, weight, height, division','wp-cfg-leaderboard');?></li>
		<li><strong>showevents</strong>: <?php _e('Comma seperated list of games events to show. All 5 Open events are shown by default. Possible values are 1,2,3,4,5.','wp-cfg-leaderboard');?></li>
		
		<h3><?php _e('Examples','wp-cfg-leaderboard'); ?></h3>
		<ul>
		<li><strong>[wpcfg_leaderboard] </strong>: <?php _e("Display the leaderboard for the affiliate id specified in the plugin settings. No additional columns and all events are shown.",'wp-cfg-leaderboard');?></li>
		<li><strong>[wpcfg_leaderboard showevents="1,2"] </strong>: <?php _e("Display the leaderboard for the affiliate id specified in the plugin's settings. Only events 1 and 2 will be shown.",'wp-cfg-leaderboard');?></li>
		<li><strong>[wpcfg_leaderboard addcolumns="age,weight" showevent="1,2,3"] </strong>: <?php _e("Display the leaderboard for the affiliate id specified in the plugin's settings. Only events 1 and 2 will be shown. Columns for age and weight will be added.",'wp-cfg-leaderboard');?></li>
		</ul>
		<h2>Using the page template</h2>
		<?php _e('You can create a seperate page, based on a page template delivered with this plugin. Create a New page and select "WP CFG Leaderboard white page" under templates. In your page you can use the above shortcode to display the leaderboard you would like to see and possibly pictures, logos or other page content.','wp-cfg-leaderboard')	?></p>
		</section>
		<?php
		submit_button(__("Save all settings",'wp-cfg-leaderboard')); 
		
		?>
	</form>

	</div><?php
	}
	/** Singleton instance */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}
global $wpcfgleaderboard;
$wpcfgleaderboard = new WP_CFGLB_Plugin;