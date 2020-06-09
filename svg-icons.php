<?php
/*
Plugin Name: SVG-Icons & Social Menu
Plugin URI: https://github.com/macgraphic/SVG-Icons-Social-Menu
Description: Adds SVG Icons and an easy to use Social Menu facility to your WordPress site
Author: Mark Smallman
Author URI: https://macgraphic.co.uk
Version: 1.0.1
Text Domain: svg-icons
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * SVG icon functionality.
 *
 * This makes it easier for us to get up and running with SVG icons, without
 * doing a lot of extra work or adjusting our templates.
 *
 * Currently using the <symbol> method of insertion, YMMV.
 */
$svgicons_svg_sprite_external = true;

/*
 * Inject our SVG sprite at the bottom of the page.
 *
 * There is a possibility that this will cause issues with
 * older versions of Chrome. In which case, it may be
 * necessary to inject just after the </head> tag.
 * See: https://code.google.com/p/chromium/issues/detail?id=349175
 *
 * This function currently is only used when we're not using the external method of insertion.
 */
function svgicons_svg_inject_sprite() {
	global $svgicons_svg_sprite_external;
	if ( ! $svgicons_svg_sprite_external ) :
		include_once( site_url() . '/wp-content/mu-plugins/svg-icons-assets/svg/icons.svg' );
	endif;
}
add_filter( 'wp_footer' , 'svgicons_svg_inject_sprite' );

/*
 * Implement svg4everybody in order to better support external sprite references
 * on IE8-10. For lower versions, we need an older copy of the script.
 * https://github.com/jonathantneal/svg4everybody
 */
function svgicons_svg_scripts() {
	global $svgicons_svg_sprite_external;

	/*
	 * Implement svg4everybody in order to better support external sprite references
	 * on IE8-10. For lower versions, we need an older copy of the script.
	 * https://github.com/jonathantneal/svg4everybody
	 */
	if ( $svgicons_svg_sprite_external ) :
		wp_enqueue_script( 'svg4everybody', site_url() . '/wp-content/mu-plugins/svg-icons-assets/js/svg4everybody.js', array(), '20160222', false );
		wp_enqueue_style( 'basestyles', site_url() . '/wp-content/mu-plugins/svg-icons-assets/stylesheets/svg-icons.css' );
	endif;

	/*
	 * Enqueue a script to dynamically insert SVG references in front of Sharedaddy links.
	 * We need to do this unless there's a good way to filter the Sharedaddy output via PHP.
	 * @todo: Pass the SVG code, with variable placeholders, to Javascript directly.
	 */
	if ( function_exists( 'wpcom_is_vip' ) ) :
			$svg_options = array( 'path' => wpcom_vip_noncdn_uri( site_url() ) );
		else :
			$svg_options = array( 'path' => site_url() );
		endif;

		// Register, localise, and enqueue the script.
		wp_enqueue_script( 'sharedaddy', site_url( '/wp-content/mu-plugins/svg-icons-assets/js/sharedaddy-svg.js' ), array( 'jquery' ), '20160316', false );
}
add_action( 'wp_enqueue_scripts', 'svgicons_svg_scripts' );

/*
 * Inject some header code to make IE play nice.
 *
 * This seems to do the trick, but may require more testing.
 * See: https://github.com/jonathantneal/svg4everybody
 */
function svgicons_svg_svg4everybody() {
	global $svgicons_svg_sprite_external;
	if ( $svgicons_svg_sprite_external ) :
		echo '<meta http-equiv="x-ua-compatible" content="ie=edge">';
		echo '<script type="text/javascript">svg4everybody();</script>';
	endif;
}
add_action( 'wp_head', 'svgicons_svg_svg4everybody', 20 );

/**
 * This allows us to get the SVG code and return as a variable
 * Usage: svgicons_svg_get_icon( 'name-of-icon' );
 */
