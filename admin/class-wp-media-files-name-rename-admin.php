<?php
/**
 * This file contains the definition of the Wp_Media_Files_Name_Rename_Admin class, which
 * is used to load the plugin's admin-specific functionality.
 *
 * @package       Wp_Media_Files_Name_Rename
 * @subpackage    Wp_Media_Files_Name_Rename/admin
 * @author        Sajjad Hossain Sagor <sagorh672@gmail.com>
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version and other methods.
 *
 * @since    2.0.0
 */
class Wp_Media_Files_Name_Rename_Admin {
	/**
	 * The ID of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since     2.0.0
	 * @access    private
	 * @var       string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     string $plugin_name The name of this plugin.
	 * @param     string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function enqueue_styles() {
		global $pagenow, $typenow;

		/* Only show if in attachment edit page */
		if ( 'post.php' !== $pagenow || 'attachment' !== $typenow ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, WP_MEDIA_FILES_NAME_RENAME_PLUGIN_URL . 'admin/css/admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since     2.0.0
	 * @access    public
	 */
	public function enqueue_scripts() {
		global $pagenow, $typenow, $post;

		/* Only show if in attachment edit page */
		if ( 'post.php' !== $pagenow || 'attachment' !== $typenow ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name, WP_MEDIA_FILES_NAME_RENAME_PLUGIN_URL . 'admin/js/admin.js', array( 'jquery' ), $this->version, false );

		wp_localize_script(
			$this->plugin_name,
			'WpMediaFilesNameRename',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'infoTxt' => __( 'Everytime You Change File Name , Thumbnails of Edited File Are Also Regenerated!', 'wp-media-files-name-rename' ),
			)
		);
	}

