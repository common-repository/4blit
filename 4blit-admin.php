<?php
/* Only if ADMIN */
if(!is_admin()) {
    wp_die();
}

/* ============================================================ */
/* 								*/
/* POST 							*/
/* 								*/
/* =============================================================*/

add_action( 'admin_post_register', 'wp_4blit_action_register' );

function wp_4blit_action_register() {
    $headers = array('Accept' => 'application/json');

    // Verify if the nonce is valid
    if ( !isset($_POST['_mynonce']) || !wp_verify_nonce($_POST['_mynonce'], 'register-user')) {
	echo "NONCE VERIFICATION FAILED";
    } else {
	$option_api_key = get_option( 'wp_4blit_api_key' );

	if(!isset($option_api_key)) {
	    $option_api_key = ''; /* No API key */
	}

	$blog_name = (sanitize_text_field($_POST["wp_4blit_blog_name"]) ? sanitize_text_field($_POST["wp_4blit_blog_name"]):get_bloginfo('name'));
	$blog_description = (sanitize_text_field($_POST["wp_4blit_blog_description"]) ? sanitize_text_field($_POST["wp_4blit_blog_description"]):get_bloginfo('description'));
	$blog_admin_email = (sanitize_email($_POST["wp_4blit_admin_email"]) ? sanitize_email($_POST["wp_4blit_admin_email"]):get_bloginfo('admin_email'));
	$blog_language = sanitize_text_field(get_bloginfo('language'));
	$blog_url = (sanitize_text_field($_POST["wp_4blit_blog_url"]) ? sanitize_text_field($_POST["wp_4blit_blog_url"]):get_bloginfo('url'));

	$data = array('key' => $option_api_key,
	    'blog_name' => $blog_name,
	    'blog_description' => $blog_description,
	    'blog_admin_email' => $blog_admin_email,
	    'blog_language' => $blog_language,
	    'blog_url' => $blog_url,
	);

	$body = Unirest\Request\Body::multipart($data);

	$result = Unirest\Request::post('https://www.4bl.it/rest/register', $headers, $body);

	write_log($result);

	if($result->code == '200') {
	    if(isset($result->body->apikey)) {
		$api_key = $result->body->apikey;

		update_option('wp_4blit_api_key', $api_key);

		$result = "success";
	    } else {
		$result = "fail";
    	    }
	} else {
	    $result = "fail";
	}

	wp_redirect(admin_url("admin.php?page=wp_4blit_options&result=$result"));
	exit;
    }
    wp_die();
}

/* Already registered ? Just signin and update BLOG data ! */
add_action( 'admin_post_signin', 'wp_4blit_action_signin' );

function wp_4blit_action_signin() {
    $headers = array('Accept' => 'application/json');

    // Verify if the nonce is valid
    if ( !isset($_POST['_mynonce']) || !wp_verify_nonce($_POST['_mynonce'], 'register-user')) {
	echo "NONCE VERIFICATION FAILED";
    } else {
	$blog_api_key = sanitize_text_field($_POST["wp_4blit_api_key"]);

	$blog_name = get_bloginfo('name');
	$blog_description = get_bloginfo('description');
	$blog_admin_email = get_bloginfo('admin_email');
	$blog_language = get_bloginfo('language');
	$blog_url = get_bloginfo('url');

	$data = array('key' => $blog_api_key,
	    'blog_name' => $blog_name,
	    'blog_description' => $blog_description,
	    'blog_admin_email' => $blog_admin_email,
	    'blog_language' => $blog_language,
	    'blog_url' => $blog_url,
	);

	$body = Unirest\Request\Body::multipart($data);

	$result = Unirest\Request::post('https://www.4bl.it/rest/register', $headers, $body);

	write_log($result);

	if($result->code == '200') {
	    if(isset($result->body->apikey)) {
		$api_key = $result->body->apikey;

		update_option('wp_4blit_api_key', $api_key);

		$result = "success";
	    } else {
		$result = "fail";
    	    }
	} else {
	    $result = "fail";
	}

	wp_redirect(admin_url("admin.php?page=wp_4blit_options&result=$result"));
	exit;
    }
    wp_die();
}

add_action( 'admin_post_update', 'wp_4blit_action_update' );

function wp_4blit_action_update() {
    if ( !isset($_POST['_mynonce']) || !wp_verify_nonce($_POST['_mynonce'], 'update')) {
	echo "NONCE VERIFICATION FAILED";
    } else {
	if(isset($_POST["wp_4blit_default_publish"])) {
	    update_option( 'wp_4blit_default_publish',true);
	} else {
	    update_option( 'wp_4blit_default_publish',false);
        }

	$api_key = sanitize_text_field($_POST["wp_4blit_api_key"]);

	update_option('wp_4blit_api_key',$api_key);

	$result = 'updated';

	wp_redirect(admin_url("admin.php?page=wp_4blit_options&result=$result"));
	exit;
    }
}

