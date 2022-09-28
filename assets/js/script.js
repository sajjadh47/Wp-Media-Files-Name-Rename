jQuery( document ).ready( function( $ )
{
	try
	{
		var media_edit_input = $( "input[id*='wpmfne_edit_file_input']" );
		
		var attachment_url_value = $( "#attachment_url" ).val();

		if ( attachment_url_value.length )
		{
			var wpmfne_media_file = attachment_url_value.split( '/' );
		
			var wpmfne_media_file_name = $( wpmfne_media_file ).get( -1 );
			
			var wpmfne_media_file_path = attachment_url_value.replace( wpmfne_media_file_name, '' );

			wpmfne_media_file_name = wpmfne_media_file_name.split('.');

			$( media_edit_input ).closest( 'table' ).css( 'width', '100%' ).end()
				.addClass( 'form-control' )
					.attr( 'placeholder', wpmfne_media_file_name[0] )
						.closest( 'td' )
							.append( '<div class="input-group"><div class="input-group-prepend" id="media_folder_path"><span class="input-group-text">' + wpmfne_media_file_path + '</span></div><div class="input-group-append"><span class="input-group-text" id="file_extension">.' + wpmfne_media_file_name[1] +' </span></div></div><small>Everytime You Change File Name , Thumbnails of Edited File Also Regenerates!</small>' );

			$( "#media_folder_path" ).after( media_edit_input );

			var button = $( "#publish" ).clone();

			$( media_edit_input ).closest( 'table' ).after( button );
		};
	}
	catch( error )
	{
		console.log( error.message );
	}
});