	/**
	 * Adds a settings link to the plugin's action links on the plugin list table.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array $links The existing array of plugin action links.
	 * @return    array $links The updated array of plugin action links, including the settings link.
	 */
	public function add_plugin_action_links( $links ) {
		$links[] = sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'upload.php' ) ), __( 'Settings', 'wp-media-files-name-rename' ) );

		return $links;
	}

	/**
	 * Adds a custom field to the attachment edit form in the media library.
	 *
	 * This function adds a new input field to the attachment edit form,
	 * specifically for changing the file name. The field is only displayed
	 * when the user is not in the Thickbox iframe (i.e., when editing
	 * attachments in the standard media library view).
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array  $form_fields An array of existing form fields for the attachment.
	 * @param     object $post        The attachment post object.
	 * @return    array  $form_fields An array of form fields, with the new field added.
	 */
	public function attachment_fields_to_edit( $form_fields, $post ) {
		/* Only show if not in Thickbox iframe */
		$screen = get_current_screen();

		if ( $screen && 'upload' !== $screen->parent_base ) {
			return $form_fields;
		}

		$form_fields['wpmfne_edit_file_input'] = array(
			'label' => __( 'Change File Name To :', 'wp-media-files-name-rename' ),
		);

		$histories          = get_post_meta( $post->ID, 'wp_media_files_name_rename_history', true );
		$changed_from_txt   = __( 'Changed From', 'wp-media-files-name-rename' );
		$changed_to_txt     = __( 'Changed To', 'wp-media-files-name-rename' );
		$changed_moment_txt = __( 'Changed Moment', 'wp-media-files-name-rename' );
		$histories_html     = '';

		if ( is_array( $histories ) ) {
			foreach ( $histories as $key => $history ) {
				// Create a DateTime object.
				$date = new DateTime();
				$date->setTimestamp( $history['time'] );

				// Get UTC offset in seconds.
				$offset_seconds  = wp_timezone()->getOffset( $date );
				$offset_hours    = $offset_seconds / 3600; // Convert to hours.
				$timezone_string = 'UTC' . ( $offset_hours >= 0 ? '+' : '' ) . $offset_hours;

				$histories_html .= '<tr>';
				$histories_html .=
				'
					<td><code>' . esc_html( $history['old'] ) . '</code></td>
					<td><code>' . esc_html( $history['new'] ) . '</code></td>
					<td>' . esc_html( wp_date( 'j M, Y g:i:sA', $history['time'] ) ) . ' (' . $timezone_string . ')</td>
				';
				$histories_html .= '</tr>';
			}
		}

		$form_fields['wpmfne_file_rename_history'] = array(
			'label' => __( 'File Changes History :', 'wp-media-files-name-rename' ),
			'input' => 'html',
			'html'  => '
			<table class="wpmfne_file_rename_history_table" cellpadding="10" border="1">
				<thead>
					<tr>
						<th>' . $changed_from_txt . '</th>
						<th>' . $changed_to_txt . '</th>
						<th>' . $changed_moment_txt . '</th>
					</tr>
				</thead>
				<tbody>' . $histories_html . '</tbody>
			</table>',
		);

		return $form_fields;
	}

	/**
	 * Save and rename the media file.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     array $post       An array of post data.
	 * @param     array $attachment An array of attachment data.
	 * @return    array $post       The modified post data.
	 */
	public function attachment_fields_to_save( $post, $attachment ) {
		/* Only proceed if filename changed and new filename input submitted */
		if ( isset( $attachment['wpmfne_edit_file_input'] ) && ! empty( $attachment['wpmfne_edit_file_input'] ) ) {
			// media post id.
			$id = $post['ID'];

			// get the media file dir path based on the media id (https://developer.wordpress.org/reference/functions/get_attached_file/).
			$original_file = get_attached_file( $id );

			$new_file_name = sanitize_text_field( $attachment['wpmfne_edit_file_input'] );

			// get media file name (https://www.php.net/manual/en/function.basename.php).
			$original_file_name = basename( $original_file );

			// get media file extension (https://php.net/manual/en/function.pathinfo.php).
			$original_file_ext = pathinfo( $original_file, PATHINFO_EXTENSION );

			// get media file full path excluded file name + ext (https://developer.wordpress.org/reference/functions/trailingslashit/).
			$original_file_path = trailingslashit( str_replace( '\\', '/', pathinfo( $original_file, PATHINFO_DIRNAME ) ) );

			// Make new a filename that is sanitized and unique.
			$new_filename = wp_unique_filename( $original_file_path, $new_file_name . '.' . $original_file_ext );

			// combine file path + new file name.
			$new_file = $original_file_path . $new_filename;

			global $wp_filesystem;

			if ( ! $wp_filesystem ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}

			WP_Filesystem();

			// Rename the media with new file (https://developer.wordpress.org/reference/functions/wp_filesystem/).
			$wp_filesystem->move( $original_file_path . $original_file_name, $new_file, true );

			// get _wp_attached_file of this post.
			$old_wp_attached_file = get_post_meta( $id, '_wp_attached_file', true );

			$new_wp_attached_file = str_replace( $original_file_name, $new_filename, $old_wp_attached_file );

			/* Update file location in database */
			update_attached_file( $id, $new_wp_attached_file );

			// get post object.
			$post_for_guid = get_post( $id );

			// Update guid for attachment.
			$guid = str_replace( $original_file_name, $new_filename, $post_for_guid->guid );

			// update the media post with new $guid (https://developer.wordpress.org/reference/functions/wp_update_post/).
			wp_update_post(
				array(
					'ID'   => $id,
					'guid' => $guid,
				)
			);

			// Update metadata for that attachment (https://developer.wordpress.org/reference/functions/wp_update_attachment_metadata/).
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $new_file ) );

			$this->clean_thumbnails( $original_file_path, $original_file, $original_file_ext );

			$histories = get_post_meta( $id, 'wp_media_files_name_rename_history', true );

			if ( ! is_array( $histories ) ) {
				$histories = array();
			}

			$histories[] = array(
				'old'  => $original_file_name,
				'new'  => $new_filename,
				'time' => time(),
			);

			update_post_meta( $id, 'wp_media_files_name_rename_history', $histories );
		}

		return $post;
	}

	/**
	 * Get all the registered image sizes along with their dimensions
	 *
	 * @since     2.0.0
	 * @access    public
	 * @global    array $_wp_additional_image_sizes
	 * @link      http://core.trac.wordpress.org/ticket/18947
	 * @return    array $image_sizes The image sizes
	 */
	public function get_all_image_sizes() {
		global $_wp_additional_image_sizes;

		$default_image_sizes = get_intermediate_image_sizes();

		foreach ( $default_image_sizes as $size ) {
			$image_sizes[ $size ]['width']  = intval( get_option( "{$size}_size_w" ) );
			$image_sizes[ $size ]['height'] = intval( get_option( "{$size}_size_h" ) );
			$image_sizes[ $size ]['crop']   = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
		}

		if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
			$image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
		}

		return $image_sizes;
	}

	/**
	 * Deletes thumbnail files associated with a given file.
	 *
	 * This function retrieves all registered image sizes in WordPress, constructs the file paths for each thumbnail size
	 * based on the original file's name and path, and then attempts to delete those thumbnail files.
	 * It checks if the files exist and are valid images before attempting deletion.
	 *
	 * @since     2.0.0
	 * @access    public
	 * @param     string $file_path The directory path of the original file.
	 * @param     string $file_name The name of the original file, including the extension.
	 * @param     string $file_ext  The file extension of the original file.
	 * @return    void
	 */
	public function clean_thumbnails( $file_path, $file_name, $file_ext ) {
		$all_thumbnailes_sizes  = $this->get_all_image_sizes();
		$file_name_excluded_ext = basename( $file_name, '.' . $file_ext );

		foreach ( $all_thumbnailes_sizes as $thumbnail ) {
			// generate thumbnail file name.
			$thumbnail_file_name = $file_name_excluded_ext . '-' . $thumbnail['width'] . 'x' . $thumbnail['height'] . '.' . $file_ext;
			$full_file_path      = $file_path . $thumbnail_file_name;

			// check if file exists.
			if ( file_exists( $full_file_path ) && file_is_valid_image( $full_file_path ) ) {
				wp_delete_file( $full_file_path );
			}
		}

		// Scan /$file_path folder for dynamically generated sizes.
		$dynamicaly_generated_thumbnails = glob( $file_path . $file_name_excluded_ext . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE );

		if ( $dynamicaly_generated_thumbnails ) {
			foreach ( $dynamicaly_generated_thumbnails as $file ) {
				$basename = basename( $file );

				/**
				 * Match:
				 * filename-150x150.jpg
				 * filename-300x200.webp
				 */
				$pattern = '/^' . preg_quote( $file_name_excluded_ext, '/' ) . '-\d+x\d+\.(jpg|jpeg|png|gif|webp)$/i';

				if ( preg_match( $pattern, $basename ) ) {
					// check if file exists.
					if ( file_is_valid_image( $file ) ) {
						wp_delete_file( $file );
					}
				}
			}
		}
	}
}