function wp_4blit_enqueue() {
    global $wp_styles;

    wp_register_script( 'validation-locale', plugins_url( '/js/jquery.validationEngine-it.js', __FILE__ ));
    wp_register_script( 'validation-engine', plugins_url( '/js/jquery.validationEngine.js', __FILE__ ));
    wp_register_script( 'custom-js', plugins_url( '/js/custom.js', __FILE__ ));

    wp_localize_script( 'ajax-script', 'ajax_object', array(
	'ajax_url' => admin_url( 'admin-ajax.php' ),
    ));
     
    wp_register_style( 'validation-css', plugins_url( '/css/validationEngine.jquery.css', __FILE__ ));
    wp_register_style( 'custom-css', plugins_url( '/css/wp-4blit.css', __FILE__ ));

    wp_enqueue_script('validation-locale');
    wp_enqueue_script('validation-engine');
    wp_enqueue_script('custom-js');
    wp_enqueue_style('validation-css');
    wp_enqueue_style('custom-css');
}

add_action('admin_enqueue_scripts', 'wp_4blit_enqueue');

/* ============================================================ */
/* 								*/
/* AJAX 							*/
/* 								*/
/* =============================================================*/

add_action( 'wp_ajax_verify', 'wp_4blit_action_verify' );

function wp_4blit_action_verify() {
    global $wpdb; // this is how you get access to the database

    // Do connection test to REST Server @ 4bl.it/bot/rest
    $option_api_key = get_option('wp_4blit_api_key');

    $headers = array('Accept' => 'application/json');

    $data = array('key' => $option_api_key,
	'blog_name' => get_bloginfo('name'),
	'blog_description' => get_bloginfo('description'),
	'blog_admin_email' => get_bloginfo('admin_email'),
	'blog_language' => get_bloginfo('language'),
	'blog_url' => get_bloginfo('url'),
    );

    $body = Unirest\Request\Body::multipart($data);

    $result = Unirest\Request::post('https://www.4bl.it/rest/verify', $headers, $body);

    if($result->code == '200') {
	_e("Connected !",'wp-4blit');
    } else {
	_e("Error: ",'wp-4blit');
	echo $result->code;
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

/* ============================================================ */
/* 								*/
/* ADMIN 							*/
/* 								*/
/* =============================================================*/

function wp_4blit_options_menu() {
    add_submenu_page(
          'options-general.php',          // admin page slug
          __( '4blit Options', 'wp-4blit' ), // page title
          __( '4blit Options', 'wp-4blit' ), // menu title
          'manage_options',               // capability required to see the page
          'wp_4blit_options',                // admin page slug, e.g. options-general.php?page=wporg_options
          'wp_4blit_options_page'            // callback function to display the options page
     );
}
add_action('admin_menu', 'wp_4blit_options_menu');

function wp_4blit_register_settings() {
     register_setting(
          'wp_4blit_options',  // settings section
          'wp_4blit_api_key' // setting name
     );
     register_setting(
          'wp_4blit_options',  // settings section
          'wp_4blit_default_ublish' // setting name
     );

}
add_action( 'admin_init', 'wp_4blit_register_settings' );

function wp_4blit_options_page() {
     if(!isset( $_REQUEST['settings-updated'])) {
          $_REQUEST['settings-updated'] = false; 
    }
    settings_fields( 'wp_4blit_options' );
    $option_api_key = get_option( 'wp_4blit_api_key' );
    $option_default_publish = get_option( 'wp_4blit_default_publish' );
?>
     <div class="wrap"><!-- WRAP -->
          <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
<?php 
    if(empty($option_api_key)) {

	if(isset($_REQUEST['result'])) {
	    if($_REQUEST['result'] == 'fail') {
?>
		<div class='notice notice-error'>
		    <p><?php _e('Ooops ! Something wrong while registering your blog: please try later.', 'wp-4blit' ); ?></p>
		</div>
<?php
	    } else if($_REQUEST['result'] == 'success') {
?>	
		<div class='notice notice-success'>
		    <p><?php _e('Great ! Your BLOG was registered successfully on 4bl.it: now check your mailbox...', 'wp-4blit' ); ?></p>
		</div>
<?php
	    }
	}
?>
	<div>
	    <script>
		jQuery(document).ready(function($){
	    	    jQuery(".validate").validationEngine('attach');
		});
	    </script>
	    <form class="validate" action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
		<?php echo wp_nonce_field('register-user', '_mynonce'); ?>
		<input type="hidden" name="action" value="register">
		<h3><?php _e('Register your blog on 4bl.it', 'wp-4blit' ); ?></h3>
		<p><?php _e('Please check (and fix, if need) the following data, that will be sent to 4bl.it server to register your blog on our platform. Don\'t worry, it\'s FREE and NO PERSONAL DATA will be sent !'); ?></p>
		<table class="form-table">
		    <tr valign="top">
			<td>
			    <?php _e('Blog name','wp-4blit');?>: 
			</td><td>
			    <input type="text" name="wp_4blit_blog_name" id="wp_4blit_blog_name" class="validate[required]" value="<?php echo get_bloginfo('name'); ?>">
			</td>
		    </tr><tr>
			<td>
			    <?php _e('Blog description','wp-4blit');?>: 
			</td><td>
			    <input type="text" name="wp_4blit_blog_description" id="wp_4blit_blog_description" class="validate[required]" value="<?php echo get_bloginfo('description'); ?>">
			</td>
		    </tr><tr>
			<td>
			    <?php _e('Admin e-mail','wp-4blit');?>: 
			</td><td>
			    <input type="text" name="wp_4blit_admin_email" id="wp_4blit_admin_email"  class="validate[required,custom[email]]" value="<?php echo get_bloginfo('admin_email'); ?>">
			</td>
		    </tr><tr>
			<td>
			    <?php _e('Blog URL','wp-4blit');?>: 
			</td><td>
			    <input type="text" name="wp_4blit_blog_url" id="wp_4blit_blog_url" class="validate[required]" value="<?php echo get_bloginfo('url'); ?>">
			</td>
		    </tr>
		</table>
		<p>
		    <?php _e("By clicking Sign Up, you agree to our <a href='http://www.4bl.it/legal'>Terms</a>, including data use policy and cookie use"); ?>
		</p>
		<input type="submit" class="btn btn-large" id="wp_4blit_register" value="<?php _e('Register now', 'wp-4blit'); ?>"> or
	    </form>
	    <hr/>
	    <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
		<?php echo wp_nonce_field('register-user', '_mynonce'); ?>
		<input type="hidden" name="action" value="signin">
		<h3><?php _e('Already registered ?', 'wp-4blit' ); ?></h3>
		<p><?php _e('If your blog is already registered, just type there your 4bl.it API key'); ?></p>
		<table class="form-table">
		    <tr valign="top">
			<td>
			    <?php _e('API key','wp-4blit');?>: 
			</td><td>
			    <input type="text" name="wp_4blit_api_key" id="wp_4blit_api_key" class="validate[required]" value="<?php echo $option_api_key; ?>">
			</td>
		    </tr>
		</table>
		<input type="submit" class="btn btn-large" id="wp_4blit_signin" value="<?php _e('Sign in', 'wp-4blit'); ?>">
	    </form>
	</div>
<?php
    } else {
	if(isset($_REQUEST['result'])) {
	    if($_REQUEST['result'] == 'updated') {
?>
		<div class='notice notice-success'>
		    <p><?php _e('Options updated !', 'wp-4blit' ); ?></p>
		</div>
<?php
	    }
	}
?>
	<div id="poststuff">
	    <div id="post-body">
		<div id="post-body-content">
		    <table class="form-table">
<tr>
			    <td>
			        <button class="btn btn-large" id="wp_4blit_verify"><?php _e('Verify connection', 'wp-4blit'); ?></button> <span id="wp_4blit_verify_result">&nbsp;</span>
			    </td>
			</tr>
		    </table>
		    <form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">
			<?php echo wp_nonce_field('update', '_mynonce'); ?>
		        <input type="hidden" name="action" value="update">
		    	<table class="form-table">
		    	    <tr valign="top">
				<th scope="row"><?php _e('Your 4blit BOT Api Key', 'wp-4blit' ); ?></th>
				<td>
			    	    <input type="text" name="wp_4blit_api_key" id="wp_4blit_api_key" value="<?php echo $option_api_key; ?>">
			    	    <br/>
			    	    <label class="description" for="wp_4blit_api_key"><?php _e('This is the unique API key for your blog. Please, keep it safe and do not change if not needed.'); ?></label>
				</td>
			    </tr><tr>
				<th scope="row"><?php _e('Default is to publish new articles', 'wp-4blit' ); ?></th>
				<td>
				    <input type="checkbox" name="wp_4blit_default_publish" id="wp_4blit_default_publish" <?php echo ($option_default_publish ? 'checked':''); ?> >
				</td>
			    </tr><tr>
				<td>
			    	    <input type="submit" value="<?php _e('Submit'); ?>">
				</td>
			    </tr>
			</table>
		    </form>
		</div> <!-- end post-body-content -->
	    </div> <!-- end post-body -->
	</div> <!-- end poststuff -->
<?php
    }
?>
    </div><!-- /WRAP -->
<?php
}
?>
