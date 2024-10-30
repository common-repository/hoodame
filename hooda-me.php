<?php
/*
Plugin Name: HoodaMe
Plugin URI: http://blog.hooda.de/
Description: This personal widget shows infos and avatar of the logged one.
Version: 0.91
Author: Angel Manolov 
Author URI: http://blog.hooda.de/
*/


	class am_hoodame {

		function init() {
		
			$widget = new am_hoodame();
	    	register_sidebar_widget('HoodaMe', array($widget,'display'));
		}
		
		function display() {
			
			global $user_ID, $current_user; $wp_roles;
			get_currentuserinfo();
			$options = get_option('hoodame_options');	
			
			if (!$user_ID && !$options['no_log']) //exit if won't show no logger
				return;
			
			$blog_url = get_bloginfo('url');
			$upload_dir = wp_upload_dir();
			$upload_dir = $upload_dir['baseurl'];
			
			$hm_overline = '';
			$hm_inline = '';
						
			//avatar
			if (userphoto_exists($user_ID))  
				$avatar = "<img id=\"hm_avatar\" src=\"$upload_dir/userphoto/$current_user->userphoto_thumb_file\"/>";
			else if (!$user_ID || $options['def_avatar'])
				$avatar = get_avatar($user_ID, 50);
				
				
			if ($user_ID) {
			
				$name = 	$current_user->display_name;
				$role = 	array_keys($current_user->caps);	
				$role = 	ucfirst($role[0]);	
				$posts =	get_usernumposts($user_ID);
			}
				
			//print_r($options);
			
			$title = $options['hdm_title'] ? $options['hdm_title'] : 'HoodaMe';
				
			foreach (array ('line_1','line_2','line_3','line_4','line_5','line_6','line_7') as $row) 
			{
				$value = $options[$row];
				
				if (strpos ($value,'|'))
					$value = $user_ID ? //user online or not online?
						substr($value,0,strpos($value,'|')) : 
						substr($value,strpos($value,'|')+1,strlen($value)); 
				else if (!@$user_ID) continue;
				
				if (!$value) continue; //next
				
				preg_match('/%([^\s]+)%/', $value, $set);
			
				//set the rows
				switch ($set[1]) {
				
					case 'avatar':
					
						$hm_inline .= $value ."\n";
						break;
						
					case 'name': case 'role': case 'posts':
						
						$hm_inline .= $this->addLine($value);
						break;
						
					case 'login': case 'logout': case 'register': case 'admin': 
					
						$hm_inline .= $this->addLine($value);
						break;						

					default:
						$hm_inline .= $this->addLine($value);
				}
			}
			
			if (!$hm_inline) return;
			
			//info
			$hm_inline = str_replace ('%avatar%', $avatar, $hm_inline);
			$hm_inline = str_replace ('%name%', $name, $hm_inline);
			$hm_inline = str_replace ('%role%', $role, $hm_inline);		
			$hm_inline = str_replace ('%posts%', $posts, $hm_inline);
			//links meta-like
			$hm_inline = str_replace ('%login%', "<a href=\"$blog_url/wp-login.php\">Log in</a>", $hm_inline);
			$hm_inline = str_replace ('%logout%', "<a href=\"$blog_url/wp-login.php?action=logout\">Log out</a>", $hm_inline);
			$hm_inline = str_replace ('%register%', "<a href=\"$blog_url/wp-login.php?action=register\">Register</a>", $hm_inline);		
			$hm_inline = str_replace ('%admin%', 
				$current_user->user_level < 2 ? "<a href=\"$blog_url/wp-admin/profile.php\">My Profile</a>" : "<a href=\"$blog_url/wp-admin/\">Site Admin</a>", 
				$hm_inline);
	
			?>		
			<li class="widget widget_hoodame" id="hoodame">
				<h2 class="widgettitle"><?php echo $title; ?></h2>
				<?php print ($hm_overline); ?>
				<ul>
				<?php print ($hm_inline); ?>
				</ul>
			</li>				
			<?php	
		}
		
		
		//adding widget lines
		private function addLine ($text, $link=null, $tip=null) {  //echo $text;
			
			$line = $link ? (!$tip ? "<a href=\"$link\">$text</a>" : "<a href=\"$link\" title=\"$tip\">$text</a>") : $text;
			//return $link ? "<li>$line</li>\n" : "<span style='display:block'>$line</span>\n";
			return "<li>$line</li>\n";
		}		
		
		
		
		//
		// Backend sets			
		
		function aktivate_plugin() {
		
			$options = array();
			$options['hdm_title'] 	= 'HoodaMe';
			$options['def_avatar'] 	= true;
			$options['no_log'] 		= true;
			$options['line_1'] 		= '%avatar%|%avatar%';
			$options['line_2'] 		= '<b>%name%</b>|%login%';
			$options['line_3'] 		= '%role%|%register%';
			$options['line_4'] 		= 'Posts: %posts%';
			$options['line_5'] 		= '%admin%';
			$options['line_6'] 		= '%logout%';	
			$options['line_7'] 		= "<a href='http://blog.hooda.de'>Hooda Home</a>|<a href='http://blog.hooda.de'>Hooda Home</a>";
			
			add_option('hoodame_options', $options);
		}
		
		function deaktivate_plugin() {
			delete_option('hoodame_options');
		}		
		
		
		
		function add_config_page() {
		
			if ( function_exists('add_submenu_page') ) {
				add_options_page('HoodaMe', 'HoodaMe', 10, basename(__FILE__), array('am_hoodame','config_page'));
			}
		}
		
		function config_page() 
		{				
			if ( isset($_POST['submit']) ) {  //var_dump($_POST);
			
				if (!current_user_can('manage_options')) die(__('You cannot edit the HoodaMe options.'));
				check_admin_referer('hoodame-config');

				$option_names = array ('def_avatar', 'no_log');
				foreach ($option_names as $option_name) {		
					$options[$option_name] = isset($_POST[$option_name]) ? true : false;		
				}
				
				$option_names = array ('hdm_title', 'line_1', 'line_2', 'line_3', 'line_4', 'line_5', 'line_6', 'line_7');
				foreach ($option_names as $option_name) 
				{
					if (isset($_POST[$option_name])) {
						$options[$option_name] = stripslashes($_POST[$option_name]);
					}
				}

				update_option('hoodame_options', $options);
				echo "<div id=\"message\" class=\"updated fade\"><p>Settings Updated.</p></div>\n";	
				
			}
			
			$options = get_option('hoodame_options');			
			
			?>
			
			<div class="wrap">
				<h2>HoodaMe options</h2>
				<form action="" method="post" id="hoodame-conf">	
					<?php
						if ( function_exists('wp_nonce_field') )
							wp_nonce_field('hoodame-config');
					?>
					<table class="form-table" style="width: 100%;">
						<tr valign="top">
							<th scrope="row">
								<label for="def_avatar">Avatar</label>
							</th>
							<td>
								<input type="checkbox" name="def_avatar" id="def_avatar" <?php if ($options['def_avatar']) { echo 'checked="checked"'; } ?>/>
								<label for="def_avatar">Display the default avater if no photo exists. (Check the diskussion and user photo options)</label>								
							</td>
						</tr>		
						<tr valign="top">
							<th scrope="row">
								<label for="no_log">No name</label>
							</th>
							<td>
								<input type="checkbox" name="no_log" id="no_log" <?php if ($options['no_log']) { echo 'checked="checked"'; } ?>/>
								<label for="no_log">Show the HoodaMe-widged when a user is not loggined?</label>								
							</td>
						</tr>		
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="title">Title</label>
							</th>
							<td width="90%">
								<input type="text" name="hdm_title" id="hdm_title" value="<?php echo $options['hdm_title']; ?>"/>
							</td>
						</tr>		
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_1">Rows</label>
							</th>
							<td width="90%">
								Use the following Vars to set up you're widget rows. <br>
								<b>Info Vars</b>: %avatar%, %name%, %role%, %posts% <br/>
								<b>Edit Vars</b>: %login%, %logout%, %register%, %admin% * <br/>
								<b>* %admin%</b> -&gt; Subscribers (Level&lt;2) will see a profile link. <br/>
								<b>Form</b>: named | no-named
							</td>
						</tr>				
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_1">#1</label>
							</th>
							<td width="90%">
								<input type="text" name="line_1" id="line_1" value="<?php echo $options['line_1']; ?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_2">#2</label>
							</th>
							<td width="90%">
								<input type="text" name="line_2" id="line_2" value="<?php echo $options['line_2']; ?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_3">#3</label>
							</th>
							<td width="90%">
								<input type="text" name="line_3" id="line_3" value="<?php echo $options['line_3']; ?>"/>
							</td>
						</tr>
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_4">#4</label>
							</th>
							<td width="90%">
								<input type="text" name="line_4" id="line_4" value="<?php echo $options['line_4']; ?>"/>
							</td>
						</tr>			
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_5">#5</label>
							</th>
							<td width="90%">
								<input type="text" name="line_5" id="line_5" value="<?php echo $options['line_5']; ?>"/>
							</td>
						</tr>	
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_6">#6</label>
							</th>
							<td width="90%">
								<input type="text" name="line_6" id="line_6" value="<?php echo $options['line_6']; ?>"/>
							</td>
						</tr>	
						<tr valign="top">
							<th scrope="row" width="10%">
								<label for="line_7">#7</label>
							</th>
							<td width="90%">
								<input type="text" name="line_7" id="line_7" value="<?php echo $options['line_7']; ?>"/>
							</td>
						</tr>							
					</table>
					<p class="submit"><input type="submit" name="submit" value="Update Settings &raquo;" /></p>
				</form>
			</div>
<?php	}	
	}


	
	add_action('activate_'.plugin_basename(__FILE__), array('am_hoodame','aktivate_plugin'));
	add_action('deactivate_'.plugin_basename(__FILE__), array('am_hoodame','deaktivate_plugin'));
	add_action('admin_menu', array('am_hoodame','add_config_page'));
	
	add_action ('widgets_init', array('am_hoodame','init'));

?>