function svgicons_svg_get_icon( $name, $id = null ) {
	global $svgicons_svg_sprite_external;

	$attr = 'role="img" aria-label=' . $name . ' class="svgicons-svg-icon svgicons-svg-icon-' . $name . '"';

	if ( $id ) :
		$attr .= 'id="' . $id . '"';
	endif;

	$return = '<svg ' . $attr . '>';

	if ( $svgicons_svg_sprite_external ) :
		if ( function_exists( 'wpcom_is_vip' ) ) :
			$path = wpcom_vip_noncdn_uri( site_url() );
		else :
			$path = site_url( '/wp-content/mu-plugins/svg-icons-assets/svg/icons.svg' );
		endif;
		$return .= '<use xlink:href="' . esc_url( $path ) . '#' . $name . '" />';
	else :
		$return .= '<use xlink:href="#' . $name . '" />';
	endif;
	$return .= '</svg>';
	return $return;
}

/*
 * This allows for easy injection of SVG references inline.
 * Usage: svg_icon( 'name-of-icon' );
 */
function svg_icon( $name, $id = null ) {
	echo svgicons_svg_get_icon( $name, $id );
}

/*
 * Filter our navigation menus to look for social media links.
 * When we find a match, we'll hide the text and instead show an SVG icon.
 */
function svgicons_svg_social_menu( $items ) {
	foreach ( $items as $item ) :
		$subject = $item->url;
		$feed_pattern = '/\/feed\/?/i';
		$mail_pattern = '/mailto/i';
		$skype_pattern = '/skype/i';
		$google_pattern = '/plus.google.com/i';
		$domain_pattern = '/([a-z]*)(\.com|\.org|\.io|\.tv|\.co)/i';
		$domains = array( 'codepen', 'digg', 'dribbble', 'dropbox', 'facebook', 'flickr', 'foursquare', 'github', 'instagram', 'linkedin', 'path', 'pinterest', 'getpocket', 'polldaddy', 'reddit', 'spotify', 'stumbleupon', 'tumblr', 'twitch', 'twitter', 'vimeo', 'vine', 'youtube', 'wordpress' );

		// Match feed URLs
		if ( preg_match( $feed_pattern, $subject, $matches ) ) :
			$icon = svgicons_svg_get_icon( 'feed' );
			// Match a mailto link
		elseif ( preg_match( $mail_pattern, $subject, $matches ) ) :
			$icon = svgicons_svg_get_icon( 'mail' );
			// Match a Skype link
		elseif ( preg_match( $skype_pattern, $subject, $matches ) ) :
			$icon = svgicons_svg_get_icon( 'skype' );
			// Match a Google+ link
		elseif ( preg_match( $google_pattern, $subject, $matches ) ) :
			$icon = svgicons_svg_get_icon( 'google-plus' );
			// Match various domains
		elseif ( preg_match( $domain_pattern, $subject, $matches ) && in_array( $matches[1], $domains ) ) :
			$icon = svgicons_svg_get_icon( $matches[1] );
		endif;

		// If we've found an icon, hide the text and inject an SVG
		if ( isset( $icon ) ) {
			$item->title = $icon . '<span class="screen-reader-text">' . $item->title . '</span>';
		}
	endforeach;
	return $items;
}
add_filter( 'wp_nav_menu_objects', 'svgicons_svg_social_menu' );

/*
 * Register a custom shortcode to allow users to insert SVGs.
 * This is used to insert a regular inline SVG.
 * Usage: [svg-icon name="filename"]
 */
function svgicons_svg_svg_shortcode( $atts, $content = null ) {
	$a = shortcode_atts( array(
		'file' => '',
	), $atts );
	$file = site_url() . $a['file'] . '.svg';
	if ( function_exists( 'wpcom_is_vip' ) ) :
		return wpcom_vip_file_get_contents( esc_url( $file ) );
	else :
		return file_get_contents( esc_url( $file ) );
	endif;
}
add_shortcode( 'svg-icon', 'svgicons_svg_svg_shortcode' );

/*
 * Register a custom shortcode to allow users to insert SVG icons.
 * This is used to insert SVG icons using the svgicons_svg_get_icon function.
 * Usage: [svg-icon name="name" id="id"]
 */
function svgicons_svg_icon_shortcode( $atts, $content = null ) {
	$a = shortcode_atts( array(
		'name' => '',
		'id'   => '',
	), $atts );
	return svgicons_svg_get_icon( $a['name'], $a['id'] );
}
add_shortcode( 'svg-icon', 'svgicons_svg_icon_shortcode' );
