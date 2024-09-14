<?php
/*
Plugin Name: Rename WP Media Files Name
Plugin URI : https://wordpress.org/plugins/wp-media-files-name-rename/
Description: Change Media Attachments Files Name Easily.
Version: 1.0.7
Author: Sajjad Hossain Sagor
Author URI: https://sajjadhsagor.com
Text Domain: wp-media-files-name-rename
Domain Path: /languages

License: GPL2
This WordPress Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This free software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this software. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// ---------------------------------------------------------
// Define Plugin Folders Path
// ---------------------------------------------------------
define( "WPMFNE_PLUGIN_PATH", plugin_dir_path( __FILE__ ) );

define( "WPMFNE_PLUGIN_URL", plugin_dir_url( __FILE__ ) );

// load language translations
function wpmfne_load_plugin_textdomain()
{    
	load_plugin_textdomain( 'wp-media-files-name-rename', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}

add_action( 'plugins_loaded', 'wpmfne_load_plugin_textdomain' );

add_action( "admin_enqueue_scripts", "wpmfne_enqueue_scripts" );

function wpmfne_enqueue_scripts()
{
	// bootstrap framework css
	wp_enqueue_style ("wpcmp_bootstrap_css", WPMFNE_PLUGIN_URL . "assets/css/bootstrap.css", false );
	
	// plugin main script
	wp_enqueue_script("wpcmp_script", WPMFNE_PLUGIN_URL . "assets/js/script.js", array('jquery'), '', true );
}

add_action( 'init', 'wpmfne_rename_media_files_init' );

// add custom style to rename input field
add_action( 'admin_head', function()
{  
	?>	
	<style>
		input[id*='wpmfne_edit_file_input']{
			margin-right: -1px;
			margin-left: -1px;
			margin-top: 0;
		}
	</style>
	<?php
} );

function wpmfne_rename_media_files_init()
{
	/* Add filters to load & save media file name field */
	add_filter( 'attachment_fields_to_edit', 'wpmfne_add_file_edit_form', 11, 2 );

	add_filter( 'attachment_fields_to_save', 'wpmfne_rename_attachment_media_files_save', 11, 2 );
}

/** Returns the path of the upload directory. */
function wpmfne_get_uploads_directory()
{  
	$uploads_directory = wp_upload_dir();

	$uploads_directory = realpath( $uploads_directory["basedir"] );

	return $uploads_directory;
}

/**
 * Get all the registered image sizes along with their dimensions
 *
 * @global array $_wp_additional_image_sizes
 *
 * @link http://core.trac.wordpress.org/ticket/18947 Reference ticket
 *
 * @return array $image_sizes The image sizes
 */
function wpmfne_get_all_image_sizes()
{
	global $_wp_additional_image_sizes;

	$default_image_sizes = get_intermediate_image_sizes();

	foreach ( $default_image_sizes as $size )
	{
		$image_sizes[ $size ][ 'width' ] = intval( get_option( "{$size}_size_w" ) );
		
		$image_sizes[ $size ][ 'height' ] = intval( get_option( "{$size}_size_h" ) );
		
		$image_sizes[ $size ][ 'crop' ] = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
	}

	if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) )
	{
		$image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
	}

	return $image_sizes;
}

function wpmfne_add_file_edit_form( $form_fields, $post )
{
	/* Only show if not in Thickbox iframe */
	$screen = get_current_screen();
	
	if ( $screen->parent_base !== 'upload' ) return $form_fields;

	/* Get original filename */

	$form_fields['wpmfne_edit_file_input'] = array(
		'label' => __( 'Change File Name To :', 'wp-media-files-name-rename' )
	);

	return $form_fields;
}

function wpmfne_rename_attachment_media_files_save( $post, $attachment )
{
	/* Only proceed if filename changed and new filename input submitted */
	if ( isset( $attachment['wpmfne_edit_file_input'] ) && $attachment['wpmfne_edit_file_input'] )
	{
		// media post id
		$id = $post['ID'];

		// get the media file dir path based on the media id (https://developer.wordpress.org/reference/functions/get_attached_file/)
		$original_file = get_attached_file( $id );

		$new_file_name = $attachment['wpmfne_edit_file_input'];

		// get media file name (https://www.php.net/manual/en/function.basename.php)
		$original_file_name = basename( $original_file );

		// get media file extension (https://php.net/manual/en/function.pathinfo.php)
		$original_file_ext = pathinfo( $original_file, PATHINFO_EXTENSION );
		
		// get media file full path excluded file name + ext (https://developer.wordpress.org/reference/functions/trailingslashit/)
		$original_file_path = trailingslashit( str_replace( "\\", "/" , pathinfo( $original_file, PATHINFO_DIRNAME ) ) );

		/* Make new a filename that is sanitized and unique */
		$new_filename = wp_unique_filename( $original_file_path, $new_file_name . "." . $original_file_ext );

		// combine file path + new file name
		$new_file = $original_file_path . $new_filename;
		
		/* Rename the media with new file (https://www.php.net/manual/en/function.rename.php)  */
		rename( $original_file_path . $original_file_name, $new_file );
		
		// get _wp_attached_file of this post
		$old_wp_attached_file = get_post_meta( $id, '_wp_attached_file', true );

		$new_wp_attached_file = str_replace( $original_file_name, $new_filename, $old_wp_attached_file );

		/* Update file location in database */
		update_attached_file( $id, $new_wp_attached_file );

		// get post object
		$post_for_guid = get_post( $id );

		/* Update guid for attachment */
		$guid = str_replace( $original_file_name, $new_filename, $post_for_guid->guid );

		// update the media post with new $guid (https://developer.wordpress.org/reference/functions/wp_update_post/)
		wp_update_post( array(
			'ID' => $id,
			'guid' => $guid
		) );

		// Update metadata for that attachment (https://developer.wordpress.org/reference/functions/wp_update_attachment_metadata/)
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $new_file ) );

		WPMFNE_THUMBNAIL_CLEANER::clean_thumbnails( $original_file_path, $original_file, $original_file_ext );
	}

	return $post;
}

/**
* Class Thumbnail Cleaner
*/
class WPMFNE_THUMBNAIL_CLEANER
{
	/**
	* Main function; deletes all thumbnails for specific post stored
	* in the uploads directory.
	*
	* @since 1.0.0
	*
	* @return int
	*/
	static public function clean_thumbnails( $file_path, $file_name, $file_ext )
	{
		$all_thumbnailes_sizes = wpmfne_get_all_image_sizes();

		$file_name_excluded_ext = basename( $file_name, '.' . $file_ext );

		foreach( $all_thumbnailes_sizes as $thumbnail )
		{
			// generate thumbnail file name
			$thumbnail_file_name = $file_name_excluded_ext . '-' . $thumbnail['width'] . 'x' . $thumbnail['height'] . '.' . $file_ext;

			$full_file_path = $file_path . $thumbnail_file_name;

			// check if file exists
			if ( file_exists( $full_file_path ) && file_is_valid_image( $full_file_path ) )
			{
				@unlink( $full_file_path );
			}
		}
	}
}
