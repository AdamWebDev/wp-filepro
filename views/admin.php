<!-- This file is used to markup the administration form of the plugin. -->
<?php
	
	//must check that the user has the required capability 
    if (!current_user_can('manage_options'))
    {
      wp_die( __('You do not have sufficient permissions to access this page.') );
    }
    $opt_name = 'wp_filepro_server';

	if ( isset( $_POST[$opt_name] ) ) {
		$server_value = $_POST[$opt_name];
		update_option( $opt_name, $server_value ); ?>
		<div class="updated"><p><strong>Settings have been saved.</strong></p></div><?php 
	}

	$server_name = get_option($opt_name);
?>

<div class="wrap">
	<?php screen_icon(); ?>
	
	<form name="wp_filepro_options_form" method="post" action="" >
		<h2>Settings Page</h2>
		
		<h3>Server Settings</h3>
		<p>Please enter the URL of the FilePro server where your files are stored.</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="wp_filepro_server">Server Address</label>
				</th>
				<td>
					<input type="text" name="<?php echo $opt_name; ?>" id="<?php echo $opt_name; ?>" class="regular-text ltr" value="<?php echo $server_name ?>">
				</td>
			</tr>
		</table>
		<p class="submit">		
			<input type="submit" name="submit" id="submit" value="Save Changes" class="button button-primary" />
		</p>
		 		
	</form>
</div>