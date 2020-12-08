<?php
/*
Plugin Name: SBL Admin Bar
Version: 1.0
Author: Steven B. Lienhard
Author URI: https://lienhard.net/admin-bar
Description: Like the Admin Bar but it's in the way? Turn it on when you need it and off when you don't. Use CONTROL-SHIFT-A to enable/disable the Admin Bar while on the front-end. 
License: License: GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/
define ('SBL_ADMIN_BAR_VERSION', '1.0');
define ('SBL_ADMIN_BAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define ('SBL_ADMIN_BAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define ('SBL_ADMIN_BAR_SETTINGS_LINK', '<a href="'.admin_url().'admin.php?page=sbl-admin-bar">Settings</a>');
define ('SBL_ADMIN_BAR_NONCE', 'xE2^*s3SPznJ32PBHPeUA');
define ('SBL_ADMIN_BAR_NONCE_NAME', 'sblAdminBarSettingsNonce');
define ('SBL_ADMIN_BAR_OPTIONS', 'sblAdminBarOptions');
define ('SBL_ADMIN_BAR_FLAG', 'sblAdminBarFlag');
define ('SBL_ADMIN_BAR_URL', 'https://lienhard.net/admin-bar');
 
class SBL_AdminBar {

	private $opt = null;

    function __construct() {

		$this->opt = get_option(SBL_ADMIN_BAR_OPTIONS);

		$ga_plugin = plugin_basename( __FILE__ );

		add_filter( "plugin_action_links_$ga_plugin", function($links){
			$settings_link = SBL_ADMIN_BAR_SETTINGS_LINK;
			array_unshift( $links, $settings_link );
			return $links;
		} );

		add_action('wp_ajax_sblAdminBar', array($this, 'setAdminBar'));

		add_action('wp', array($this, 'adminBarStuff'));

		add_action('admin_menu', array( $this, 'settings'));

		add_action('personal_options', function(){

			if (!$this->isRoleEnabled()) return;

			?>
			<style>
				#admin_bar_front, .show-admin-bar label {display:none}
				.show-admin-bar td::before {
					content: "Use CONTROL-SHIFT-A to toggle this value (SBL Admin Bar plugin)";
				}
			</style>
			<?php
		});

		add_action ( 'wp_enqueue_scripts', array($this, 'enqueueScript')); 
	}

	function showVar($var, $echo = true) {

		if ($echo) 

			echo '<pre>'.print_r($var, true).'</pre>';

		else

			return '<pre>'.print_r($var, true).'</pre>';
	}

	function settings() {

		// Add the menu item and page
		$page_title = 'SBL Admin Bar';
		$menu_title = 'SBL Admin Bar';
		$capability = 'manage_options';
		$slug = 'sbl-admin-bar';
		$callback = array( $this, 'settingsContent' );
	
		$result = add_options_page( $page_title, $menu_title, $capability, $slug, $callback);

	}

	function isRoleEnabled() {

		$user = wp_get_current_user();

		foreach ($user->roles as $role) {

			if (isset($this->opt['active_roles'][$role])) {

				return $this->opt['active_roles'][$role];

			} else {

				return true; // default to enable admin bar for all roles
			}
		}

	}

	function handleSettings() {

		$post = filter_input_array(INPUT_POST);

		if(
			! isset( $post[SBL_ADMIN_BAR_NONCE_NAME]) ||
			! wp_verify_nonce( $post[SBL_ADMIN_BAR_NONCE_NAME], SBL_ADMIN_BAR_NONCE)
		){ ?>
			<div class="error">
			   <p>Sorry, something went wrong. Please try again.</p>
			</div> <?php
		} else {

			$roles = get_editable_roles();

			foreach($roles as $role => $theRole) {

				if (!empty($post['active_roles'][$role])) {

					$this->opt['active_roles'][$role] = true;

				} else {

					$this->opt['active_roles'][$role] = false;
				}

			}

			update_option(SBL_ADMIN_BAR_OPTIONS , $this->opt);
		}
	}

	function settingsContent() {

		$updated = filter_input(INPUT_POST, 'updated', FILTER_SANITIZE_STRING);

		if( $updated == 'true' ){
				$this->handleSettings();
		} ?>
		<style>
			.form-table td.role {
				width: 30%;
			}
		</style>
		<div class="wrap sbl-admin-bar">
		<h2>SBL Admin Bar Settings</h2>
		<p>User the form below to enable or disable the Admin Bar by user role.</p>
        <form method="POST">
		<input type="hidden" name="updated" value="true" />
		<?php wp_nonce_field(SBL_ADMIN_BAR_NONCE, SBL_ADMIN_BAR_NONCE_NAME); ?>
            <table class="form-table">
                <tbody>
					<th>Role</th>
					<th>Enabled</th>
					<?php
					$roles = get_editable_roles();
					//$this->showVar($roles);
					foreach($roles as $role => $theRole) {
						$name = $theRole['name'];
						if (isset($this->opt['active_roles'][$role])) {
							$isChecked = $this->opt['active_roles'][$role] ? ' checked' : '';
						} else {
							$isChecked = ' checked'; // default
						}
					?>
                    <tr>
						<td class="role"><label for="active_role_<?php echo $role ?>"><?php echo $name ?></label></td>
						<td class="checkbox"><input type="checkbox" id="active_role_<?php echo $role ?>" name="active_roles[<?php echo $role ?>]" value="1"<?php echo $isChecked ?>/></td>
					<?php } ?>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Submit">
            </p>
			<p class="info">While logged in on the front-end, use CONTROL-SHIFT-A to turn the Admin Bar On/Off. A momentary dialog will appear to confirm the on/off setting.  Refresh the page to see result.
			</p>
			<p class="info url">For more information, and to submit bug reports or feature requests, visit <a href="<?php echo SBL_ADMIN_BAR_URL ?>" target="_blank">Admin Bar</a></p>
        </form>
    </div> <?php
	}
	
	function setAdminBar() {
				
		$post = filter_input_array(INPUT_POST);

		$nonce = $post['_ajax_nonce'];

		if ( ! wp_verify_nonce( $nonce, SBL_ADMIN_BAR_NONCE ) ) {
			
			 die( 'Security check' ); 
		} 
		
		$flag = $post['value'];

		$userid = get_current_user_id();
		
		update_user_meta($userid, SBL_ADMIN_BAR_FLAG, $flag);
		
		wp_die('success');
		
	}

	function isFlagSet() {

		$userid = get_current_user_id();
				
		$meta = get_user_meta($userid);
		
		if (isset($meta[SBL_ADMIN_BAR_FLAG])) {

			if (!empty($meta[SBL_ADMIN_BAR_FLAG[0]])) {

				return true;

			} else {

				return false;
			}
		}

		return true; // default state is enabled
	}

	function adminBarStuff() {
		
		if (is_user_logged_in()) {
			
			if ( $this->isRoleEnabled() ) {
				
				add_action('wp_footer', array($this, 'adminBarSpace'));
								
				$userid = get_current_user_id();
				
				$flag = get_user_meta($userid, SBL_ADMIN_BAR_FLAG, true);
				
				if ($flag) {
					
					add_filter('show_admin_bar', '__return_true', 9999999); // make sure last
							
				} else {
					
					add_filter('show_admin_bar', '__return_false', 9999999);
				}
			}
		}
		
	}

	function enqueueScript () {

		$url = admin_url( 'admin-ajax.php' );
		$nonce = wp_create_nonce(SBL_ADMIN_BAR_NONCE);
		$user_id = get_current_user_id();
		$flag = !empty(get_user_meta ($user_id, SBL_ADMIN_BAR_FLAG, true)) ? 1 : 0;

		$vars = array(
			'url' 	=> $url,
			'nonce'	=> $nonce,
			'flag'	=> $flag
		);

		$json = json_encode($vars);
		
		wp_enqueue_script( 'sbl-admin-bar', SBL_ADMIN_BAR_PLUGIN_URL . 'js/sbl-admin-bar.js', array(), false, TRUE );

		wp_add_inline_script( 'sbl-admin-bar', '/* < ![CDATA[ */ var abVarsJson = \'' . $json . '\'; /* ]]> */', 'before' );
	}
	
	function adminBarSpace() {
				
	  echo '<style>#admin_bar_msg{display:none; position:fixed; top: 50%; left: 50%; z-index: 9999; background-color: black; width: 150px; height: 50px; text-align:center; vertical-align:middle; line-height: 50px; color:white; font-weight: bold;}</style>';
	  echo '<div id="admin_bar_msg">Admin Bar On</div>';
				
	}
}

$adminBar = new SBL_AdminBar();