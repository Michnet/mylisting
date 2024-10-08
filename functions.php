<?php

// Enqueue child theme style.css
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'child-style', get_stylesheet_uri() );
    wp_enqueue_script( 'child-script', get_stylesheet_directory_uri() . '/assets/script.js'/*,  ['c27-main'], \MyListing\get_assets_version(), true  */);

    wp_deregister_script( 'mylisting-listing-form' );
    wp_register_script( 'mylisting-listing-form', get_stylesheet_directory_uri() . '/assets/add-listing.js');


    if ( is_rtl() ) {
    	wp_enqueue_style( 'mylisting-rtl', get_template_directory_uri() . '/rtl.css', [], wp_get_theme()->get('Version') );
    }
}, PHP_INT_MAX );

// Happy Coding :)


 function edit_theme_caps() {
  // gets the author role
  $role = get_role( 'subscriber' );

  $role->add_cap( 'list_users' ); 
}
add_action( 'admin_init', 'edit_theme_caps');

//Define Constants

$frontend_link = 'https://lyvecity.com';
$backend_link = 'https://lyvecityclub.com';

function lc_disable_admin_bar() {
  if (current_user_can('administrator') ) {
    // user can view admin bar
    show_admin_bar(true); // this line isn't essentially needed by default...
  } else {
    // hide admin bar
    show_admin_bar(false);
  }
}
add_action('after_setup_theme', 'lc_disable_admin_bar');


// Allow Registration Only from @warrenchandler.com email addresses

function is_valid_email_domain($login, $email, $errors ){
  $valid_email_domains = array("gmail.com","yahoo.com");// allowed domains
  $valid = false; // sets default validation to false
  foreach( $valid_email_domains as $d ){
   $d_length = strlen( $d );
   $current_email_domain = strtolower( substr( $email, -($d_length), $d_length));
  if( $current_email_domain == strtolower($d) ){
   $valid = true;
   break;
  }
  }
  // Return error message for invalid domains
  if( $valid === false ){
 
 $errors->add('domain_whitelist_error',__( '<strong>ERROR</strong>: Registration is only allowed from selected approved domains. If you think you are seeing this in error, please contact the system administrator.' ));
  }
 }
 add_action('register_post', 'is_valid_email_domain',10,3 );


 //Guest redirect
 function my_logged_in_redirect() {
	
	if ( is_user_logged_in() || is_page(array( 'my-account', 'home' )) ){
    return;
  } else{
        wp_redirect( '/my-account');
 }
	
}
//add_action( 'template_redirect', 'my_logged_in_redirect' );

//Image sizes

add_action( 'init', 'custom_theme_setup' );
function custom_theme_setup() {
  //$sizes_arr = ['2048x2048', '1536x1536', 'woocommerce_thumbnail', 'shop_catalog', 'woocommerce_gallery_thumbnail', 'woocommerce_single' ];
  /* foreach($sizes_arr as $size){
  remove_image_size( $size );
  } */

  $sizes_arr = array( 'thumbnail', 'medium', 'medium_large', 'large', 'big_thumb' );
  foreach ( get_intermediate_image_sizes() as $size ) {
    if ( !in_array( $size, $sizes_arr ) ) {
        remove_image_size( $size );
    }
}
  add_image_size( 'big_thumb', 400, 400, false );
}

/* 
add_filter( 'rest_request_before_callbacks', 
function($response, $handler, $request){
  $params = $request->get_params();
  $soc_token = $params['soc_token'] ?? null;
  $soc_id = $params['soc_int'] ?? null;
  if(isset($soc_token)){
    $socl = new \NSL\REST();
    $internal_id = $socl->get_user($request);
    if(intval($internal_id) == $soc_id){
      return $response;
    }else{
      $error_res = array();
      $error_res['message'] = 'Need to log in';
      return $error_res;
    }
  }else{
    return $response;
  }

}, 10, 3
); */


//Rest init
add_action( 'rest_api_init', 'addOrderbySupportRest' );

function addOrderbySupportRest(){

	$post_type ="job_listing";
	// Add meta your meta field to the allowed values of the REST API orderby parameter
	add_filter(
		'rest_' . $post_type . '_collection_params',
		function( $params ) {
			$fields = ["level"];
			foreach ($fields as $key => $value) {
				$params['orderby']['enum'][] = $value;
			}
			return $params;
		},
		10,
		1
	);
}

//Limit relationship posts to author
add_filter('acf/fields/relationship/query', 'my_acf_fields_relationship_query', 10, 3);
function my_acf_fields_relationship_query( $args, $field, $post_id ) {
   
    $args['author'] = get_current_user_id();

    return $args;
}

use SimpleJWTLogin\Modules\WordPressDataInterface;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;


function get_social_user_rest($request) {
  //$params = $request->get_params();
  $jwt_auth_space = new \SimpleJWTLogin\Services\AuthenticateService();
 // $authSettings = new \SimpleJWTLogin\Modules\SimpleJWTLoginSettings();
  $wordPressData = new \SimpleJWTLogin\Modules\WordPressData();
  $jwtSettings = new \SimpleJWTLogin\Modules\SimpleJWTLoginSettings($wordPressData);

  $JWT = new \SimpleJWTLogin\Libraries\JWT\JWT();
  $JwtKeyFactory = new \SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory();

 /**  
     * @param WordPressDataInterface $wordPressData
     * @param SimpleJWTLoginSettings $jwtSettings
     * 
     * @return array
     **/


$providerID = $request['provider'];
$access_token = $request['access_token'];
$authOptions['access_token_data'] = $access_token;

try {
  $userIdBySocial = nslLinkOrRegister($providerID, $authOptions);
} catch (Exception $e) {
  return new WP_Error('error', $e->getMessage());
}

  $response = [];

  if($userIdBySocial){
    $user_id = intval($userIdBySocial);

    $userObj = get_userdata($user_id );
    $status = loginUser($userObj);
    
    $jwt_userObj['username'] = $userObj->user_login;
    $jwt_userObj = (object)$jwt_userObj;
    $jwt = 'No class';
    
    $payload = $jwt_auth_space->generatePayload(
      [],
      $wordPressData,
      $jwtSettings,
      $userObj
    );

    $jwt = $JWT::encode(
      $payload,
      $JwtKeyFactory::getFactory($jwtSettings)->getPrivateKey(),
      $jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()
    );

    $user_meta = [];

    $user_meta['likes'] = get_user_meta( $user_id, 'likes', true ) ?? false;
    $user_meta['following'] = get_user_meta( $user_id, 'following', true ) ?? false;
    $user_meta['reviewed'] = get_user_meta( $user_id, 'reviewed_list', true ) ?? false;

    $wp_req = array();
    $wp_req['id'] = $user_id;
    $wp_req['context'] = 'edit';
    $rest_request = new WP_REST_Request();
    $rest_request->set_query_params($wp_req);
    $local_controller = new WP_REST_Users_Controller();
    $returnable_user = $local_controller->get_item($rest_request);
    $response['user'] = $returnable_user->data;
    $response['user']['status'] = $status;
    $response['jwt'] = $jwt;
    $response['request'] = $jwt_userObj;
    $response['user']['user_meta'] = $user_meta;
  }else{
    $response['user']['status'] = 'unregistered';
  }
  return $response;
}

//log in by jwt

function login_by_jwt(){
	if (!is_user_logged_in() ) {
		
		if(isset($_GET["lc_tok"])){


      function validateDoLogin($jwt_login, $jwtSettings){
        $allowedIPs = $jwtSettings->getLoginSettings()->getAllowedLoginIps();
        if (!empty($allowedIPs) && !$jwt_login->serverHelper->isClientIpInList($allowedIPs)) {
            throw new Exception(
                sprintf(
                    __('This IP[ %s ] is not allowed to auto-login.', 'simple-jwt-login'),
                    $jwt_login->serverHelper->getClientIP()
                ),
                ErrorCodes::ERR_IP_IS_NOT_ALLOWED_TO_LOGIN
            );
        }
      }

      function getUserParameterValueFromPayload($payload, $parameter)
    {
        if (strpos($parameter, '.') !== false) {
            $array = explode('.', $parameter);
            foreach ($array as $value) {
                $payload = (array)$payload;
                if (!isset($payload[$value])) {
                    throw new Exception(
                        sprintf(
                            __('Unable to find user %s property in JWT.( Settings: %s )', 'simple-jwt-login'),
                            $value,
                            $parameter
                        ),
                        ErrorCodes::ERR_UNABLE_TO_FIND_PROPERTY_FOR_USER_IN_JWT
                    );
                }
                $payload = $payload[$value];
            }

            return (string)$payload;
        }

        if (!isset($payload[$parameter])) {
            throw new Exception(
                sprintf(
                    __('Unable to find user %s property in JWT.', 'simple-jwt-login'),
                    $parameter
                ),
                ErrorCodes::ERR_JWT_PARAMETER_FOR_USER_NOT_FOUND
            );
        }

        return $payload[$parameter];
    }
      
			function validateJWTAndGetUserValueFromPayload($parameter, $tok){
        $wordPressData = new \SimpleJWTLogin\Modules\WordPressData();
        $jwtSettings = new \SimpleJWTLogin\Modules\SimpleJWTLoginSettings($wordPressData);
				//$jwt_base  = new \SimpleJWTLogin\Services\BaseService();
        $jwt_login = new \SimpleJWTLogin\Services\LoginService();
				$JWT = new \SimpleJWTLogin\Libraries\JWT\JWT();
  			$JwtKeyFactory = new \SimpleJWTLogin\Helpers\Jwt\JwtKeyFactory();


				$JWT::$leeway = $jwt_login::JWT_LEEVAY;
				$decoded = (array)$JWT::decode(
					//$jwt_base->jwt,
					$tok,
					$JwtKeyFactory::getFactory($jwtSettings)->getPublicKey(),
					[$jwtSettings->getGeneralSettings()->getJWTDecryptAlgorithm()]
				);
		
				//return $jwt_login->getUserParameterValueFromPayload($decoded, $parameter);
        return getUserParameterValueFromPayload($decoded, $parameter);
			}

      function getUserDetails($userData, $jwtSettings, $wordPressData){
        $login_settings = new \SimpleJWTLogin\Modules\Settings\LoginSettings();
        
        switch ($jwtSettings->getLoginSettings()->getJWTLoginBy()) {
            case $login_settings::JWT_LOGIN_BY_EMAIL:
                $user = $wordPressData->getUserDetailsByEmail($userData);
                break;
            case $login_settings::JWT_LOGIN_BY_USER_LOGIN:
                $user = $wordPressData->getUserByUserLogin($userData);
                break;
            case $login_settings::JWT_LOGIN_BY_WORDPRESS_USER_ID:
            default:
                $user = $wordPressData->getUserDetailsById((int)$userData);
                break;
        }

        if ($wordPressData->isInstanceOfuser($user) === false) {
            return null;
        }

        return $user;
    }

    function validateJwtRevoked($userId, $jwt, $wordPressData, $jwtSettings)
    {
        $revokedTokensArray = $wordPressData->getUserMeta(
            $userId,
            $jwtSettings::REVOKE_TOKEN_KEY
        );

        if (empty($revokedTokensArray)) {
            return true;
        }
        foreach ($revokedTokensArray as $token) {
            if ($token === $jwt) {
                throw new Exception(__('This JWT is invalid.', 'simple-jwt-login'), ErrorCodes::ERR_REVOKED_TOKEN);
            }
        }

        return true;
    }
		function make_login($tok){

				//$jwt_login = new \SimpleJWTLogin\Services\LoginService();
        $wordPressData = new \SimpleJWTLogin\Modules\WordPressData();
        $jwtSettings = new \SimpleJWTLogin\Modules\SimpleJWTLoginSettings($wordPressData);

				//$jwt_login->validateDoLogin();
        //validateDoLogin($jwt_login, $jwtSettings);

				$loginParameter = validateJWTAndGetUserValueFromPayload(
					$jwtSettings->getLoginSettings()->getJwtLoginByParameter(),
					$tok
				);
        //$user = $jwt_login->getUserDetails($loginParameter);
				$user = getUserDetails($loginParameter, $jwtSettings, $wordPressData);
				if ($user === null) {
					throw new Exception(
						__('User not found.', 'simple-jwt-login'),
						ErrorCodes::ERR_DO_LOGIN_USER_NOT_FOUND
					);
				}

				validateJwtRevoked(
          //$jwt_login->jwt
					$wordPressData->getUserProperty($user, 'ID'),
          $tok,
          $wordPressData, 
          $jwtSettings
				);
        if (!is_user_logged_in() && $wordPressData->getUserProperty($user, 'ID') > 0) {
				$wordPressData->loginUser($user);
        }else{
          return false;
        }
			}

			$token = trim($_GET["lc_tok"]);
			make_login($token);
		}
	}
}

add_action('wp_loaded','login_by_jwt');
  

//Edit listing fields
function add_listing_fields($post_id) {

/*   if ( 'job_listing' !== $post->post_type ) {
		return;
	} */
  if ( get_post_type( $post_id ) !== 'job_listing' ){
    return;
  }
  // If this is a revision, get real post ID
  if ( $parent_id = wp_is_post_revision( $post_id ) ) 
      $post_id = $parent_id;
      
   $excer = get_post_meta($post_id, '_short-description', true);
   $featImg = get_post_meta($post_id, '_featured-image', true)[0];
   $existing_grp = get_post_meta($post_id, 'community_id', true) ?? false;
   $initialCoverImg = get_post_meta($post_id, '_job_cover', true);
   $coverImg = is_array($initialCoverImg) ? $initialCoverImg[0] : $initialCoverImg;
   $imgs = get_post_meta($post_id, '_job_gallery', true) ?? false;
   //$imgs = is_array($initialImgs) ? $initialImgs[0] : $initialImgs;
   $imgs_arr = array();

   if(is_array($imgs)){
      foreach ($imgs as $img) {
        $imgs_arr[] = is_string($img) && str_contains($img, 'http')  ? $img : wp_get_attachment_image_url($img, 'full');
      }
   }

   $imId = attachment_url_to_postid( $featImg);

   $post_ob = get_post($post_id);

    $my_args = array( 
      'ID' => $post_id,
      'post_excerpt' => $excer,
    );

    $g_args = array(
      'group_id'     => $existing_grp ?? 0,
      'creator_id'   => $post_ob->post_author,
      'name'         => get_the_title($post_id),
      'description'  => '',
      'slug'         => $post_ob->post_name,
      'status'       => 'public',
      'enable_forum' => 0,
      'date_created' => bp_core_current_time()
    );

    $group_id = groups_create_group($g_args);

    $listing = \MyListing\Src\Listing::get( $post_id );
  
    remove_action('save_post', 'add_listing_fields');
      set_post_thumbnail( $post_id, $imId );
      wp_update_post( $my_args );
      update_post_meta( $post_id, 'listing_logo', $listing->get_logo());
      update_post_meta( $post_id, 'listing_cover', is_string($coverImg) && str_contains($coverImg, 'http') ? $coverImg : wp_get_attachment_image_url($coverImg, 'full'));
      update_post_meta( $post_id, 'listing_gallery', $imgs_arr);
      if(!$existing_grp){
        if($group_id){
          add_post_meta( $post_id, 'community_id', $group_id);
          groups_add_groupmeta($group_id, 'linked_post', $post_id);
        }
      }

      acf_after_save_post($post_id);

      add_action('save_post', 'add_listing_fields');
}

add_action( 'save_post', 'add_listing_fields');

//Product fields
function add_product_fields($post_id) {
  // If this is a revision, get real post ID
  if ( $parent_id = wp_is_post_revision( $post_id ) ) 
      $post_id = $parent_id;

      $meta_arr = array(); 

      $listingId = intval(get_post_meta($post_id, 'listing', true)[0]);
      $listing_meta = get_post_meta($listingId);
  
      $meta_arr['slug'] = get_post_field( 'post_name', intval($listingId) );
      $meta_arr['phone'] = $listing_meta['_job_phone'][0] ?? null;
      $meta_arr['cover'] = $listing_meta['listing_cover'] ?? null;
      $meta_arr['logo'] = $listing_meta['listing_logo'][0] ?? null;
      $meta_arr['whatsapp'] = $listing_meta['_whatsapp-number'][0] ?? null;
  
    remove_action('save_post_product', 'add_product_fields');
      //wp_update_post( $my_args );
      update_post_meta( $post_id, 'listing_data', $meta_arr);

      add_action('save_post_product', 'add_product_fields');
}
add_action( 'save_post_product', 'add_product_fields', 11, 1 );

//Simple jwt hooks

add_action('simple_jwt_login_no_redirect_message', function($response, $request){
 if($request['name']){
   $user_name = $request['name'];
   $user_obj = get_user_by('login', $user_name);
   /* $user_meta = [];
   $user_meta['likes'] = get_user_meta( $user_obj-> ID, 'likes', true ) ?? false;
   $user_meta['following'] = get_user_meta( $user_obj-> ID, 'following', true ) ?? false;
   $user_meta['reviewed'] = get_user_meta( $user_obj-> ID, 'reviewed_list', true ) ?? false; */

   //$avatar = get_avatar_url($user_obj->ID);
   //$user_obj->avatar = $avatar;

   /* $request['id'] = $user_obj->ID;
   $request['context'] = 'edit';
   $rest_request = new WP_REST_Request();
    $rest_request->set_query_params($request);
   $local_controller = new WP_REST_Users_Controller();
   $returnable_user = $local_controller->get_item($rest_request);
   $response['user'] = $returnable_user->data; */

   $response['user']['id'] = $user_obj->ID;
   $response['user']['name'] = $user_obj->display_name;
   

   //$response['user']['user_meta'] = $user_meta;
 }
 return $response;
}, 10, 2);


add_action('simple_jwt_login_response_auth_user', function($response, $user){
 if($user){
  $user_id = $user->ID;


  $user_obj = get_user_by('id', $user_id);
  $inner_req = array();

    //$avatar = get_avatar_url($user_obj->ID);
    //$user_obj->avatar = $avatar;
    $inner_req['id'] = $user_obj->ID;
    $inner_req['context'] = 'edit';
    $rest_request = new WP_REST_Request();
    $rest_request->set_query_params($inner_req);
    $local_controller = new WP_REST_Users_Controller();
    $returnable_user = $local_controller->get_item($rest_request);
    $resp_user = $returnable_user->data;


    foreach($resp_user as $p_key => $p_val){
      if(!in_array($p_key, array('avatar_urls', 'description', 'name', 'registered_date', 'yoast_head_json', 'email', 'id'))){
        unset($resp_user[$p_key]);
      }
    }
    
    //if($params['with_meta'] == true){
      $user_meta = [];
      if(function_exists('groups_get_user_groups')){
        $user_meta['communities'] = groups_get_user_groups($user_obj-> ID);
      }
      $user_meta['likes'] = get_user_meta( $user_obj-> ID, 'likes', true ) ?? false;
      $user_meta['following'] = get_user_meta( $user_obj-> ID, 'following', true ) ?? false;
      $user_meta['reviewed'] = get_user_meta( $user_obj-> ID, 'reviewed_list', true ) ?? false;
      $resp_user['user_meta'] = $user_meta; 
    //}


  $response['auth_user'] = $resp_user;
 }
 return $response;
}, 10, 2);


add_action('simple_jwt_login_generate_payload', function(array $payload, $user){
  if($user){
    $payload['picture'] = get_avatar_url( $user->ID);
    $payload['name'] = $user->display_name;
  }
  return $payload;
 }, 10, 2);


/* 
function after_social_login($user_id, $provider) {
  $user = get_userdata($user_id);

   $user_meta = [];
   $response = [];

   $user_meta['likes'] = get_user_meta( $user_id, 'likes', true ) ?? [];
   $user_meta['following'] = get_user_meta( $user_id, 'following', true ) ?? [];
  $response['user'] = $user;
  $response['user']['user_meta'] = $user_meta;
  return $response;
}
add_action('nsl_login', 'after_social_login', 10, 2); */



//mylisting snippets
add_filter( 'acf/settings/show_admin', '__return_true', 50 );

add_filter( 'mylisting\packages\free\skip-checkout', '__return_true' );


add_filter( 'register_post_type_args', function( $args, $post_type ) {

  if ( 'job_listing' === $post_type ) {
    
    $args['show_in_rest'] = true;
    //$args['rest_base']             = 'listings';
   //$args['rest_controller_class'] = 'WP_REST_Posts_Controller';
  }

  return $args;

}, 10, 2 );


//WpGraphql categories

add_filter( 'register_taxonomy_args', function( $args, $taxonomy ) {

  if ( 'job_listing_category' === $taxonomy ) {
    
    $args['show_in_rest'] = true;

    $args['rest_base']             = 'dir_categories';
    $args['rest_controller_class'] = 'WP_REST_Terms_Controller';

  }

  return $args;

}, 10, 2 );

//Api Tags

add_filter( 'register_taxonomy_args', function( $args, $taxonomy ) {

  if ( 'case27_job_listing_tags' === $taxonomy ) {
    $args['show_in_rest'] = true;

    $args['rest_base']             = 'dir_tags';
    $args['rest_controller_class'] = 'WP_REST_Terms_Controller';
  
  }

  return $args;

}, 10, 2 );

//WpGraphQl Locations

add_filter( 'register_taxonomy_args', function( $args, $taxonomy ) {

  if ( 'region' === $taxonomy ) {
    $args['show_in_rest'] = true;
    $args['rest_base']             = 'dir_locations';
    $args['rest_controller_class'] = 'WP_REST_Terms_Controller';
  }

  return $args;

}, 10, 2 );

//Payment methods
add_filter( 'register_taxonomy_args', function( $args, $taxonomy ) {

  if ( 'payment-method' === $taxonomy ) {
    $args['show_in_rest'] = true;
    $args['rest_base']             = 'payment_methods';
    $args['rest_controller_class'] = 'WP_REST_Terms_Controller';
  }

  return $args;

}, 10, 2 );

//ACF image field 
/* 
add_filter('acf/update_value/key=field_66b0fba20063b', function ($value, $post_id, $field, $original){
  if( is_int($value)) {
      $value =  wp_get_attachment_image_url($value, 'full');
  }
  return $value;

}, 11, 4); */

//ACF Rest image
add_filter('acf/rest/format_value_for_rest/type=image', function ($value_formatted, $post_id, $field, $value, $format){
   $obj = new stdClass();
           $obj->ID = $value_formatted['ID'];
  			$obj->sizes = $value_formatted['sizes'];	

  
  return $obj;

}, 10, 5);

////field name = sm_image

add_filter('acf/rest/format_value_for_rest/key=field_5feee4b968b63', function ($value_formatted, $post_id, $field, $value, $format){
   $obj = new stdClass();
           $obj->ID = $value_formatted->ID;
  			$obj->sizes = $value_formatted->sizes;	

  
  return $obj;

}, 10, 5);


add_filter('acf/rest/format_value_for_rest/name=cover_photo', function ($value_formatted, $post_id, $field, $value, $format){
         
  $cover_obj = new stdClass();
  
            $cover_obj->ID = $value_formatted->ID;
  			$cover_obj->sizes = $value_formatted->sizes;	

  
  return $cover_obj;

}, 10, 5);

//Directory API


//custom REST 
function directory_query($request) {
  
    // Extend custom query arguments
    $params = $request->get_params();
    $order = $params['order'];
    $orderby = $params['order_by'];
    $meta_key = $params['meta_key'];
    $tag  = $params['tag'];
    $filters  = $params['filter'];
    $page  = $params['page'];
    $per_page  = $params['per_page'];
    $keyword  = $params['keyword'];
    $randomize = $params['random'];
   
    
  $custom_args = array(
        'post_type'  => array('job_listing'),
        'paged'		=> $page,
        'posts_per_page' => $per_page,
        //'orderby' => $orderby,
        'meta_key' => $meta_key,
        //'order'   => $order,
        's'     =>  $keyword ? $keyword : ''
      );
  
 // If filter by category or attributes.
    //if ( ! empty( $category ) || ! empty($filters) ) {
      $custom_args['tax_query']['relation'] = 'AND';

      // Category filter.
      if (!empty($params['category'] ) ) {
        $category  = $params['category'];	

        $custom_args['tax_query'][] = [
          'taxonomy' => 'job_listing_category',
          'field'    => 'term_id',
          'terms'    => [ $category ],
        ];
      }
      
      // Location filter.
      if ( ! empty($params['location'] ) ) {
        $location  = $params['location'];
        $custom_args['tax_query'][] = [
          'taxonomy' => 'region',
          'field'    => 'term_id',
          'terms'    => [ $location ],
        ];
      }
      
        // Tag filter.
      if ( ! empty($tag ) ) {
        
        $custom_args['tax_query'][] = [
          'taxonomy' => 'case27_job_listing_tags',
          'field'    => 'term_id',
          'terms'    => [ $tag ],
        ];
      }

      //Keyword search
      if ( ! empty( $keyword ) ) {
          $custom_args['s'] = $keyword;
      }
      
      if ( ! empty( $orderby ) ) {
          $custom_args['orderby'] = $orderby;
          $custom_args['order'] = $order;
      }elseif (!empty($randomize)) {
        $custom_args['orderby'] = 'rand';
      }
    
 	
	$query = new WP_Query($custom_args);
    $listings = $query->get_posts();
    if (empty($listings)) {
    return new WP_Error( 'no_listing', 'No listings matching your filters', array('status' => 200) );

    }

  	$directoryList = array();
  
  	foreach($listings as $listing){
        $list_post = get_post( $listing-> ID );
      $directoryList[] = $list_post;
      
    }

    $response = new WP_REST_Response($directoryList);
    $response->set_status(200);
  
  	$total = $query->found_posts; 	
    $pages = $query->max_num_pages;   	 	
    $response->header( 'X-WP-Total', $total ); 	
    $response->header( 'X-WP-TotalPages', $pages );  	

    return $response;
}

//Allitem ids by post type

function all_items_ids($request) {
  
	$params = $request->get_params();
    $type  = $params['type'];
    $pdt_type  = $params['product_type'];
    $l_type  = $params['listing_type'] ?? null;
    $slugs  = $params['slugs'] ?? null;

    $args = array(
        'post_type'  => $type,
        'posts_per_page' => -1,
        'status'    => 'publish'
    );

    if($pdt_type){
     $args['tax_query'][] = array(
        array(
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => $pdt_type, 
        ),
      );
    }

    if($l_type){

        $args['meta_query'][] = array(
            'key' => '_case27_listing_type',
            'value' => $l_type,
            'compare' => '=',
        );
    }
 	
	$query = new WP_Query($args);
    $posts = $query->get_posts();
    if (empty($posts)) {
    return new WP_Error( 'no_items', 'No item matching your filters', array('status' => 200) );

    }
    
  	$postList = array();
  
  	foreach($posts as $post){
      $postList[] = $slugs ? $post -> post_name : $post-> ID;
    }

    $response = new WP_REST_Response($postList);
    $response->set_status(200);
  
  	$total = $query->found_posts; 	
    $pages = $query->max_num_pages;   	 	
    $response->header( 'X-WP-Total', $total ); 	
    $response->header( 'X-WP-TotalPages', $pages );  	

    return $response;
}

function get_google_calendar_link($listing, $start_date, $end_date = '') {
    // &dates=20170101T180000Z/20170101T190000Z
    $template = 'https://calendar.google.com/calendar/render?action=TEMPLATE&';
    $template .= 'text={title}&dates={dates}&details={description}&location={location}&trp=true&ctz={timezone}';

    // generate a description
    if ( $tagline = $listing->get_field( 'tagline' ) ) {
        $description = wp_kses( $tagline, [] );
    } else {
        $description = wp_kses( $listing->get_field( 'description' ), [] );
        $description = mb_strimwidth( $description, 0, 150, '...' );
    }

    if ( ! empty( $description ) ) {
        $description .= ' ';
    }
    
    $list_type = get_post_meta( $listing->get_id(), '_case27_listing_type', true);
    $list_slug = get_post_field('post_name', $listing->get_id());
    // append listing link to the description
    $description .= 'https://lyvecity.com/'.$list_type.'/'.$list_slug;

    // generate date string
    $dates = date( 'Ymd\THis', strtotime( $start_date ) );
    if ( ! empty( $end_date ) ) {
        $dates .= date( '/Ymd\THis', strtotime( $end_date ) );
    } else {
        // if no end date, just duplicate the start date as the link
        // doesn't work with just a start date
        $dates .= date( '/Ymd\THis', strtotime( $start_date ) );
    }

    $location = $listing->get_field('location', true)
        ? $listing->get_field('location', true)->string_value('address')
        : null;

    $values = [
        '{title}' => $listing->get_title(),
        '{description}' => $description,
        '{location}' => $location,
        '{dates}' => $dates,
        '{timezone}' => c27()->get_timezone_string(),
    ];

    return str_replace( array_keys( $values ), array_values( $values ), $template );
}

function get_event_dates($request){
    $params = $request->get_params();
    $event_id  = $params['event_id'];
    $field_key  = $params['f_key'];
    $upcoming_inst = $params['upcoming_instances'] ?? null;
    $past_inst = $params['past_instances'] ?? null;
    $listing = \MyListing\Src\Listing::get( $event_id);

    $dates = [];
    $now = date_create('now');
    $field = $listing->get_field_object($field_key);
    if ( ! $field ) {
        return $dates;
    }

    $list_type = get_post_meta( $listing->get_id(), '_case27_listing_type', true);
    $list_desc = get_post_meta( $listing->get_id(), '_short-description', true);
    $list_slug = get_post_field('post_name', $listing->get_id());
    $list_location = get_post_field('_job_location', $listing->get_id());
    $l_title = $listing->get_name();
    $l_tz = c27()->get_timezone_string();
    $l_link = 'https://lyvecity.com/'.$list_type.'/'.$list_slug;

    if ( $field->get_type() === 'date' ) {
        $date = $field->get_value();
        if ( ! empty( $date ) && strtotime( $date ) ) {
            $dates[] = [
                'start' => $date,
                'end' => '',
                'title' => $l_title,
                'time_zone' => $l_tz,
                'location' => $list_location,
                'desc' => $list_desc,
                'url' => $l_link,
                'gcal_link' => get_google_calendar_link($listing, $date, '' ),
                'is_over' => $now->getTimestamp() > strtotime( $date, $now->getTimestamp() ),
            ];
        }
    }

    if ( $field->get_type() === 'recurring-date' ) {
        $dates = array_merge(
            \MyListing\Src\Recurring_Dates\get_previous_instances( $field->get_value(), $past_inst ?? 0 ),
            \MyListing\Src\Recurring_Dates\get_upcoming_instances( $field->get_value(), $upcoming_inst ?? 3 )
        );

        foreach ( $dates as $key => $date ) {
            $dates[$key]['title'] = $l_title;
            $dates[$key]['time_zone'] = $l_tz;
            $dates[$key]['location'] = $list_location;
            $dates[$key]['desc'] = $list_desc;
            $dates[$key]['url'] = $l_link;
            $dates[$key]['gcal_link'] = get_google_calendar_link($listing, $date['start'], $date['end']);
            $dates[$key]['is_over'] = $now->getTimestamp() > strtotime( $date['end'], $now->getTimestamp() );
        }
    }

    return $dates;
}


 //$customName = "m-api/v1";
add_action('rest_api_init', function () {
    
    register_rest_route('m-api/v1', 'places(?:/(?P<id>\d+))?',array(
        'methods'  => 'GET',
        'callback' => 'directory_query',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('m-api/v1', 'event-dates(?:/(?P<id>\d+))?',array(
    'methods'  => 'GET',
    'callback' => 'get_event_dates',
    'permission_callback' => '__return_true',
    ));

    register_rest_route('m-api/v1', 'create_booking(?:/(?P<id>\d+))?',array(
      'methods'  => 'POST',
      'callback' => 'create_slot_booking',
      'permission_callback' => '__return_true',
      ));

    register_rest_route('m-api/v1', 'check_slot(?:/(?P<id>\d+))?',array(
      'methods'  => 'GET',
      'callback' => 'check_slot_availability',
      'permission_callback' => '__return_true',
      ));

    register_rest_route( 'user-actions/v1', 'user-picks(?:/(?P<id>\d+))?', array(
        'methods' => 'POST',
        'callback' => 'listing_user_actions',
        'permission_callback' => '__return_true',
    ));

    register_rest_route( 'user-actions/v1', 'edit-user(?:/(?P<id>\d+))?', array(
      'methods' => 'POST',
      'callback' => 'update_user_info',
      'permission_callback' => '__return_true',
  ));

  register_rest_route( 'user-actions/v1', 'logout(?:/(?P<id>\d+))?', array(
    'methods' => 'POST',
    'callback' => 'logout_user_rest',
    'permission_callback' => '__return_true',
));

    register_rest_route('m-api/v1', 'ids(?:/(?P<id>\d+))?',array(
      'methods'  => 'GET',
      'callback' => 'all_items_ids',
      'permission_callback' => '__return_true',
  ));

  register_rest_route('m-api/v1', 'delete-review(?:/(?P<id>\d+))?',array(
    'methods'  => 'POST',
    'callback' => 'rest_delete_review',
    'permission_callback' => '__return_true',
    ));

  register_rest_route('m-api/v1', 'submit-review(?:/(?P<id>\d+))?',array(
    'methods'  => 'POST',
    'callback' => 'rest_submit_reviews',
    'permission_callback' => '__return_true',
    ));

    register_rest_route('m-api/v1', 'visit(?:/(?P<id>\d+))?',array(
      'methods'  => 'POST',
      'callback' => 'record_visit',
      'permission_callback' => '__return_true',
      ));

    register_rest_route('m-api/v1', 'views(?:/(?P<id>\d+))?',array(
        'methods'  => 'GET',
        'callback' => 'get_views',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('m-api/v1', 'newsletter(?:/(?P<id>\d+))?',array(
      'methods'  => 'POST',
      'callback' => 'news_subscribe',
      'permission_callback' => '__return_true',
  ));

    register_rest_route('m-api/v1', 'activity(?:/(?P<id>\d+))?',array(
      'methods'  => 'GET',
      'callback' => 'public_activity',
      'permission_callback' => '__return_true',
  ));

    register_rest_route('m-api/v1', 'listings(?:/(?P<id>\d+))?',array(
        'methods'  => 'GET',
        'callback' => 'get_listings_query',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('m-api/v1', 'user-auth(?:/(?P<id>\d+))?',array(
      'methods'  => 'POST',
      'callback' => 'rest_user_auth',
      'permission_callback' => '__return_true',
  ));

  register_rest_route('m-api/v1', 'get-user(?:/(?P<id>\d+))?',array(
    'methods'  => 'GET',
    'callback' => 'get_user_rest',
    'permission_callback' => '__return_true',
  ));


    register_rest_route('m-api/v1', 'product-attribute-taxonomy(?:/(?P<id>\d+))?',array(
      'methods'  => 'GET',
      'callback' => 'get_pdt_attribute_tax',
      'permission_callback' => '__return_true',
  ));

  register_rest_route(
    'm-api/v1',
    'vendor(?:/(?P<id>\d+))?',
    array(
        'methods' => 'GET',
        'callback' => 'vendor_info',
        'permission_callback' => '__return_true',
    ));

      register_rest_route('m-api/v1', '/(?P<provider>\w[\w\s\-]*)/get_social_user', array(
        'args' => array(
            'provider'     => array(
                'required'          => true,
                'validate_callback' => 'validate_soc_provider'
            ),
            'access_token' => array(
                'required' => true,
            ),
        ),
        array(
            'methods'             => 'POST',
            'callback'            => 'get_social_user_rest',
            'permission_callback' => '__return_true'
        ),
    ));
    
    if(class_exists('Bookings_REST_Booking_Controller')){
      $controller = new Bookings_REST_Booking_Controller();
      $controller->register_routes();
    }
});

//create 4-digit number
function random_digits($length=10) {

  $string = '';
  // You can define your own characters here.
  $characters = "0123456789";

  for ($p = 0; $p < $length; $p++) {
      $string .= $characters[mt_rand(0, strlen($characters)-1)];
  }

  return $string;
}

function rest_user_auth ( WP_REST_Request $request ) {

  $user = wp_signon(
    [
      'user_login' => $request['login'],
      'user_password' => $request['password'],
      'remember' => true,
    ],
    false
  );

  if ( is_wp_error( $user ) ) {
    $msg = $user->get_error_message();
    $status = false;
  }else{
    wp_clear_auth_cookie();
    wp_set_current_user ( $user->ID ); // Set the current user detail
    wp_set_auth_cookie  ( $user->ID ); // Set auth details in cookie
    $msg = "Logged in successfully"; 
    $status = true;
  }
  $user->message = $msg;
  $user->success = $status;
  wp_send_json( $user );
}

function get_user_rest( WP_REST_Request $request ) {
  $response = new stdClass();
  $params = $request->get_params();
  $inner_req = array();

  if($params['key'] && $params['val']){
    $key = $params['key'] ?? null;
    $val = $params['val'] ?? null;

    $user_obj = get_user_by($key, $val);

    //$avatar = get_avatar_url($user_obj->ID);
    //$user_obj->avatar = $avatar;
    $inner_req['id'] = $user_obj->ID;
    $inner_req['context'] = 'edit';
    $rest_request = new WP_REST_Request();
    $rest_request->set_query_params($inner_req);
    $local_controller = new WP_REST_Users_Controller();
    //var_dump($rest_request);
    $returnable_user = $local_controller->get_item($rest_request);
    $user = $returnable_user->data;


    foreach($user as $p_key => $p_val){
      if(!in_array($p_key, array('avatar_urls', 'description', 'name', 'registered_date', 'yoast_head_json', 'email', 'id'))){
        unset($user[$p_key]);
      }
    }
    
    if($params['with_meta'] == true){
      $user_meta = [];
      if(function_exists('groups_get_user_groups')){
        $user_meta['communities'] = groups_get_user_groups($user_obj-> ID);
      }
      $user_meta['likes'] = get_user_meta( $user_obj-> ID, 'likes', true ) ?? false;
      $user_meta['following'] = get_user_meta( $user_obj-> ID, 'following', true ) ?? false;
      $user_meta['reviewed'] = get_user_meta( $user_obj-> ID, 'reviewed_list', true ) ?? false;
      $user['user_meta'] = $user_meta; 
    }

    $response->user = $user;
  }

  wp_send_json($response);
}

function news_subscribe($request){
  $params = $request->get_params();
  $email = $params["email"] ?? null;
  $first_name = $params["first_name"] ?? null;
  $last_name = $params["last_name"] ?? null;

  $data = new stdClass();

  if( function_exists( 'mailster' ) ){
      $args = array(
        "firstname" => $first_name,
        'email' => $email,
      );
      if($last_name){
        $args["lastname"] = $last_name;
      }

      $subscriber_id = mailster('subscribers')->add($args);

      if( ! is_wp_error( $subscriber_id)){
        $data->subscriber_id = $subscriber_id;
        $data->success = true;

        $to_list = mailster('subscribers')->assign_lists($subscriber_id, 1, false);
        if($to_list){
          $data->list_id = 1;
        }

      }else{
        $data->success = false;
        $data->error = $subscriber_id;
      }
    }else{ 
      $data->success = false; 
      $data->reason = 'no_function';
    }

  return $data;
}

//public activity rest api

function public_activity( $request ) {
  $rootClass = new BP_REST_Activity_Endpoint();
  global $bp;

  $args = array(
    'exclude'           => $request['exclude'],
    'in'                => $request['include'],
    'page'              => $request['page'],
    'per_page'          => $request['per_page'],
    'search_terms'      => $request['search'],
    'sort'              => strtoupper( $request['order'] ),
    'order_by'          => $request['orderby'],
    'spam'              => $request['status'],
    'display_comments'  => $request['display_comments'],
    'site_id'           => $request['site_id'],
    'group_id'          => $request['group_id'],
    'scope'             => $request['scope'],
    'privacy'           => ( ! empty( $request['privacy'] ) ? ( is_array( $request['privacy'] ) ? $request['privacy'] : (array) $request['privacy'] ) : '' ),
    'count_total'       => true,
    'fields'            => 'all',
    'show_hidden'       => false,
    'update_meta_cache' => true,
    'filter'            => false,
  );

  if ( empty( $args['display_comments'] ) || 'false' === $args['display_comments'] ) {
    $args['display_comments'] = false;
  }

  if ( empty( $request['exclude'] ) ) {
    $args['exclude'] = false;
  }

  if ( empty( $request['include'] ) ) {
    $args['in'] = false;
  }

  if ( isset( $request['after'] ) ) {
    $args['since'] = $request['after'];
  }

  if ( isset( $request['user_id'] ) ) {
    $args['filter']['user_id'] = $request['user_id'];
    if ( ! empty( $request['user_id'] ) ) {
      $bp->displayed_user->id = (int) $request['user_id'];
    }
  }

  $item_id = 0;
  if ( ! empty( $args['group_id'] ) ) {
    $request['component']         = 'groups';
    $args['filter']['object']     = 'groups';
    $args['filter']['primary_id'] = $args['group_id'];
    $args['privacy']              = array( 'public' );

    $item_id = $args['group_id'];
  } elseif ( ! empty( $request['component'] ) && 'groups' === $request['component'] && ! empty( $request['primary_id'] ) ) {
    $args['privacy'] = array( 'public' );
  }

  if ( ! empty( $args['site_id'] ) ) {
    $args['filter']['object']     = 'blogs';
    $args['filter']['primary_id'] = $args['site_id'];

    $item_id = $args['site_id'];
  }

  if ( empty( $args['group_id'] ) && empty( $args['site_id'] ) ) {
    if ( isset( $request['component'] ) ) {
      $args['filter']['object'] = $request['component'];
    }

    if ( ! empty( $request['primary_id'] ) ) {
      $item_id                      = $request['primary_id'];
      $args['filter']['primary_id'] = $item_id;
    }
  }

  if ( empty( $request['scope'] ) ) {
    $args['scope'] = false;
  }

  if ( isset( $request['type'] ) ) {
    $args['filter']['action'] = $request['type'];
  }

  if ( ! empty( $request['secondary_id'] ) ) {
    $args['filter']['secondary_id'] = $request['secondary_id'];
  }

  if ( ! empty( $args['order_by'] ) && 'include' === $args['order_by'] ) {
    $args['order_by'] = 'in';
  }

  if ( $args['in'] ) {
    $args['count_total'] = false;
  }

  /* if ( $rootClass::show_hidden( $request['component'], $item_id ) ) {
    $args['show_hidden'] = true;
  } */

  $args['scope'] = $rootClass->bp_rest_activity_default_scope(
    $args['scope'],
    ( $request['user_id'] ? $request['user_id'] : 0 ),
    $args['group_id'],
    isset( $request['component'] ) ? $request['component'] : '',
    $request['primary_id']
  );

  if ( empty( $args['scope'] ) ) {
    $args['privacy'] = 'public';
  }

  /**
   * Filter the query arguments for the request.
   *
   * @param array           $args    Key value array of query var to query value.
   * @param WP_REST_Request $request The request sent to the API.
   *
   * @since 0.1.0
   */
  $args = apply_filters( 'bp_rest_activity_get_items_query_args', $args, $request );

  // Actually, query it.
  $activities = bp_activity_get( $args );

  $retval = array();
  foreach ( $activities['activities'] as $activity ) {
    $retval[] = $rootClass->prepare_response_for_collection(
      $rootClass->prepare_item_for_response( $activity, $request )
    );
  }

  $response = rest_ensure_response( $retval );
  $response = bp_rest_response_add_total_headers( $response, $activities['total'], $args['per_page'] );

  /**
   * Fires after a list of activities is fetched via the REST API.
   *
   * @param array            $activities Fetched activities.
   * @param WP_REST_Response $response   The response data.
   * @param WP_REST_Request  $request    The request sent to the API.
   *
   * @since 0.1.0
   */
  do_action( 'bp_rest_activity_get_items', $activities, $response, $request );

  return $response;
}

//nsl handler
function nslLinkOrRegister($providerID, $authOptions) {
  $provider = NextendSocialLogin::getProviderByProviderID($providerID);
  if ($provider) {
      $social_user_id = $provider->getAuthUserDataByAuthOptions('id', $authOptions);
      if ($social_user_id) {
          /**
           * Step2: Check if the social media account is linked to any WordPress account.
           */
          $wordpress_user_id = $provider->getUserIDByProviderIdentifier($social_user_id);

          if (!is_user_logged_in()) {

              /**
               * Step3: Handle the logged out users
               */
              if ($wordpress_user_id !== null) {
                  $provider->triggerSync($wordpress_user_id, $authOptions, "login", true);

                  /**
                   * Step 4: This social media account is already linked to a WordPress account.-> Log the user in using the returned User ID.
                   */

                  return $wordpress_user_id;
              } else {
                  /**
                   * Step 5: This social media account is not linked to any WordPress account, yet. -> Find out if we need to Link or Register
                   */

                  $wordpress_user_id = false;

                  /**
                   * Step 6: Attempt to match a WordPress account with the email address returned by the provider:
                   */
                  $email = $provider->getAuthUserDataByAuthOptions('email', $authOptions);
                  if (empty($email)) {
                      $email = '';
                  } else {
                      $wordpress_user_id = email_exists($email);
                  }

                  if ($wordpress_user_id !== false) {
                      /**
                       * Step 7: There is an email address match -> Link the existing user to the provider
                       */
                      if ($provider->linkUserToProviderIdentifier($wordpress_user_id, $social_user_id)) {
                          $provider->triggerSync($wordpress_user_id, $authOptions, "login", true);

                          //log the user in if the linking was successful

                          return $wordpress_user_id;
                      } else {
                          // Throw error: User already have another social account from this provider linked to the WordPress account that has the email match. They should use that account.
                      }

                  } else {
                      $base_name = $provider->getAuthUserDataByAuthOptions('name', $authOptions);
                      /**
                       * Step 8: There is no email address match -> Register a new WordPress account, e.g. with wp_insert_user()
                       * fill $user_data with the data that the provider returned
                       */
                      $user_data = array(
                          'user_login'   => '@'.str_replace(' ', '-', strtolower($base_name)).'-'.random_digits(4),
                          //generate a unique username, e.g. from the name returned by the provider: $provider->getAuthUserDataByAuthOptions('name', $authOptions);
                          'user_email'   => $email,
                          //use the email address returned by the provider, note: it can be empty in certain cases
                          'user_pass'    =>  wp_generate_password(),
                          //generate a password, e.g.: with wp_generate_password()
                          'display_name' => $base_name,
                          //generate a display name, e.g. from the name returned by the provider: $provider->getAuthUserDataByAuthOptions('name', $authOptions);
                          'first_name'   => $provider->getAuthUserDataByAuthOptions('first_name', $authOptions) ?? '',
                          //generate a first name, e.g.: from the first name returned by the provider: $provider->getAuthUserDataByAuthOptions('first_name', $authOptions);
                          'last_name'    => $provider->getAuthUserDataByAuthOptions('last_name', $authOptions) ?? '',
                          //generate a last name, e.g.: from the last name returned by the provider: $provider->getAuthUserDataByAuthOptions('last_name', $authOptions);
                      );


                      $wordpress_user_id = wp_insert_user($user_data);

                      if (!is_wp_error($wordpress_user_id) && $wordpress_user_id) {
                          /**
                           * Step 9: Link the new user to the provider
                           */
                          if ($provider->linkUserToProviderIdentifier($wordpress_user_id, $social_user_id, true)) {
                              $provider->triggerSync($wordpress_user_id, $authOptions, 'register', false);
                              $provider->triggerSync($wordpress_user_id, $authOptions, "login", true);

                              //The registration and the linking was successful -> log the user in.

                              return $wordpress_user_id;
                          }
                      } else {
                          //Throw error: There was an error with the registration
                      }
                  }
              }
          } else {
              /**
               * Step 10: Handle the linking for logged in users
               */
              $current_user = wp_get_current_user();
              if ($wordpress_user_id === null) {
                  // Let's connect the account to the current user!
                  if ($provider->linkUserToProviderIdentifier($current_user->ID, $social_user_id)) {
                      //account is linked, we don't need to trigger additional actions we just need to sync the avatar
                      $provider->triggerSync($current_user->ID, $authOptions, false, true);

                      return $current_user->ID;
                  } else {
                      //Throw error: Another social media account is already linked to the current WordPress account. The user need to unlink the currently linked one and he/she can link the this social media account.
                  }
              } else if ($current_user->ID != $wordpress_user_id) {
                  //Throw error: This social account is already linked to another WordPress user.
              }
          }
      }
  }

  return false;
}

function loginUser($user)
    {
      clean_user_cache( $user->ID );
      wp_clear_auth_cookie();

      // Set the current user and update the caches.
      wp_set_current_user( $user->ID );
      wp_set_auth_cookie( $user->ID, true, false );
      update_user_caches( $user );

        do_action('wp_login', $user->user_login, $user);
        return 'Logged In';
}



function validate_soc_provider($providerID) {
  if (NextendSocialLogin::isProviderEnabled($providerID)) {
      if (NextendSocialLogin::$enabledProviders[$providerID] instanceof NextendSocialProviderOAuth) {
          return true;
      } else {
          /*
           * OpenID providers don't have a secure Access Token, but just a simple ID that is usually easy to guess.
           * For this reason we shouldn't return the WordPress user ID over the REST API of providers based on OpenID authentication.
           */
          return new WP_Error('error', __('This provider doesn\'t support REST API calls!', 'nextend-facebook-connect'));
      }
  }

  return false;
}


function check_slot_availability($request){
  $params = $request->get_params();
  $product_id = $params['product_id'] ?? null;
  $start_date = $params['start_date'] ?? null;
  $max_slots = $params['occurrence_slots'] ?? null;

  $availability_obj = new stdClass();

  $pdt_bookings = get_post_meta( $product_id, 'booking_schedule', true );

  if(metadata_exists('post', $product_id, 'booking_schedule')){
    $filteredItems = array_filter($pdt_bookings, function($occ) use($start_date){
      return $occ['start_date'] == $start_date;
    });
    if($filteredItems){
      foreach($filteredItems as $the_occurrence){
         if($the_occurrence['booked_slots'] < intval($max_slots)){
          $availability_obj->available = true;
          $availability_obj->slots = intval($max_slots) - $the_occurrence['booked_slots'];
         }else{
          $availability_obj->available = false;
         }
      }
    }else{
      $availability_obj->available = true;
      $availability_obj->slots = intval($max_slots);
    }
  }else{
    $availability_obj->available = true;
    $availability_obj->slots = intval($max_slots);
  }
  return $availability_obj;
}


function create_slot_booking($request){
  $params = $request->get_params();
  $product_id = $params['product_id'] ?? null;
  $start_date = $params['start_date'] ?? null;
  $meta_start_date = $params['meta_start_date'] ?? null;
  $end_date = $params['end_date'] ?? null;
  $max_slots = $params['occurrence_slots'] ?? null; 
  $cost = $params['cost'] ?? null;
  $user_id = $params['user_id'] ?? null;

  $args = array(
    'product_id'  => $product_id,
    'start_date'  => $start_date,
    'user_id' => $user_id,
    'persons' => 1,
    'end_date'    => $end_date,
    'cost' => $cost
  );

  $booking_obj = new stdClass();
  if(metadata_exists('post', $product_id, 'booking_schedule')){
    $pdt_bookings = get_post_meta( $product_id, 'booking_schedule', true );

    $filteredItems = array_filter($pdt_bookings, function($occ) use($meta_start_date){
      return $occ['start_date'] == $meta_start_date;
    });

    if($filteredItems){
      foreach($filteredItems as $key => $the_occurrence){
         $taken_slots = $the_occurrence['booked_slots'];
         if($taken_slots < $max_slots){
          
          $booking = create_wc_booking( $product_id, $args, 'unpaid', false );
          if($booking){
            $the_occurrence['booked_slots'] = $taken_slots + 1;
            $pdt_bookings[$key] = $the_occurrence;
            update_post_meta( $product_id, 'booking_schedule', $pdt_bookings);
            $booking_obj->status = "occ_increased";
            $booking_obj->booking = $booking;
          }
         }else{
          $booking_obj->status = "occ_full";
         }
      }
    }else{
      $booking = create_wc_booking( $product_id, $args, 'unpaid', false );
        if($booking){
          $meta_arr = array();
          $slot_arr = array();

          $slot_arr['start_date'] = $meta_start_date;
          $slot_arr['booked_slots'] = 1; 

          $pdt_bookings[] = $slot_arr;
          update_post_meta( $product_id, 'booking_schedule', $pdt_bookings);
          $booking_obj->status = "occ_added";
          $booking_obj->booking = $booking;
        }
    }
  }else{
    $booking = create_wc_booking( $product_id, $args, 'unpaid', false );
    if($booking){
      $meta_arr = array();
      $slot_arr = array();

      $slot_arr['start_date'] = $meta_start_date;
      $slot_arr['booked_slots'] = 1; 

      $meta_arr[] = $slot_arr;
      add_post_meta( $product_id, 'booking_schedule', $meta_arr);
      $booking_obj->status = "occ_created";
      $booking_obj->booking = $booking;
    }
  }
  return $booking_obj;
}


function get_pdt_attribute_tax($request){
  $params = $request->get_params();

  $obj_arr = array();
  
  if($params['ids']){
    $attr_obj = NULL;
    $ids_str = $params['ids'];
    $idsArr = explode(',', $ids_str);
    foreach($idsArr as $id){
    //$id = $params['id'];
    $attr_name = wc_attribute_taxonomy_name_by_id(intval($id));
    $terms = get_terms( array( 
      'taxonomy' => $attr_name,
    ));
    $attr_obj['name'] = get_taxonomy_labels(get_taxonomy( $attr_name ))->singular_name;
    $attr_obj['terms'] = $terms; 

    $obj_arr[] = $attr_obj;
  }
  }
  $response = rest_ensure_response( $obj_arr );
  return $response;

}


//Custom user actions rest

function listing_user_actions( $request ) {
    $parameters = $request->get_params();
    $postId = isset($parameters['liked_id']) ? intval($parameters['liked_id']) : '';
    $removeId = isset($parameters['unliked_id']) ? intval($parameters['unliked_id']) : '';
    $userId = isset($parameters['user_id']) ? $parameters['user_id'] : '';

    
    $baseArr = array();
    $field = get_field( "field_617a86558e8e0", 'user_'.$userId );
    if (!empty($postId ) ) {
      if(empty($field)){
        $baseArr[] = $postId;
      }else{
        if(is_array($field)){
          if (in_array($postId, $field)){
            return;
          }else{
            $field[] = $postId;
          }
          array_push($baseArr, ...$field);
      }
     }
    }

    if (!empty($removeId)) {
      if(is_array($field)){
        if (($key = array_search($removeId, $field)) !== false) {
            unset($field[$key]);
        }
        array_push($baseArr, ...$field);
      }
    }
    
    update_field('field_617a86558e8e0', $baseArr, 'user_'.$userId);
    $showObj = new stdClass();
    $showObj->user = $userId; 
    $showObj->field = $baseArr;
    return $showObj;
}

function update_user_info( $request ) {
  $parameters = $request->get_params();
  $display_name = $parameters['display_name'] ?? null;
  $userId = $parameters['user_id'] ?? 0;

  $baseArr = new stdClass();
  $baseArr -> ID = $userId;
  if($display_name){
    $baseArr->display_name = $display_name;
  }

  $user_data = wp_update_user($baseArr);

  if ( is_wp_error( $user_data ) ) {
    // There was an error; possibly this user doesn't exist.
    echo 'Error.';
  } else {
    return get_userdata( $userId);
  }
}

//Include dates in rest event listing

function process_dates($listing){
  
   // $listing = \MyListing\Src\Listing::get( $event_id);

    $dates = [];
    $now = date_create('now');
    $field = $listing->get_field_object('event-date');
    if ( ! $field ) {
        return $dates;
    }

    if ( $field->get_type() === 'date' ) {
        $date = $field->get_value();
        if ( ! empty( $date ) && strtotime( $date ) ) {
            $dates[] = [
                'start' => $date,
                'end' => '',
                'gcal_link' => get_google_calendar_link($listing, $date, ''),
                'is_over' => $now->getTimestamp() > strtotime( $date, $now->getTimestamp() ),
            ];
        }
    }

    if ( $field->get_type() === 'recurring-date' ) {
        $dates = array_merge(
            \MyListing\Src\Recurring_Dates\get_previous_instances( $field->get_value(), 0 ),
            \MyListing\Src\Recurring_Dates\get_upcoming_instances( $field->get_value(), 1 )
        );

        foreach ( $dates as $key => $date ) {
            $dates[$key]['gcal_link'] = get_google_calendar_link($listing, $date['start'], $date['end']);
            $dates[$key]['is_over'] = $now->getTimestamp() > strtotime( $date['end'], $now->getTimestamp() );
        }
    }

    return $dates;
}

function process_field($field, &$_data, $post_id, $meta){
  switch ($field) {
    case 'schedule':
      $hours = get_post_meta($post_id, '_work_hours', true);
      $_data['schedule'] = $hours   ?? null;
      break;

    case 'food_menu':
      $food_menu = get_post_meta($post_id, '_food-drinks-menu', true);
      $_data['food_menu'] = $food_menu   ?? null;
      break;

    case 'event_date':
      $listing_post = \MyListing\Src\Listing::get( $post_id);
      $dates = process_dates($listing_post);
      $_data['event_date'] = $dates   ?? null;
      break;

    case 'rating':
      $_data['rating'] = $meta['user_rating'] ? intval($meta['user_rating'][0]) : null;
      break;

    case 'acf':
      $acf_data = get_fields($post_id) ?? null;
      $_data['acf'] = $acf_data ?? null;
      break;

  case 'page_views':
      $views = get_visits($post_id);
      $_data['page_views'] = $views  ?? null;
      break;

  case 'level':
      $_data['level'] =  $meta['_featured'][0] ? intval($meta['_featured'][0]) : 0;
      break;

  case 'category':
      $cats = get_the_terms( $post_id, 'job_listing_category' );
      $catIds = array();
      if($cats){
        foreach($cats as $cat){
          $catIds[] = $cat->term_id;
        }
      }
      if($catIds){
          $category = get_term( $catIds[0], 'job_listing_category' );
          $cat_meta = get_term_meta($catIds[0]);
          $category->rl_awesome = $cat_meta['rl_awesome'][0]   ?? null;
          $category->color = $cat_meta['color'][0]   ?? null;
      }
      $_data['category'] = $category  ?? null;
      break;

  case 'type':
      $_data['type'] = $meta['_case27_listing_type'][0]  ?? null;
      break;
    
  case 'xtra_large_thumb':
    $xlarge_thumb = get_the_post_thumbnail_url( $post_id, 'medium_large' );
      $_data['xtra_large_thumb'] = $xlarge_thumb   ?? null;
      break;

  case 'gallery':
      //$gallery = get_post_meta($post_id, '_job_gallery', true);
      $gallery = get_post_meta($post_id, 'listing_gallery', true);
      $_data['gallery'] = $gallery ?? null;
      break;

  case 'locations':
      $locs = get_the_terms( $post_id, 'region' );
      $_data['locations'] = $locs   ?? null;
      break;

  case 'categories':
      $cats = get_the_terms( $post_id, 'job_listing_category' );
      $_data['categories'] = $cats   ?? null;
      break;

  case 'large_thumb':
      $large_thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' );
      $_data['large_thumb'] = $large_thumbnail   ?? null;
      break;
      
  case 'thumbnail':
      $thumbnail = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
      $_data['thumbnail'] = $thumbnail   ?? null;
      break;

  case 'logo':
      $logo = get_post_meta($post_id, '_job_logo', true);
      $_data['logo'] = $logo  ? $logo[0] : null;
      break;

  case 'ticket_min_price':
      $_data['ticket_min_price'] = $meta['ticket_min_price'][0] ??  null;
      break;

  case 'ticket_min_price_html':
      $_data['ticket_min_price_html'] = $meta['ticket_min_price_html'][0] ??  null;
      break;

  case 'item_min_price':
      $_data['item_min_price'] = $meta['item_min_price'][0] ??  null;
      break;

  case 'item_min_price_html':
      $_data['item_min_price_html'] = $meta['item_min_price_html'][0] ??  null;
      break;
      
  case 'cover':
      //$cover = get_post_meta($post_id, '_job_cover', true);
      $cover = get_post_meta($post_id, 'listing_cover', true);
      //$_data['cover'] = $cover  ? $cover[0] : null;
      $_data['cover'] = $cover  ? $cover : null;
  break;

  case 'latitude':
      $_data['latitude'] = $meta['geolocation_lat'] ? floatval($meta['geolocation_lat'][0]) : null;
      break;

  case 'longitude':
      $_data['longitude'] = $meta['geolocation_long'] ? floatval($meta['geolocation_long'][0]) : null;
      break;

  case 'address':
      $_data['address'] = $meta['_job_location'][0]  ?? null;
      break;

  case 'venue':
      $_data['venue'] = $meta['_venue'][0]  ?? null;
      break;

  case 'event_type':
      $_data['event_type'] = $meta['_event-type'][0] ?? null;
      break;

 /*  case 'id':
      $_data['id'] = $post_id;
      break;
      
  case 'slug':
      $_data['slug'] = get_post_field( 'post_name', $post_id );;
      break; */

  /* case 'title':
      $_data['title'] = get_the_title($post_id);
      break; */

  case 'short_desc':
    $_data['short_desc'] = get_the_excerpt( $post_id);
    break;
  }
}

function my_rest_prepare_listing( $data, $post, $request ) {

    $params = $request->get_query_params();
    $_data = $data->data;
    $post_id = $post->ID;
    $listing_post = \MyListing\Src\Listing::get( $post_id);

    //$category = get_the_category ( $post->ID );

    $meta = get_post_meta( $post_id );

    foreach($meta as $key => $value){
      $val = $value[0];
      if(is_string($val)){
        if(str_starts_with($val,'field_')){
          unset( $meta[$key] );
        }else{
          if(str_starts_with($val,'a:')){
            $meta[$key] = unserialize($val);
          }else{
            $meta[$key] = $val;
          }
        }
      }
    }

    //$fields = $params['_fields']  ?? null;
    $_data['params'] = $params;

      $acf_data = get_fields($post_id) ?? null;
      $thumbnail = get_the_post_thumbnail_url( $post_id, 'thumbnail' );
      $large_thumbnail = get_the_post_thumbnail_url( $post_id, 'medium' );
      $xlarge_thumb = get_the_post_thumbnail_url( $post_id, 'medium_large' );
      $cats = get_the_terms( $post_id, 'job_listing_category' );
      $locs = get_the_terms( $post_id, 'region' );
  
      $catIds = array();
      if($cats){
        foreach($cats as $cat){
          $catIds[] = $cat->term_id;
        }
      }
  
      $views = get_visits($post_id);
  
      if($catIds){
        $category = get_term( $catIds[0], 'job_listing_category' );
        $cat_meta = get_term_meta($catIds[0]);
        $category->rl_awesome = $cat_meta['rl_awesome'][0]   ?? null;
        $category->color = $cat_meta['color'][0]   ?? null;
      }
  
      $excerpt = get_the_excerpt( $post_id);
      $the_content = apply_filters('the_content', get_the_content());
  
      $author = get_the_author_meta('ID');
      $comment_num = get_comments_number($post_id);
  
  
      if($meta['_case27_listing_type'] == 'event'){
        $dates = process_dates($listing_post);
        
        $_data['affiliates']['sponsors'] = $meta['_event-sponsors'] ?? null;
        $_data['persons']['special_guests'] = $meta['_special-guests'] ?? null;
        $_data['persons']['performers'] = $meta['_performers'] ?? null;
        $_data['listing_store']['tickets'] =  $meta['tickets']  ?? null;   
        $_data['event_date'] = $dates   ?? null;
        $_data['ticket_min_price'] = $meta['ticket_min_price'] ??  null;
        $_data['ticket_min_price_html'] = $meta['ticket_min_price_html'] ??  null;
      }
 
      $_data['item_min_price'] = $meta['item_min_price'] ??  null;
      $_data['item_min_price_html'] = $meta['item_min_price_html'] ??  null;
      $_data['rating'] = array_key_exists('user_rating', $meta) ? intval($meta['user_rating']) : null;
      $_data['food_menu'] = $meta['_food-drinks-menu']   ?? null;
      $_data['about_us']['our_history'] = $meta['_our-history']   ?? null; 
      $_data['about_us']['our_vision'] = $meta['_our-vision']   ?? null;
      $_data['about_us']['opening_date'] = $meta['_date-we-started']   ?? null; 
      $_data['about_us']['our_mission'] = $meta['_our-mission']   ?? null;
      $_data['landing']['greeting'] = $meta['_welcome_message']   ?? null;
      $_data['marketing']['punch_lines'] = $meta['_punch_lines']   ?? null; 
      $_data['marketing']['wcu']['wcu_intro_title'] = $meta['_wcu_intro_title'] ?? null;
      $_data['marketing']['wcu']['wcu_intro_detail'] = $meta['_wcu_intro_detail'] ?? null;
      $_data['marketing']['wcu']['list'] = $meta['_why_choose_us']   ?? null;
      $_data['marketing']['what_we_do']['wwd_intro_title'] = $meta['_wwd_intro_title'] ?? null;
      $_data['marketing']['what_we_do']['wwd_intro_detail'] = $meta['_wwd_intro_detail'] ?? null;
      $_data['marketing']['what_we_do']['wwd_services'] = $meta['_wwd_services'] ?? null;
      $_data['about_us']['faqs'] = $meta['_frequently-asked-questions'] ?? null;
      $_data['listing_store']['general_merchandise'] =  $meta['general_merchandise']  ?? null;
      $_data['author_id'] = $author;
      $_data['comment_num'] = $comment_num;
      $_data['tagline'] = $meta['_job_tagline'] ?? null; 
      $_data['category'] = $category  ?? null;
      $_data['home'] = $meta['_listing-home-page'] ?? null; 
      $_data['community_id'] = $meta['community_id'] ?? null; 
      $_data['phone'] = $meta['_job_phone']  ?? null;
      $_data['page_views'] = $views  ?? null;
      $_data['content'] = $the_content ?? null;
      $_data['persons']['team'] = $meta['_team'] ?? null;
      $_data['short_desc'] = $excerpt;
      $_data['latitude'] = $meta['geolocation_lat'] ? floatval($meta['geolocation_lat']) : null;
      $_data['longitude'] = $meta['geolocation_long'] ? floatval($meta['geolocation_long']) : null;
      $_data['address'] = $meta['_job_location']  ?? null;
      $_data['venue'] = $meta['_venue']  ?? null;
      $_data['event_type'] = $meta['_event-type'] ?? null;
      $_data['greeting'] = $meta['_greeting']  ?? null;
      //$_data['cover'] = $meta['_job_cover'][0] ??  null;
      $_data['cover'] = $meta['listing_cover'] ??  null;
      //$_data['gallery'] = $meta['_job_gallery'] ?? null;
      $_data['gallery'] = $meta['listing_gallery'] ?? null;
      $_data['logo'] = $meta['_job_logo'][0] ?? null;
      $_data['website'] = $meta['_job_website']  ?? null;
      $_data['type'] = $meta['_case27_listing_type']  ?? null;
      $_data['level'] =  $meta['_featured'] ? intval($meta['_featured']) : 0;
      $_data['social'] = $meta['_links']   ?? null;
      $_data['schedule'] = $meta['_work_hours']   ?? null;
      $_data['acf'] = $acf_data ?? null;
      $_data['whatsapp'] = $meta['_whatsapp-number'] ?? null;
      $_data['thumbnail'] = $thumbnail   ?? null;
      $_data['large_thumb'] = $large_thumbnail   ?? null;
      $_data['xtra_large_thumb'] = $xlarge_thumb   ?? null;
      $_data['categories'] = $cats   ?? null;
      $_data['locations'] = $locs   ?? null;
      $_data['meta'] = $meta;
    
    $data->data = $_data;
    return $data;
}
add_filter( 'rest_prepare_job_listing', 'my_rest_prepare_listing', 10, 3 );



//Post thumbanil in rest

function my_rest_prepare_post( $data, $post, $request ) {
    $_data = $data->data;

  	//$postMeta = get_post_meta($post->ID);
    $thumbnail = get_the_post_thumbnail_url( $post->ID, 'medium' );
  
    if($_data['acf']['listing']){
       $listing = $_data['acf']['listing'][0];
       $acf_data = get_fields($listing);
       if($acf_data['cover_photo']){
         $cover = $acf_data['cover_photo']['sizes']['large'];
         $_data['cover'] = $cover;
       }
       
    }
  
    $_data['thumbnail'] = $thumbnail;
    

    $data->data = $_data;
    return $data;
}
add_filter( 'rest_prepare_post', 'my_rest_prepare_post', 10, 3 );

//comments Rest query
add_filter( 'rest_comment_query', 'custom_comments_rest', 11, 2 );
function custom_comments_rest( $prepared_args, $request ){

 $prepared_args['hierarchical'] = 'threaded';

	return $prepared_args;
}

//custom rest comment item
add_filter( 'rest_prepare_comment', 'my_rest_prepare_comment', 10, 3 );

function my_rest_prepare_comment($response, $comment, $request){
  if (empty($response->data))
        return $response;
        $id = intval($comment->comment_ID);

      $response->data['count'] = 0;
      $response->data['replies'] = [];

      if(($comment->get_children([ 'parent' => $id, 'count' => true ] ) > 0)){
        $args = array(
          'parent'    => intval($id),
        );
        $comments_query = new WP_Comment_Query();
        $comments = $comments_query->query($args);
        $childArray = array();

        foreach ( $comments as $comment ) {
          $com_controller = new WP_REST_Comments_Controller();
          $data = $com_controller->prepare_item_for_response( $comment, $request );
          $childArray[] = $com_controller->prepare_response_for_collection( $data );
        }

        $childResponse = rest_ensure_response( $childArray );
        $count = count($comments);

        $response->data['count'] = $count;
        $response->data['replies'] = $childResponse->data;
      } 

      return $response;
} 

//Custom woo product query
$tax_arr = array('job_listing_category', 'case27_job_listing_tags', 'region');

function custom_taxonomy_rest($args, $request)
{
    $params = $request->get_query_params();
    $tax_name = $args['taxonomy'] ?? null;
    $taxonomy = get_taxonomy( $tax_name );
    $query_parent =  $params['parent'] ?? null;
    $parent_slug =  $params['parent_slug'] ?? null;

    if ( $query_parent ){
      if(is_numeric( $query_parent )){
          $parent_id = $query_parent;
      }elseif($parent_slug && ( $parent = get_term_by( 'slug', $parent_slug, $taxonomy->name ) ) && ! is_wp_error( $parent )){
          $parent_id = absint( $parent->term_id );
      }
     // $args['taxonomy'] = $taxonomy->name;
      $args['parent'] = $parent_id;
  }
    return $args;
}

foreach ($tax_arr as &$taxonomy) {
    add_filter("rest_{$taxonomy}_query", 'custom_taxonomy_rest', 10, 2);
}

//Custom woo product query
function modified_woo_rest($args, $request)
{
    $params = $request->get_query_params();
    $orderby = $params['orderby'] ? $params['orderby'] : null;
    $order = $params['order'] ? $params['order'] : null;

    //Remove Hidden items
    $args['tax_query'][] = [
        'taxonomy' => 'product_visibility',
        'field' => 'name',
        'terms' => array('exclude-from-catalog'),
        'operator' => 'NOT IN',
    ];

    if (isset($params['filter'])) {

        $filters = $params['filter'];

        foreach ($filters as $filter_key => $filter_value) {

            $args['tax_query'][] = [
                'taxonomy' => $filter_key,
                'field' => 'term_id',
                'terms' => \explode(',', $filter_value),
            ];
            $args['tax_query']['relation'] = 'AND';
        }

    }

    //random 
    if(isset($params['random'])){
        $random = $params['random'];
        $args['orderby'] = 'rand';
    }

    //discount
    if (!empty($orderby)) {
        if($orderby == 'discount'){
            $args['orderby']  = 'meta_value_num';
            $args['meta_key']  = '_discount_percentage';
        }else{
            $args['orderby'] = $orderby;
        }
        $args['order'] = $order;
    }

    //Filter by vendor
    if (isset($request['vendor'])) {
        $args['author'] = intval($request['vendor']);
        ;
    }

    return $args;
}
;
add_filter("woocommerce_rest_product_object_query", 'modified_woo_rest', 10, 2);

//Custom Woo Product data in rest

add_filter('woocommerce_rest_prepare_product_object', 'custom_product_rest', 10, 3);

function custom_product_rest($response, $object, $request) {
    if (empty($response->data))
        return $response;

        $id = $object->get_id();


        foreach ($response->data['images'] as $key => $image) {
            foreach (['medium', 'thumbnail', 'big_thumb'] as $size) {
                $image_info = wp_get_attachment_image_src($image['id'], $size);
                $response->data['images'][$key][$size] = $image_info[0];
            }
        }
    
    
        //$attributes = get_attributes($object);
    
        $pdt_discount = get_post_meta( $id, '_discount_percentage', true );
        $slots = get_post_meta( $id, 'occurance_slots', true );

        //$response->data['attributes'] = $attributes;
        $response->data['discount_rate'] = intval($pdt_discount);
 
    $listing_data = get_post_meta($id, 'listing_data', true);
    if($slots){
      $response->data['occurrence_slots'] = intval($slots);
    }

    $response->data['listing'] = $listing_data;
    return $response;
}

//category tax meta inherit
function inherit_cat_tax_meta($term_id, $tt_id, $taxonomy, $update, $args)
{

  if($taxonomy !== 'job_listing_category'){
    return;
  }
    // If this is a revision, get real post ID
    if ($parent_id = wp_is_post_revision($term_id))
        $term_id = $parent_id;

    $meta_arr = array();

    if (!function_exists('inherit_cat_meta')) {

        function inherit_cat_meta($new_id, $tax_slug)
        {
            $ansestor_arr = get_ancestors($new_id, $tax_slug);
            if ($ansestor_arr) {
                $ans_id = $ansestor_arr[0];
                $ans_meta = get_term_meta($ans_id);
                if ($ans_meta['image'][0]) {
                    return $ans_meta;
                } else {
                    return inherit_cat_meta($ans_id, $tax_slug);
                }
            }
        }
    }

    $cat_meta = get_term_meta($term_id);

    if (isset($cat_meta['image'][0]) && !empty($cat_meta['image'][0]) && $cat_meta['color'][0] && $cat_meta['cover_photo'][0]) {
        return;
    } else {
        $borrowed_meta = inherit_cat_meta($term_id, 'job_listing_category');
        if ($borrowed_meta) {
            $meta_arr['color'] = $cat_meta['color'] ? $cat_meta['color'] : $borrowed_meta['color'];
            $meta_arr['image'] = $cat_meta['image'] ? $cat_meta['image'] : $borrowed_meta['image'];
            $meta_arr['rl_awesome'] = $cat_meta['rl_awesome'] ? $cat_meta['rl_awesome'] : $borrowed_meta['rl_awesome'];
        }
    }

    //$total_meta = array_replace_recursive($cat_meta, $meta_arr);


  //  remove_action('saved_term', 'inherit_cat_tax_meta');
    //wp_update_post( $my_args );
    if ($meta_arr['color'][0]) {
        update_term_meta($term_id, 'color', $meta_arr['color'][0]);
    }
    if ($meta_arr['image'][0]) {
        update_term_meta($term_id, 'image', $meta_arr['image'][0]);
    }
    if ($meta_arr['rl_awesome'][0]) {
        update_term_meta($term_id, 'rl_awesome', $meta_arr['rl_awesome'][0]);
    }

  //  add_action('saved_term', 'inherit_cat_tax_meta');
}
add_action('saved_term', 'inherit_cat_tax_meta', 11, 5);


//term meta inheritance
function add_woo_tax_meta($term_id, $tt_id, $taxonomy, $update, $args)
{

  if($taxonomy !== 'product_cat'){
    return;
  }
    // If this is a revision, get real post ID
    if ($parent_id = wp_is_post_revision($term_id))
        $term_id = $parent_id;

    $meta_arr = array();

    if (!function_exists('inherit_meta')) {
        function inherit_meta($new_id, $tax_slug)
        {
            $ansestor_arr = get_ancestors($new_id, $tax_slug);
            if ($ansestor_arr) {
                $ans_id = $ansestor_arr[0];
                $ans_meta = get_term_meta($ans_id);
                if ($ans_meta['thumbnail_id'][0]) {
                    return $ans_meta;
                } else {
                    return inherit_meta($ans_id, $tax_slug);
                }
            }
        }
    }

    $cat_meta = get_term_meta($term_id);

    if ($cat_meta['thumbnail_id'][0] && $cat_meta['color'][0] && $cat_meta['cover_photo'][0]) {
        return;
    } else {
        $borrowed_meta = inherit_meta($term_id, 'product_cat');
        if ($borrowed_meta) {
            $meta_arr['color'] = $cat_meta['color'] ? $cat_meta['color'] : $borrowed_meta['color'];
            $meta_arr['thumbnail_id'] = $cat_meta['thumbnail_id'] ? $cat_meta['thumbnail_id'] : $borrowed_meta['thumbnail_id'];
            $meta_arr['cover_photo'] = $cat_meta['cover_photo'] ? $cat_meta['cover_photo'] : $borrowed_meta['cover_photo'];
        }
    }

    $total_meta = array_replace_recursive($cat_meta, $meta_arr);


    remove_action('saved_term', 'add_woo_tax_meta');
    //wp_update_post( $my_args );
    if ($meta_arr['color'][0]) {
        update_term_meta($term_id, 'color', $meta_arr['color'][0]);
    }
    if ($meta_arr['thumbnail_id'][0]) {
        update_term_meta($term_id, 'thumbnail_id', $meta_arr['thumbnail_id'][0]);
    }
    if ($meta_arr['cover_photo'][0]) {
        update_term_meta($term_id, 'cover_photo', $meta_arr['cover_photo'][0]);
    }

    add_action('saved_term', 'add_woo_tax_meta');
}
add_action('saved_term', 'add_woo_tax_meta', 10, 5);

//Term meta in Rest


add_action( 'rest_api_init', 'add_term_meta_rest' );
    
    function add_term_meta_rest() {
        register_rest_field( ['job_listing_category', 'payment-method', 'case27_job_listing_tags'],
            'term_meta',
            array(
                'get_callback'      => 'term_meta_callback',
                'update_callback'   => function ( $value, $object )  {
					// Update the field/meta value.
					update_post_meta( $object->ID, 'term_meta', term_meta_callback($object) );
				},
                'schema'            => null,
            )
        );

        register_rest_field( ['job_listing_category', 'region'],
            'extra_meta',
            array(
                'get_callback'      => 'extra_term_meta',
                'update_callback'   => function ( $value, $object )  {
					// Update the field/meta value.
					update_post_meta( $object->ID, 'extra_meta', extra_term_meta($object) );
				},
                'schema'            => null,
            )
        );

      
    }
    function total_term_post_count( $cat_id) {
     $tax = get_term( $cat_id ) -> taxonomy;
      $q = new WP_Query( array(
          'nopaging' => true,
          'tax_query' => array(
              array(
                  'taxonomy' => $tax,
                  'field' => 'id',
                  'terms' => $cat_id,
                  'include_children' => true,
              ),
          ),
          'fields' => 'ids',
      ) );
      return $q->post_count;
  }

    function term_meta_callback( $term, $field_name, $request) {
        $meta_0bj = new stdClass();
        $meta = get_term_meta( $term['id'] );
        $meta_0bj->icon_image_url = array_key_exists('icon_image', $meta) ? wp_get_attachment_url(number_format($meta['icon_image'][0])) : null;
        $meta_0bj->image_url = array_key_exists('image', $meta) ? wp_get_attachment_url(intval($meta['image'][0])) : null;
        $meta_0bj->color = $meta['color'][0]  ?? null;
      	$meta_0bj->icon = $meta['icon'][0]  ?? null;
        $meta_0bj->rl_awesome = $meta['rl_awesome'][0]  ?? null;
        $meta_0bj->iconify = $meta['iconify'][0]  ?? null;
        //$meta_0bj->meta = $meta  ?? null;
        return $meta_0bj;
    }

    function extra_term_meta( $term, $field_name, $request) {
      $meta_0bj = new stdClass();
      $meta_0bj->total = total_term_post_count($term['id']);
      return $meta_0bj;
  }

//Product category rest
function filter_woocommerce_rest_prepare_taxonomy( $response, $item, $request ) { 

  if (empty($response->data))
        return $response;

    $id = $item->term_id; 

    $attributes = get_term_meta($id, 'usable_attributes', true);
    $unique_att_terms = get_term_meta($id, 'unique_attributes', true);

    $ansestor_arr = get_ancestors( $id, 'product_cat');
    $top_ans = null;
    
    if($ansestor_arr){
      if(count($ansestor_arr) === 1){
        $top_ans = $ansestor_arr[0];
      }elseif(count($ansestor_arr) > 1){
        $top_ans = end($ansestor_arr);
      }
    }

    $attr_ids_arr = array();
    $unique_att_terms_ids_arr = array();

    //Brands
    if(!empty($unique_att_terms)){     
      //$unique_att_terms_ids_arr = $unique_att_terms; 
      foreach($unique_att_terms as $attr_term){
        $unique_att_terms_ids_arr[] = intval($attr_term);
      }     
    }elseif(empty($unique_att_terms)){
          $ans_unique_att_terms = get_term_meta($top_ans, 'unique_attributes', true);
          if(!empty($ans_unique_att_terms)){
            foreach($ans_unique_att_terms as $attr_term){
              $unique_att_terms_ids_arr[] = intval($attr_term);
            } 
            //$unique_att_terms_ids_arr = $ans_brands;
          }
    }else{
      $unique_att_terms_ids_arr[] = null;
    }

       $image_id = $response->data['image'] ? $response->data['image']['id'] : null;
       if($image_id){
        foreach (['medium', 'thumbnail', 'big_thumb'] as $size) {
            $image_info = wp_get_attachment_image_src($image_id, $size);
            $response->data['image'][$size] = $image_info[0];
        }
       }

    if(!empty($attributes)){
        foreach($attributes as $attr){
        $attr_ids_arr[] = wc_attribute_taxonomy_id_by_name($attr);
        }
    }elseif(empty($attributes)){
          $ans_attrs = get_term_meta($top_ans, 'usable_attributes', true);
          if($ans_attrs){
            //$result = array_merge($array1, $array2);
            foreach($ans_attrs as $attr){
              $attr_ids_arr[] = wc_attribute_taxonomy_id_by_name($attr);
            }
          }
    }else{
        $attr_ids_arr[] = null;
    }

    $response->data['attribute_groups'] = $attr_ids_arr;
    $response->data['unique_att_terms'] = $unique_att_terms_ids_arr;

  return $response; 
}; 
       
add_filter( "woocommerce_rest_prepare_product_cat", 'filter_woocommerce_rest_prepare_taxonomy', 10, 3 );    

//Rest directory query

function location_address_where( $where, $query ) {
    global $wpdb;
    $location = $GLOBALS['mylisting_search_location'];
    $where .= $wpdb->prepare( " AND mylisting_locations.address LIKE %s ", '%'.$wpdb->esc_like( $location ).'%' );

    return $where;
}

function location_address_join( $join, $query ) {
    global $wpdb;
    $join .= <<<SQL
        INNER JOIN {$wpdb->prefix}mylisting_locations AS mylisting_locations
            ON {$wpdb->posts}.ID = mylisting_locations.listing_id
    SQL;

    return $join;
}
function directory_query_args( $args, $request ) {
    global $wpdb;
    $query_base_class = new \MyListing\Src\Queries\Query();
    $rest_control = new WP_REST_Posts_Controller('job_listing');

    add_filter( 'posts_join', [ $query_base_class, 'priority_field_join' ], 30, 2 );
    add_filter( 'posts_orderby', [ $query_base_class, 'priority_field_orderby' ], 40, 2 );
    add_filter( 'posts_distinct', [ $query_base_class, 'prevent_duplicates' ], 30, 2 );

    $args = wp_parse_args( $args, [
        'search_location'   => '',
        'search_keywords'   => '',
        'offset'            => 0,
        'posts_per_page'    => 20,
        'orderby'           => 'date',
        'order'             => 'DESC',
        //'fields'            => 'ids',
        'post__in'          => [],
        'post__not_in'      => [],
        'meta_key'          => null,
        'meta_query'        => [],
        'tax_query'         => [],
        'author'            => null,
        'ignore_sticky_posts' => true,
        'mylisting_orderby_rating' => false,
        'mylisting_ignore_priority' => false,
        'recurring_dates' => [],
        'title_search' => '',
        'description_search' => '',
    ] );

    do_action( 'get_job_listings_init', $args );

    $query_args = array(
        'post_type'              => 'job_listing',
        'post_status'            => 'publish',
        'ignore_sticky_posts'    => $args['ignore_sticky_posts'],
        'offset'                 => absint( $args['offset'] ),
        'posts_per_page'         => intval( $args['posts_per_page'] ),
        'orderby'                => $args['orderby'],
        'search_keywords'        => $args['search_keywords'],
        'order'                  => $args['order'],
        'tax_query'              => $args['tax_query'],
        'meta_query'             => $args['meta_query'],
        'update_post_term_cache' => false,
        'update_post_meta_cache' => false,
        'cache_results'          => false,
        //'fields'                 => $args['fields'],
        //'post__in'                =>  $args['post__in'],
        'author'                 => $args['author'],
        'mylisting_orderby_rating' => $args['mylisting_orderby_rating'],
        'mylisting_ignore_priority' => $args['mylisting_ignore_priority'],
        'mylisting_prevent_duplicates' => true,
    );
    

    if ( $args['posts_per_page'] < 0 ) {
        $query_args['no_found_rows'] = true;
    }

    if ( ! empty( $args['search_location'] ) ) {
        $GLOBALS['mylisting_search_location'] = sanitize_text_field( $args['search_location'] );
        add_filter( 'posts_join', 'location_address_join', 30, 2 );
        add_filter( 'posts_where', 'location_address_where', 30, 2 );
    }

    if (!empty($args['post__in'])) {
        $query_args['post__in'] = $args['post__in'];
    }

    //$query_args['post__in'] = array('2089');

    if (!empty($args['post__not_in'])) {
        $query_args['post__not_in'] = $args['post__not_in'];
    }

    if ( ! empty( $args['search_keywords'] ) ) {
        $query_args['s'] = $GLOBALS['mylisting_search_keywords'] = sanitize_text_field( $args['search_keywords'] );
        add_filter( 'posts_search', [ $query_base_class, 'keyword_search' ] );
    }

    $query_args = apply_filters( 'job_manager_get_listings', $query_args, $args );

    if ( empty( $query_args['meta_query'] ) ) {
        unset( $query_args['meta_query'] );
    }

    if ( empty( $query_args['tax_query'] ) ) {
        unset( $query_args['tax_query'] );
    }

    if ( ! $query_args['author'] ) {
        unset( $query_args['author'] );
    }

    if ( $args['meta_key'] !== null ) {
        $query_args['meta_key'] = $args['meta_key'];
    }

    if ( ! empty( $args['recurring_dates'] ) ) {
        $query_args['recurring_dates'] = $args['recurring_dates'];
        add_filter( 'posts_join', [ $query_base_class, 'events_field_join' ], 30, 2 );
        add_filter( 'posts_where', [ $query_base_class, 'events_field_where' ], 30, 2 );
        add_filter( 'posts_orderby', [ $query_base_class, 'events_field_orderby' ], 30, 2 );
    }

    if ( ! empty( $args['title_search'] ) ) {
        $query_args['title_search'] = $args['title_search'];
        add_filter( 'posts_where', [ $query_base_class, 'title_search' ], 30, 2 );
    }

    if ( ! empty( $args['description_search'] ) ) {
        $query_args['description_search'] = $args['description_search'];
        add_filter( 'posts_where', [ $query_base_class, 'description_search' ], 30, 2 );
    }

    // Filter args
    $query_args = apply_filters( 'get_job_listings_query_args', $query_args, $args );
    $query_args = apply_filters( 'mylisting/explore/args', $query_args, $args );

    do_action( 'before_get_job_listings', $query_args, $args );

    $posts = array();
   // $q_posts = get_posts($query_args);

    $the_query = new WP_Query( $query_args );

      // The Loop
      if ( $the_query->have_posts() ) {

        global $post;
          while ( $the_query->have_posts() ) {
              $the_query->the_post();
              $data    = $rest_control->prepare_item_for_response( $post, $request );
              $posts[] = $rest_control->prepare_response_for_collection( $data );
          }
      } else {
          // no posts found
      }
      /* Restore original Post Data */
      wp_reset_postdata();

    /* foreach ( $q_posts as $post ) {
       $data    = $rest_control->prepare_item_for_response( $post, $request );
       $posts[] = $rest_control->prepare_response_for_collection( $data );
    } */

    // return results
    //var_dump($query_args);
    return new WP_REST_Response($posts, 200);
    /* 
    $args_for_ids = $query_args;
    //$args_for_ids['fields'] = 'ids';
    $ids_result = new \WP_Query( $args_for_ids );
    $rest_request = new WP_REST_Request();
    if($ids_result->posts && !empty($ids_result->posts)){
        //$query_args['include'] = $ids_result->posts;
        $request->set_query_params(
           array(
                'include'	=> $ids_result->posts,
            ) 
        );
        $rest_request->set_query_params(
            array(
                'include'	=> $ids_result->posts,
            )
        );
        //return $ids_result->posts[0];
        return new WP_REST_Response(
          array(
            'body_response' => $ids_result
          )
        ); 
    }else{
        return [];
    }*/
    
   // $result = $rest_control->get_items( $request );

    do_action( 'mylisting/explore/after-query' );

    remove_filter( 'posts_join', [ $query_base_class, 'priority_field_join' ], 30 );
    remove_filter( 'posts_orderby', [ $query_base_class, 'priority_field_orderby' ], 40 );
    remove_filter( 'posts_distinct', [ $query_base_class, 'prevent_duplicates' ], 30 );
    remove_filter( 'posts_search', [ $query_base_class, 'keyword_search' ] );
    remove_filter( 'posts_join', [ $query_base_class, 'events_field_join' ], 30 );
    remove_filter( 'posts_where', [ $query_base_class, 'events_field_where' ], 30 );
    remove_filter( 'posts_orderby', [ $query_base_class, 'events_field_orderby' ], 30 );

    // Remove rating field filter if used.
    remove_filter( 'posts_join', [ $query_base_class, 'rating_field_join' ], 35 );
    remove_filter( 'posts_orderby', [ $query_base_class, 'rating_field_orderby' ], 35 );

   // return $result;
   // return $request->get_params();
}

// Extend the `WP_REST_Posts_Controller` class
class Custom_Terms_Controller extends WP_REST_Terms_Controller
{

  public function register_routes() {
    register_rest_route(
      $this->namespace,  $this->taxonomy, [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_items'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
            'args' => $this->get_collection_params(),
        ],
        'schema' => [$this, 'get_public_item_schema'],
    ]);
  }
   
  public function get_items( $request ) {

    $query_parent =  $request['parent'] ?? null;
    $parent_slug =  $request['parent_slug'] ?? null;

    // Retrieve the list of registered collection query parameters.
    $registered = $this->get_collection_params();

    $parameter_mappings = array(
      'exclude'    => 'exclude',
      'include'    => 'include',
      'order'      => 'order',
      'orderby'    => 'orderby',
      'post'       => 'post',
      'hide_empty' => 'hide_empty',
      'per_page'   => 'number',
      'search'     => 'search',
      'slug'       => 'slug',
    );
  
    $prepared_args = array( 'taxonomy' => $this->taxonomy );
  
    foreach ( $parameter_mappings as $api_param => $wp_param ) {
      if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
        $prepared_args[ $wp_param ] = $request[ $api_param ];
      }
    }
  
    if ( isset( $prepared_args['orderby'] ) && isset( $request['orderby'] ) ) {
      $orderby_mappings = array(
        'include_slugs' => 'slug__in',
      );
  
      if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
        $prepared_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
      }
    }
  
    if ( isset( $registered['offset'] ) && ! empty( $request['offset'] ) ) {
      $prepared_args['offset'] = $request['offset'];
    } else {
      $prepared_args['offset'] = ( $request['page'] - 1 ) * $prepared_args['number'];
    }
  
    $taxonomy_obj = get_taxonomy( $this->taxonomy );
  
    if ( $taxonomy_obj->hierarchical ) {
     if($query_parent){
        if ( 0 === $request['parent'] ) {
        // Only query top-level terms.
        $prepared_args['parent'] = 0;
        } else {
          if ( $request['parent'] ) {
            $prepared_args['parent'] = $request['parent'];
          }
        }
      }elseif($parent_slug && ( $parent = get_term_by( 'slug', $parent_slug, $taxonomy_obj->name ) ) && ! is_wp_error( $parent )){
        $prepared_args['parent'] = absint( $parent->term_id );
      }

      }
  
    /**
     * @since 4.7.0
     *
     * @link https://developer.wordpress.org/reference/functions/get_terms/
     *
     * @param array           $prepared_args Array of arguments for get_terms().
     * @param WP_REST_Request $request       The REST API request.
     */
    $prepared_args = apply_filters( "rest_{$this->taxonomy}_query", $prepared_args, $request );
  
    if ( ! empty( $prepared_args['post'] ) ) {
      $query_result = wp_get_object_terms( $prepared_args['post'], $this->taxonomy, $prepared_args );
  
      // Used when calling wp_count_terms() below.
      $prepared_args['object_ids'] = $prepared_args['post'];
    } else {
      $query_result = get_terms( $prepared_args );
    }
  
    $count_args = $prepared_args;
  
    unset( $count_args['number'], $count_args['offset'] );
  
    $total_terms = wp_count_terms( $count_args );
  
    // wp_count_terms() can return a falsey value when the term has no children.
    if ( ! $total_terms ) {
      $total_terms = 0;
    }
  
    $response = array();
  
    foreach ( $query_result as $term ) {
      $data       = $this->prepare_item_for_response( $term, $request );
      $response[] = $this->prepare_response_for_collection( $data );
    }
  
    $response = rest_ensure_response( $response );
  
    // Store pagination values for headers.
    $per_page = (int) $prepared_args['number'];
    $page     = ceil( ( ( (int) $prepared_args['offset'] ) / $per_page ) + 1 );
  
    $response->header( 'X-WP-Total', (int) $total_terms );
  
    $max_pages = ceil( $total_terms / $per_page );
  
    $response->header( 'X-WP-TotalPages', (int) $max_pages );
  
    $request_params = $request->get_query_params();
    $collection_url = rest_url( rest_get_route_for_taxonomy_items( $this->taxonomy ) );
    $base           = add_query_arg( urlencode_deep( $request_params ), $collection_url );
  
    if ( $page > 1 ) {
      $prev_page = $page - 1;
  
      if ( $prev_page > $max_pages ) {
        $prev_page = $max_pages;
      }
  
      $prev_link = add_query_arg( 'page', $prev_page, $base );
      $response->link_header( 'prev', $prev_link );
    }
    if ( $max_pages > $page ) {
      $next_page = $page + 1;
      $next_link = add_query_arg( 'page', $next_page, $base );
  
      $response->link_header( 'next', $next_link );
    }
  
    return $response;
  }
  
}

//shortcodes
//stats
add_shortcode( 'stats_showcase', 'statistics_showcase' );
function statistics_showcase( $atts ) {
	$result = count_users();
  echo 'There are ', $result['total_users'], ' total users';

  foreach( $result['avail_roles'] as $role => $count )
      echo ', ', $count, ' are ', $role, 's';
  echo '.';
}

//Link to users post
add_shortcode( 'users_post_link', 'users_site_link' );
function users_site_link( $atts ) {
	echo '<div class="final_step"><p class="_instructions">This step is important. Click the button to make create/update your page for the public</p><a class="btn _action" href="https://lyvecity.com/authors/refresh?path=/events/'. get_post()->post_name . '">Publish your page</a></div>';
}

//Login/out button

add_shortcode( 'auth_button', 'login_logout' );
/**
 * Add a login/logout shortcode button
 * @since 1.0.0
 */
function login_logout() {
ob_start();
    if (is_user_logged_in()) : 
    // Set the logout URL - below it is set to the root URL
    ?>
    <a role="button" href="<?php echo wp_logout_url('/'); ?>">Log Out</a>

<?php 
    else : 
    // Set the login URL - below it is set to get_permalink() - you can set that to whatever URL eg '/whatever'
?>
    <a role="button" href="<?php echo wp_login_url(get_permalink()); ?>">Log In</span></a>

<?php 
    endif;

return ob_get_clean();
}


// Extend the `WP_REST_Posts_Controller` class
class Custom_Listings_Controller extends WP_REST_Posts_Controller
{

    // Override the register_routes() and add '/m-api/v1'
   public function register_routes()
    {
        register_rest_route(
          $this->namespace, 'listings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);

        register_rest_route(
          $this->namespace, 'random_listings', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_items'],
                'permission_callback' => [$this, 'get_items_permissions_check'],
                'args' => $this->get_collection_params(),
            ],
            'schema' => [$this, 'get_public_item_schema'],
        ]);

        // $schema        = $this->get_item_schema(); 
        $get_item_args = array(
            'context' => $this->get_context_param( array( 'default' => 'view' ) ),
        );
        if ( isset( $schema['properties']['password'] ) ) {
            $get_item_args['password'] = array(
                'description' => __( 'The password for the post if it is password protected.' ),
                'type'        => 'string',
            );
        } 
        register_rest_route(
            $this->namespace,
            'listings(?:/(?P<id>\d+))?',
            array(
                'args'   => array(
                    'id' => array(
                        'description' => __( 'Unique identifier for the post.' ),
                        'type'        => 'integer',
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_item' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => array(
                    'context' => $this->get_context_param( array( 'default' => 'view' ) ),
                ),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    // Override the `prepare_items_query` method in order to add custom query arguments
    protected function prepare_items_query($prepared_args = [], $request = null)
    {
        // Call the parent class method
        $query_args = parent::prepare_items_query($prepared_args, $request);

        // Extend custom query arguments
        $params = $request->get_params();
        $order = $params['order'] ?? null;
        $orderby = $params['order_by']  ?? null;
        $meta_key = $params['meta_key']  ?? null;
        $tag  = $params['tag']  ?? null;
        $author  = $params['author']  ?? null;
        $filters  = $params['filter']  ?? null;
        $page  = $params['page']  ?? null;
        $per_page  = $params['per_page']  ?? null;
        $keyword  = $params['keyword']  ?? null;
        $randomize = $params['random']  ?? null;
       
        
      $custom_args = array(
            'post_type'  => array('job_listing'),
            'paged'		=> $page,
            'posts_per_page' => $per_page,
            //'author' => $author,
            'meta_key' => $meta_key,
            //'order'   => $order,
            's'     =>  $keyword ? $keyword : ''
          );
      
     // If filter by category or attributes.
        //if ( ! empty( $category ) || ! empty($filters) ) {
          $custom_args['tax_query']['relation'] = 'AND';
    
          // Category filter.
          if (!empty($params['category'] ) ) {
            $category  = $params['category'];	

            $custom_args['tax_query'][] = [
              'taxonomy' => 'job_listing_category',
              'field'    => 'slug',
              'terms'    => [ $category ],
              'include_children' => true
            ];
          }

          //Keyword search
          if ( ! empty( $author ) ) {
            $custom_args['author'] = $author;
        }
          
          // Location filter.
          if ( ! empty($params['location'] ) ) {
            $location  = $params['location'];
            $custom_args['tax_query'][] = [
              'taxonomy' => 'region',
              'field'    => 'slug',
              'terms'    => [ $location ],
              'include_children' => true
            ];
          }
          
            // Tag filter.
          if ( ! empty($tag ) ) {
            
            $custom_args['tax_query'][] = [
              'taxonomy' => 'case27_job_listing_tags',
              'field'    => 'term_id',
              'terms'    => [ $tag ],
            ];
          }
    
          //Keyword search
          if ( ! empty( $keyword ) ) {
              $custom_args['s'] = $keyword;
          }
          
          if ( ! empty( $orderby ) ) {
              $custom_args['orderby'] = $orderby;
              $custom_args['order'] = $order;
          }elseif (!empty($randomize)) {
            $custom_args['orderby'] = 'rand';
          }
    
          // Attributes filter.
          if ( ! empty( $filters ) ) {
            
            foreach ( $filters as $filter_key => $filter_value ) {
              if ( $filter_key === 'min_price' || $filter_key === 'max_price' ) {
                continue;
              }
    
              if($filter_key==='pa_man_country' || $filter_key === 'pa_brand'){
                $custom_args['tax_query'][] = [
                  'taxonomy' => $filter_key,
                  'field'    => 'name',
                  'terms'    => \explode( ',', $filter_value ),
                ];
              }
    
              $custom_args['tax_query'][] = [
                'taxonomy' => $filter_key,
                'field'    => 'term_id',
                'terms'    => \explode( ',', $filter_value ),
              ];
            }
          }
    
          // Min / Max price filter.
          if ( isset( $filters['min_price'] ) || isset( $filters['max_price'] ) ) {
            $price_request = [];
    
            if ( isset( $filters['min_price'] ) ) {
              $price_request['min_price'] = $filters['min_price'];
            }
    
            if ( isset( $filters['max_price'] ) ) {
              $price_request['max_price'] = $filters['max_price'];
            }
    
            $custom_args['meta_query'][] = \wc_get_min_max_price_meta_query( $price_request );
          }
        
         
        // Return a merged array including default and custom arguments
        return array_merge($query_args, $custom_args);
    }

}

// Create an instance of `Custom_Listings_Controller` and call register_routes() methods
add_action('rest_api_init', function () {
  
  $tax_arr = array('job_listing_category', 'case27_job_listing_tags', 'region');

    foreach ($tax_arr as &$taxonomy) {
      $controller_instance = new Custom_Terms_Controller($taxonomy);
      $controller_instance->register_routes();
    }
    $listingsController = new Custom_Listings_Controller('job_listing');
    $listingsController->register_routes();
});


//acf after post save
//add_action('acf/save_post', 'acf_after_save_post');
function acf_after_save_post( $post_id ) {

    $tickets = get_field('tickets', $post_id) ?? null;
    $merch = get_field('general_merchandise', $post_id) ?? null;

    if( $tickets && count($tickets) !== 0){
      $args = array(
        'posts_per_page' => 1,
        'post_type' => 'product',
        'orderby' => 'meta_value_num',
        'meta_key' => '_price',
        'order' => 'asc',
        'post__in' => $tickets
    );
    $the_query = new WP_Query( $args );
    if ($the_query->have_posts()) {
      $first_post = $the_query->posts[0];
      $product = wc_get_product( $first_post->ID);
      update_post_meta( $post_id, 'ticket_min_price_html', $product->get_price_html());
      update_post_meta( $post_id, 'ticket_min_price', $product->get_price());
    }
    }

    if( $merch && count($merch) !== 0){
      $args = array(
        'posts_per_page' => 1,
        'post_type' => 'product',
        'orderby' => 'meta_value_num',
        'meta_key' => '_price',
        'order' => 'asc',
        'post__in' => $merch
    );
    $the_query = new WP_Query( $args );
    if ($the_query->have_posts()) {
      $first_post = $the_query->posts[0];
      $product = wc_get_product( $first_post->ID);
      update_post_meta( $post_id, 'items_min_price_html', $product->get_price_html());
      update_post_meta( $post_id, 'items_min_price', $product->get_price());
    }
    }
}


add_filter( 'acf/rest/get_fields', function ( $fields, $resource, $http_method ) {
    // Modify and return the $fields array here.

    // Example 1: Disable all fields for all request methods on a particular custom post type.
    if ( $resource['type'] == 'post' && $resource['sub_type'] == 'job_listing') {
       
      return array_filter( $fields, function ( $field ) {
            return ! in_array( $field['key'], [
                'field_5feee4b968b63',
            ] );
        } );
    }

    // Example 2: Show only a specific field on term GET requests.
    if ( $http_method == 'GET' && $resource['type'] == 'term' ) {
        return wp_list_filter( $fields, [ 'name' => 'author' ] );
    }

    // Example 3: Exclude particular field types on user POST requests.
    if ( $http_method == 'POST' &&  $resource['type'] == 'user' ) {
        return array_filter( $fields, function ( $field ) {
            return ! in_array( $field['type'], [
                'password',
                'file',
            ] );
        } );
    }

    return $fields;
}, 10, 3 );


//logout user by rest
function logout_user_rest($request){
  $parameters = $request->get_params();
  $headers = $request->get_headers();
  $cookies = $request->get_header('cookie');  

  $ret_obj = new stdClass();
  
  $user_id = $parameters['user_id'] ?? null;
  if(is_user_logged_in() && $user_id) {
    $ret_obj->user_id = $user_id;
  }

  $ret_obj->headers = $headers;
  $ret_obj->cookies = $cookies;
  return $ret_obj;
  
}

//Record vsist
function record_visit($request ) {
    
    $parameters = $request->get_params();
    $post_id = $parameters['listing_id'];
    $city_name = isset($parameters['city']) ? $parameters['city'] : null;

		// Get visitor data and insert visit.
    	$visitor = \MyListing\Src\Visitor::instance();
    	$ref = $visitor->get_referrer();
    	$os = $visitor->get_os();
    	$location = $visitor->get_location();
      //$city = $visitor->get_visit_city();
		add_visit( [
			'listing_id' => $post_id,
			'fingerprint' => $visitor->get_fingerprint(),
			'ip_address' => $visitor->get_ip(),
			'language' => $visitor->get_language(),
			'ref_url' => $ref ? $ref['url'] : null,
			'ref_domain' => $ref ? $ref['domain'] : null,
			'os' => $os ? $os['os'] : null,
			'device' => $os ? $os['device'] : null,
			'browser' => $visitor->get_browser(),
			'http_user_agent' => $visitor->get_user_agent(),
			'country_code' => $location ?? null,
			'city' => $city_name,
		] );
}

function add_visit( $args ) {
    global $wpdb;

    if ( empty( $args['fingerprint'] ) || empty( $args['listing_id'] ) ) {
        return;
    }

    // Get values.
    $values = array_filter( [
        'listing_id' => $args['listing_id'],
        'fingerprint' => $args['fingerprint'],
        'ip_address' => ! empty( $args['ip_address'] ) ? $args['ip_address'] : null,
        'language' => ! empty( $args['language'] ) ? $args['language'] : null,
        'ref_url' => ! empty( $args['ref_url'] ) ? $args['ref_url'] : null,
        'ref_domain' => ! empty( $args['ref_domain'] ) ? $args['ref_domain'] : null,
        'os' => ! empty( $args['os'] ) ? $args['os'] : null,
        'device' => ! empty( $args['device'] ) ? $args['device'] : null,
        'browser' => ! empty( $args['browser'] ) ? $args['browser'] : null,
        'http_user_agent' => ! empty( $args['http_user_agent'] ) ? $args['http_user_agent'] : null,
        'country_code' => ! empty( $args['country_code'] ) ? $args['country_code'] : null,
        'city' => ! empty( $args['city'] ) ? $args['city'] : null,
    ] );

    $values['time'] = gmdate('Y-m-d H:i:s');

    // Insert visit to db.
    $wpdb->insert( $wpdb->prefix.'mylisting_visits', $values );
}

//Get Visits

function get_views($request ) {
    $visits_class_base = new \MyListing\Ext\Visits\Visits();

    $parameters = $request->get_params();
    $post_id = $parameters['listing_id'];

    global $wpdb;

    $args = [
        'listing_id' => $post_id,
        'user_id' => false,
        'unique' => false,
    ];

    $sql = [];

    if ( $args['unique'] ) {
        $sql[] = "SELECT COUNT( DISTINCT( {$wpdb->prefix}mylisting_visits.fingerprint ) ) AS count";
    } else {
        $sql[] = "SELECT COUNT( {$wpdb->prefix}mylisting_visits.id ) AS count";
    }

    $sql[] = "FROM {$wpdb->prefix}mylisting_visits";
    $sql[] = "INNER JOIN {$wpdb->posts} ON ( {$wpdb->posts}.ID = {$wpdb->prefix}mylisting_visits.listing_id )";
    $sql[] = "WHERE {$wpdb->posts}.post_status = 'publish'";

    $sql = $visits_class_base->_apply_query_rules($sql, $args);
    $sql = join( "\n", $sql );

    $query = $wpdb->get_row( $sql, OBJECT );

    $data = is_object( $query ) && ! empty( $query->count ) ? (int) $query->count : 0;
    $response = rest_ensure_response( $data );

    return $response;
}


//Get Visits
function get_visits($post_id ) {
    
    global $wpdb;
    $visits_class_base = new \MyListing\Ext\Visits\Visits();

    $args = [
        'listing_id' => $post_id,
        'user_id' => false,
        'unique' => false,
    ];

    $sql = [];

    if ( $args['unique'] ) {
        $sql[] = "SELECT COUNT( DISTINCT( {$wpdb->prefix}mylisting_visits.fingerprint ) ) AS count";
    } else {
        $sql[] = "SELECT COUNT( {$wpdb->prefix}mylisting_visits.id ) AS count";
    }

    $sql[] = "FROM {$wpdb->prefix}mylisting_visits";
    $sql[] = "INNER JOIN {$wpdb->posts} ON ( {$wpdb->posts}.ID = {$wpdb->prefix}mylisting_visits.listing_id )";
    $sql[] = "WHERE {$wpdb->posts}.post_status = 'publish'";

    $sql = $visits_class_base-> _apply_query_rules( $sql, $args );
    $sql = join( "\n", $sql );

    $query = $wpdb->get_row( $sql, OBJECT );

    return is_object( $query ) && ! empty( $query->count ) ? (int) $query->count : 0;
    //$response = rest_ensure_response( $data );
}


//Woocommerce product rest
//custom REST 
function shop_filter($request) {
  
	$params = $request->get_params();
    $category  = $params['category'];
    $filters  = $params['filter'];
  	$page  = $params['page'];
    $search  = $params['search'];
  	$orderby = $params['orderby'];
  	$order = $params['order'];
    
    
    
  $args = array(
        'visibility' => 'catalog',
        'post_type'  => array('product'),
        'paged'		=> $page,
        'posts_per_page' => 4,
    //'status'    => 'publish',
    /*'meta_query'     => array( array(
           'key' => 'visibility',
           'value' => array('catalog', 'visible'),
           'compare' => 'IN',
       ) ), */
      

      );
  
 // If filter buy category or attributes.
    if ( ! empty( $category ) || ! empty($filters) ) {
      $args['tax_query']['relation'] = 'AND';

      // Category filter.
      if ( ! empty($category ) ) {
        
        $args['tax_query'][] = [
          'taxonomy' => 'product_cat',
          'field'    => 'term_id',
          'terms'    => [ $category ],
        ];
      }

      //Keyword search
      if ( ! empty( $search ) ) {
          $args['s'] = $search;
      }
      
      if ( ! empty( $orderby ) ) {
          $args['orderby'] = $orderby;
          $args['order'] = $order;
      }

      // Attributes filter.
      if ( ! empty( $filters ) ) {
        
        foreach ( $filters as $filter_key => $filter_value ) {
          if ( $filter_key === 'min_price' || $filter_key === 'max_price' ) {
            continue;
          }

          if($filter_key==='pa_man_country' || $filter_key === 'pa_brand'){
            $args['tax_query'][] = [
              'taxonomy' => $filter_key,
              'field'    => 'name',
              'terms'    => \explode( ',', $filter_value ),
            ];
          }

          $args['tax_query'][] = [
            'taxonomy' => $filter_key,
            'field'    => 'term_id',
            'terms'    => \explode( ',', $filter_value ),
          ];
        }
      }

      // Min / Max price filter.
      if ( isset( $filters['min_price'] ) || isset( $filters['max_price'] ) ) {
        $price_request = [];

        if ( isset( $filters['min_price'] ) ) {
          $price_request['min_price'] = $filters['min_price'];
        }

        if ( isset( $filters['max_price'] ) ) {
          $price_request['max_price'] = $filters['max_price'];
        }

        $args['meta_query'][] = \wc_get_min_max_price_meta_query( $price_request );
      }
    }
 	
	$query = new WP_Query($args);
    $pdts = $query->get_posts();
    //$pdts = get_posts($args);
    if (empty($pdts)) {
    return new WP_Error( 'no_product', 'No product matching your filters', array('status' => 200) );

    }
    
  	$pdtList = array();
  
  	foreach($pdts as $pdt){
      $_product = wc_get_product( $pdt-> ID );
      $pdtObj = $_product->get_data();
      $images = get_images( $_product );
      $attributes = get_attributes($_product);
      $pdtObj['images'] = $images;
      $pdtObj['attributes'] = $attributes; 
      $pdtList[] = $pdtObj;
      
    }

    $response = new WP_REST_Response($pdtList);
    $response->set_status(200);
  
  	$total = $query->found_posts; 	
    $pages = $query->max_num_pages;   	 	
    $response->header( 'X-WP-Total', $total ); 	
    $response->header( 'X-WP-TotalPages', $pages );  	

    return $response;
}

//Rest Hooks

//logged user authentication

function custom_bp_loggedin_user_id($id) {

  $id = !empty( get_current_user_id() )
      ? get_current_user_id()
      : 0;

  return $id;
}

add_filter( 'bp_loggedin_user_id', 'custom_bp_loggedin_user_id', 11, 1);

//(1) Messages

function custom_get_messages_permission( $retval, $request ) {

    $retval = true;

    if ( ! is_user_logged_in() ) {
        $retval = new WP_Error(
            'bp_rest_authorization_required',
            __( 'Sorry, you have to login to see messages.', 'buddyboss' ),
            array(
                'status' => rest_authorization_required_code(),
            )
        );
    }

    $user = bp_rest_get_user( $request['user_id'] );

    if ( true === $retval && ! $user instanceof WP_User ) {
        $retval = new WP_Error(
            'bp_rest_invalid_id',
            __( 'Invalid member ID.', 'buddyboss' ),
            array(
                'status' => 404,
            )
        );
    }

    if ( true === $retval && (int) get_current_user_id() !== $user->ID && ! bp_current_user_can( 'bp_moderate' ) ) {
        $retval = new WP_Error(
            'bp_rest_authorization_required',
            __( 'Sorry, you cannot view the messages in this chat.', 'buddyboss' ),
            array(
                'status' => rest_authorization_required_code(),
            )
        );
    }

    return $retval;
}

add_filter( 'bp_rest_messages_get_items_permissions_check', 'custom_get_messages_permission', 11, 2 );


//Single thread permission
function custom_single_thread_permission($retval, $request ) {
  $retval = true;

  if ( ! is_user_logged_in() ) {
      $retval = new WP_Error(
          'bp_rest_authorization_required',
          __( 'Sorry, you have to be logged in to see the chat.', 'buddyboss' ),
          array(
              'status' => rest_authorization_required_code(),
          )
      );
  }

  $endpoint = new BP_REST_Messages_Endpoint();
  $thread = $endpoint->get_thread_object( $request['id'] );

  if ( true === $retval && empty( $thread->thread_id ) ) {
      $retval = new WP_Error(
          'bp_rest_invalid_id',
          __( 'Sorry, this thread does not exist.', 'buddyboss' ),
          array(
              'status' => 404,
          )
      );
  }

  if ( true === $retval && bp_current_user_can( 'bp_moderate' ) ) {
      $retval = true;
  } else {
      $id = messages_check_thread_access( $thread->thread_id, get_current_user_id() );
      if ( true === $retval && is_null( $id ) ) {
          $retval = new WP_Error(
              'bp_rest_authorization_required',
              __( 'Sorry, you are not allowed to see this particular thread.', 'buddyboss' ),
              array(
                  'status' => rest_authorization_required_code(),
              )
          );
      }

      if ( true === $retval ) {
          $retval = true;
      }
  }

  return $retval;
}

add_filter( 'bp_rest_messages_get_item_permissions_check', 'custom_single_thread_permission', 11, 2 );

//Rest Cache


function custom_api_cache( $allowed_endpoints ) {
  if ( ! in_array( 'listings', $allowed_endpoints[ 'wp/v2' ] ) ) {
      $allowed_endpoints[ 'wp/v2' ][] = 'listings';
  }
  return $allowed_endpoints;
}
add_filter( 'wp_rest_cache/allowed_endpoints', 'custom_api_cache', 10, 1);


//listing products

function acf_pdt_edit( $value, $post_id, $field  ) {
	// vars
	$field_name = $field['name'];
	$global_name = 'is_updating_' . $field_name;
	
	// - this prevents an inifinte loop
	if( !empty($GLOBALS[ $global_name ]) ) return $value;
	
	// set global variable to avoid inifite loop
	$GLOBALS[ $global_name ] = 1;
	
	update_post_meta( $post_id, '_'.$field_name, $value);
	
	// reset global varibale to allow this filter to function as per normal
	$GLOBALS[ $global_name ] = 0;
	
	// return
    return $value;
    
}

$acf_pdt_fields = array('regular_price','price','stock');
foreach ($acf_pdt_fields as  $field) {
   add_filter('acf/update_value/name='.$field, 'acf_pdt_edit', 10, 3);
}


function listing_pdts_edit( $value, $post_id, $field  ) {
	// vars
	$field_name = $field['name'];
	$field_key = $field['key'];
	$global_name = 'is_updating_' . $field_name;
	
	// - this prevents an inifinte loop
	if( !empty($GLOBALS[ $global_name ]) ) return $value;
	
	// set global variable to avoid inifite loop
	$GLOBALS[ $global_name ] = 1;
  $meta_arr = array(); 

  //$listingId = intval(get_post_meta($post_id, 'listing', true)[0]);
  $listing_meta = get_post_meta($post_id);
  
  $meta_arr['id'] = intval($post_id);
  $meta_arr['title'] = get_the_title($post_id);
  $meta_arr['slug'] = get_post_field( 'post_name', $post_id );
  $meta_arr['phone'] = $listing_meta['_job_phone'][0];
  $meta_arr['cover'] = $listing_meta['listing_cover'];
  $meta_arr['logo'] = $listing_meta['listing_logo'][0];
  $meta_arr['whatsapp'] = $listing_meta['_whatsapp-number'][0];
  $meta_arr['type'] = $listing_meta['_case27_listing_type'][0]; 
	
	// loop over selected posts and add this $post_id
	if( is_array($value) ) {

    foreach($value as $pdt_id) {

    //$pdt_id = $value[0];
      update_post_meta( $pdt_id, 'listing_data', $meta_arr);
    }
	
	}
	
	
	// find posts which have been removed
	$old_value = get_field($field_name, $post_id, false);
	
	if( is_array($old_value) ) {
		
		foreach( $old_value as $post_id2 ) {
			
			// bail early if this value has not been removed
			if( is_array($value) && in_array($post_id2, $value) ) continue;
			
			// load existing related posts
			//$value2 = get_field($field_name, $post_id2, false);
			
			// bail early if no value
			//if( empty($value2) ) continue;
			
			// find the position of $post_id within $value2 so we can remove it
		 	//$pos = array_search($post_id, $value2);
			
			// remove
	
        update_post_meta( $post_id2, 'listing_data', '');
			
			// update the un-selected post's value (use field's key for performance)
			//update_field($field_key, $value2, $post_id2);
			
		}
		
	}
	
	
	// reset global varibale to allow this filter to function as per normal
	$GLOBALS[ $global_name ] = 0;
	
	
	// return
    return $value;
    
}

add_filter('acf/update_value/key=field_64cbdd20462a9', 'listing_pdts_edit', 10, 3);
add_filter('acf/update_value/key=field_64cbcdca213b0', 'listing_pdts_edit', 10, 3);

add_filter( 'mylisting\links-list', function( $links ) {
  // Add new link
  $links['Ticktok'] = [
      'name' => 'Ticktok',
      'key' => 'Ticktok',
      'icon' => 'fab fa-tiktok',
      'color' => '#128c7e',
  ];
  
  // Remove a link
  unset( $links['Pinterest'] );
  unset( $links['DeviantArt'] );
  return $links;
} );


add_filter( 'mylisting/listing-types/register-fields', function( $fields ) {
  // Add new link
  $fields[] = \MyListing\Src\Forms\Fields\Team_Members_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Event_Program_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Image_Links_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Features_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Repeater_Text_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Statistics_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Acf_Field_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Repeater_Section_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Faqs_Field::class;
  $fields[] = \MyListing\Src\Forms\Fields\Color_Select_Field::class;
  $fields[] = \MyListing\Src\Listing_Types\Content_Blocks\General_Repeater_Block::class;
  
  return $fields;
} );

//Prdouct discount Percentage

add_action('woocommerce_process_product_meta', 'woo_calc_my_discount');
add_action( 'woocommerce_new_product', 'woo_calc_my_discount', 10, 1 );
add_action( 'woocommerce_update_product', 'woo_calc_my_discount', 10, 1 );

function woo_calc_my_discount( $product_id ) {
    $discount = 0;

    $_product = wc_get_product( $product_id );

    $regular = (float) $_product->get_regular_price();

    $sale = (float) $_product->get_sale_price();
    
    //var_dump($sale, $regular);
    if($sale > 0 ){
        if($regular > 0){
             $discount = round( 100 - ( $sale / $regular * 100));
        }
    }

    update_post_meta( $product_id, '_discount_percentage', intval($discount));

}

add_action('woocommerce_product_quick_edit_save', 'sv_woo_calc_my_discount_quickedit');
function sv_woo_calc_my_discount_quickedit( $post ) {

    $_product = wc_get_product( $post );

    $regular = (float) $_product->get_regular_price();

    $sale = (float) $_product->get_sale_price();

    if($sale > 0){
        $discount = round( 100 - ( $sale / $regular * 100));
    }

    update_post_meta( $_product -> get_id(), '_discount_percentage', intval($discount) );

}

//Create vendor category
add_action('save_post_product', 'update_vendor_categories', 10, 1);
function update_vendor_categories($post_id)
{

  remove_action('save_post_product', 'update_vendor_categories');

    $product = wc_get_product($post_id);
    $post_obj = get_post($post_id);
    $author_id = $post_obj->post_author;
    $cat_ids = $product->get_category_ids();
    if (!empty($author_id)) {
        $new_list = null;
        $vendor_categories = get_user_meta($author_id, 'store_categories', true);
        if ($vendor_categories) {
            $new_list = array_unique(array_merge($vendor_categories, $cat_ids));
            update_user_meta($author_id, 'store_categories', $new_list);
        } else {
            $new_list = $cat_ids;
            add_user_meta($author_id, 'store_categories', $new_list);
        }
    }
    add_action('save_post_product', 'update_vendor_categories');
}

//clean vendors
function clean_vendors()
{
    $args = array(
        'role' => ['wcfm_vendor','seller'],
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ID'
    );
    $vendors = get_users($args);
    foreach ($vendors as $vendor) {
        clean_vendor_categories($vendor);
    }
}


//clean vendor categories
function clean_vendor_categories($vendor_id)
{
    $vendor_categories = get_user_meta($vendor_id, 'store_categories', true);
    //$cat_ids = $product->get_category_ids();
    if (!empty($vendor_id)) {
        $new_list = null;
        $vendor_categories = get_user_meta($vendor_id, 'store_categories', true);
        if ($vendor_categories) {
            foreach ($vendor_categories as $key => $val) {
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => '1',
                    'author' => $vendor_id,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'term_id',
                            //This is optional, as it defaults to 'term_id'
                            'terms' => $val,
                            'operator' => 'IN' // Possible values are 'IN', 'NOT IN', 'AND'.
                        ),
                    )
                );
                $products = new WP_Query($args);
                if ($products->have_posts()) {
                    return;
                } else {
                    unset($vendor_categories[$key]); // remove item at index 0
                    $leaner_arr = array_values($vendor_categories); //
                    update_user_meta($vendor_id, 'store_categories', $leaner_arr);
                }
            }
        } else {
            return;
        }

    }
}

//Get vendor info
function vendor_info($request)
{
    $params = $request->get_params();
    $user_id = intval($params['id']);

    $vendor_cats = get_user_meta($user_id, 'store_categories', true);
    $meta_arr['store_categories'] = $vendor_cats;

    $response = new WP_REST_Response($meta_arr);
    $response->set_status(200);

    return $response;
}

//Theme rest query


function get_ordering_clauses( &$args, $type, $form_data ) {
    $query_base_class = new \MyListing\Src\Queries\Query();

    $options = $type ? (array) $type->get_ordering_options() : [];
    $sortby  = ! empty( $form_data['sort'] ) ? sanitize_text_field( $form_data['sort'] ) : false;
    if ( empty( $options ) ) {
        return false;
    }

    // default to the first ordering option
    if ( empty( $sortby ) ) {
        $sortby = $options[0]['key'];
    }

    if ( ( $key = array_search( $sortby, array_column( $options, 'key' ) ) ) === false ) {
        return false;
    }

    $option  = $options[$key];
    $clauses = $option['clauses'];
    $orderby = [];

    foreach ( $clauses as $clause ) {
        if ( empty( $clause['context'] ) || empty( $clause['orderby'] ) || empty( $clause['order'] ) || empty( $clause['type'] ) ) {
            continue;
        }

        $clause_hash = substr( md5( json_encode( $clause ) ), 0, 16 );
        $clause_id = sprintf( 'clause-%s-%s', $option['key'], $clause_hash );

        if ( $clause['context'] === 'option' ) {
            if ( $clause['orderby'] === 'rand' ) {
                // Randomize every 3 hours.
                $seed = apply_filters( 'mylisting/explore/rand/seed', floor( time() / 10800 ) );
                $orderby[ "RAND({$seed})" ] = $clause['order'];
            } elseif ( $clause['orderby'] === 'rating' ) {
                add_filter( 'posts_join', [ $query_base_class, 'rating_field_join'], 35, 2 );
                add_filter( 'posts_orderby', [ $query_base_class, 'rating_field_orderby'], 35, 2 );
                $args['mylisting_orderby_rating'] = $clause['order']; // Note the custom order to $args, so it's cached properly.
                $orderby[ $clause_id ] = []; // Add a dummy orderby, to override the default one.
            } elseif ( $clause['orderby'] === 'proximity' ) {
                $orderby = 'post__in';

                add_filter( 'mylisting/explore/args', function( $args ) use ( $clause ) {
                    // Support descending order for distance/proximity.
                    if ( $clause['order'] === 'DESC' && ! empty( $args['post__in'] ) ) {
                        $args['post__in'] = array_reverse( $args['post__in'] );
                    }

                    return $args;
                } );
            } elseif ( $clause['orderby'] === 'relevance' ) {
                // order by relevance only works when there's a single 'orderby' clause,
                // and if that's passed as a string instead of an array
                $orderby = 'relevance';
            } else {
                $orderby[ $clause['orderby'] ] = $clause['order'];
            }
        }

        if ( $clause['context'] === 'meta_key' ) {
            $field = $type->get_field( $clause['orderby'] );

            if ( $field && $field->get_type() === 'recurring-date' ) {

                // if a recurring date filter isn't present, join the events table
                // and filter listings with the start date set to the current date
                // so that only future events are shown by default
                $args['recurring_dates'][ $field->get_key() ] = [
                    'start' => date('Y-m-d H:i:s', current_time('timestamp')),
                    'end' => '',
                    'orderby' => true,
                    'order' => $clause['order'],
                    'where_clause' => false,
                ];

                $orderby[ $clause_id ] = []; // Add a dummy orderby, to override the default one.
            } else {
                $args['meta_query'][ $clause_id ] = [
                    'key' => '_' . $clause['orderby'],
                    'compare' => 'EXISTS',
                    'type' => $clause['type'],
                ];

                $orderby[ $clause_id ] = $clause['order'];
            }
        }

        if ( $clause['context'] === 'raw_meta_key' ) {
            $args['meta_query'][ $clause_id ] = [
                'key' => $clause['orderby'],
                'compare' => 'EXISTS',
                'type' => $clause['type'],
            ];

            $orderby[ $clause_id ] = $clause['order'];
        }
    }

    if ( ! empty( $orderby ) ) {
        $args['orderby'] = $orderby;

        if ( isset( $args['order'] ) ) {
            unset( $args['order'] );
        }

        // Ignore order by priority if set.
        if ( ! empty( $option['ignore_priority'] ) ) {
            $args['mylisting_ignore_priority'] = true;
            add_filter( 'mylisting/explore/listing-wrap', function( $wrap ) {
                $wrap .= ' hide-priority';
                return $wrap;
            } );
        }
    }

    // dd($clauses, $option);
    // dd($args, $orderby);
}


function get_listings_query($request) {
    global $wpdb;
    // handle find listings using explore page query url
    $params = $request->get_params();

    $sort = $params['sort'] ?? null;
    $author = isset($params['author']) ? $params['author'] : null;
    $include_ids = $params['include_ids'] ?? null;
    $exclude_ids = $params['exclude'] ?? null;
    $listing_type_obj = array_key_exists('listing_type', $params)  ? ( get_page_by_path( $params['listing_type'], OBJECT, 'case27_listing_type' ) ) : null;
    $type = $listing_type_obj ? new \MyListing\Src\Listing_Type( $listing_type_obj ) : null;
    $page = absint( isset($params['page']) ? $params['page'] - 1 : 0 );

    //$meta_q = [];
		$per_page = absint( isset($params['per_page']) ? $params['per_page'] : c27()->get_setting('general_explore_listings_per_page', 9));
		$orderby = sanitize_text_field( isset($params['orderby']) ? $params['orderby'] : 'date' );
    $order = sanitize_text_field(isset($params['order']) ? $params['order'] : 'DESC');
		$context = sanitize_text_field( isset( $params['context'] ) ? $params['context'] : 'advanced-search' );

		$args = [
			'order' => $order,
			'offset' => $page * $per_page,
			'orderby' => array( $orderby => $order),
			'posts_per_page' => $per_page,
			'tax_query' => [],
			'meta_query' => [],
      'search_keywords' => $params['search_keywords'] ?? '',
      'mylisting_ignore_priority' => $params['ignore_priority'] ?? false
			//'fields' =>  $params['ids'] ? 'ids' : 'all',
      //'fields' =>  'ids',
			//'event-date' => isset($params['event-date']) ? $params['event-date'] : 'any-day',
      //'recurring_dates' => []
		];

    if (isset( $author ) ) {
			$args['author'] = intval($author);
		}
    if (isset( $include_ids ) ) {
			$args['post__in'] = explode(',', $include_ids);
		}
    if ($exclude_ids ) {
			$args['post__not_in'] = explode(',', $exclude_ids);;
		}

    if(isset($sort)){
      if($sort === 'top-rated'){
        $args['meta_key'] = 'user_rating';
        $args['orderby'] = 'meta_value_num';
        $args['order'] = 'DESC';
      }
    }

		get_ordering_clauses( $args, $type, $params );

		// Make sure we're only querying listings of the requested listing type.
		if ($type && ! $type->is_global() ) {
			$args['meta_query']['listing_type_query'] = [
				'key'     => '_case27_listing_type',
				'value'   =>  $type->get_slug(),
				'compare' => '='
			];
		}

    //category
    if(isset($params['category'])){
      $category  = $params['category'];	

      $args['tax_query'][] = [
				'taxonomy' => 'job_listing_category',
				'field' => 'slug',
				'terms' => array( $category),
				'operator' => 'IN',
				'include_children' => true,			
			];
    }

     // Location filter.
     if (isset($params['region'] ) ) {
      $location  = $params['region'];
      $args['tax_query'][] = [
        'taxonomy' => 'region',
        'field'    => 'slug',
        'operator' => 'IN',
        'terms' => array( $location ),
        'include_children' => true
      ];
    }
    
      // Tag filter.
    if (isset($params['tags']) ) {
      $tags  = $params['tags'];	
      $args['tax_query'][] = [
        'taxonomy' => 'case27_job_listing_tags',
        'field'    => 'slug',
        'terms' => array( $tags),
      ];
    }

  // add support for nearby order everywhere
    if ( isset( $params['proximity'], $params['lat'], $params['lng'] ) ) {
      $proximity = absint( $params['proximity'] );
      //$location = isset( $params['search_location'] ) ? sanitize_text_field( stripslashes( $params['search_location'] ) ) : false;
      $lat = (float) $params['lat'];
      $lng = (float) $params['lng'];
      $units = isset($params['proximity_units']) && $params['proximity_units'] == 'mi' ? 'mi' : 'km';
      if ( $lat && $lng && $proximity /* && $location */ ) {
        $earth_radius = $units == 'mi' ? 3959 : 6371;
        $sql = $wpdb->prepare( \MyListing\Helpers::get_proximity_sql(), $earth_radius, $lat, $lng, $lat, $proximity );
        $post_ids = (array) $wpdb->get_results( $sql, OBJECT_K );
        if ( empty( $post_ids ) ) { $post_ids = ['none']; }
        $args['post__in'] = array_keys( (array) $post_ids );
        $args['search_location'] = '';
      }
    }

		if ( $context === 'term-search' ) {
			$taxonomy = ! empty( $params['taxonomy'] ) ? sanitize_text_field( $params['taxonomy'] ) : false;
			$term = ! empty( $params['term'] ) ? sanitize_text_field( $params['term'] ) : false;

			if ( ! $taxonomy || ! $term || ! taxonomy_exists( $taxonomy ) ) {
				return false;
			}

			$tax_query_operator = apply_filters( 'mylisting/explore/match-all-terms', false ) === true ? 'AND' : 'IN';
			$args['tax_query'][] = [
				'taxonomy' => $taxonomy,
				'field' => 'slug',
				'terms' => $term,
				'operator' => $tax_query_operator,
				//'include_children' => $tax_query_operator !== 'AND',
        'include_children' => true,			
			];

			// add support for nearby order in single term page
			if ( isset( $params['proximity'], $params['lat'], $params['lng'] ) ) {
				$proximity = absint( $params['proximity'] );
				$location = isset( $params['search_location'] ) ? sanitize_text_field( stripslashes( $params['search_location'] ) ) : false;
				$lat = (float) $params['lat'];
				$lng = (float) $params['lng'];
				$units = isset($params['proximity_units']) && $params['proximity_units'] == 'mi' ? 'mi' : 'km';
				if ( $lat && $lng && $proximity && $location ) {
					$earth_radius = $units == 'mi' ? 3959 : 6371;
					$sql = $wpdb->prepare( \MyListing\Helpers::get_proximity_sql(), $earth_radius, $lat, $lng, $lat, $proximity );
					$post_ids = (array) $wpdb->get_results( $sql, OBJECT_K );
					if ( empty( $post_ids ) ) { $post_ids = ['none']; }
					$args['post__in'] = array_keys( (array) $post_ids );
					$args['search_location'] = '';
				}
			}
		} else {
            if($type){
			foreach ( (array) $type->get_advanced_filters() as $filter ) {
				$args = $filter->apply_to_query( $args, $params );
			}
        }
		}

		$result = [];
		$listing_wrap = ! empty( $params['listing_wrap'] ) ? sanitize_text_field( $params['listing_wrap'] ) : '';
		$listing_wrap = apply_filters( 'mylisting/explore/listing-wrap', $listing_wrap );

		/**
		 * Hook after the search args have been set, but before the query is executed.
		 *
		 * @since 1.7.0
		 */
    //var_dump($args);
		do_action_ref_array( 'mylisting/get-listings/before-query', [ &$args, $type, $result ] );

   if(isset($params['_fields'])){
    $params['_fields'] = $params['_fields'];
    //$request->set_query_params($params);
   }

    $listings = directory_query_args($args, $request);
    
		return $listings;
}

//User Reviews
function rest_submit_reviews( $request ) {
  $base_class = new \Jet_Reviews\Endpoints\Submit_Review();
  $reviewsData = new \Jet_Reviews\Reviews\Data();

  $args = $request->get_params();

  $allowed_html = jet_reviews_tools()->get_content_allowed_html();

  $source        = isset( $args[ 'source' ] ) ? $args[ 'source' ] : 'post';
  $source_id     = isset( $args[ 'source_id' ] ) ? $args[ 'source_id' ] : false;
  $title         = isset( $args[ 'title' ] ) ? wp_kses( $args[ 'title' ], 'strip' ) : '';
  $content       = isset( $args[ 'content' ] ) ? wp_kses( $args[ 'content' ], $allowed_html ) : '';
  $author_id     = isset( $args[ 'author_id' ] ) ? $args[ 'author_id' ] : '0';
  $author_name   = isset( $args[ 'author_name' ] ) ? wp_kses( $args[ 'author_name' ], 'strip' ) : '';
  $author_mail   = isset( $args[ 'author_mail' ] ) ? sanitize_email( $args[ 'author_mail' ] ) : '';
  $rating_data   = isset( $args[ 'rating_data' ] ) ? json_decode($args[ 'rating_data' ]) : [];
  $captcha_token = isset( $args[ 'captcha_token' ] ) ? $args[ 'captcha_token' ] : '';

  if ( jet_reviews_tools()->is_demo_mode() ) {
    return rest_ensure_response( array (
      'success' => false,
      'code'    => 'demo-mode',
      'message' => __( 'You can\'t leave a review. Demo mode is active', 'jet-reviews' ),
      'data'    => [],
    ) );
  }

  $recaptcha_instance = jet_reviews()->integration_manager->get_integration_module_instance( 'recaptcha' );
  $captcha_verify     = $recaptcha_instance->maybe_verify( $captcha_token );

  if ( ! $captcha_verify ) {
    return rest_ensure_response( array (
      'success' => false,
      'code'    => 'captcha-failed',
      'message' => __( 'Captcha validation failed', 'jet-reviews' ),
      'data'    => [],
    ) );
  }

  $source_instance = jet_reviews()->reviews_manager->sources->get_source_instance( $source );
  $source_type     = $source_instance->get_type( [
    'source_id' => $source_id,
  ] );
  $source_settings = jet_reviews()->settings->get_source_settings_data( $source_instance->get_slug(), $source_type );

  $rating   = $base_class->calculate_rating( $rating_data );
  $is_guest = false === strpos( $author_id, 'guest' ) ? false : true;

  $prepared_data = array (
    'source'      => $source,
    'post_id'     => $source_id,
    'post_type'   => $source_type,
    'author'      => $author_id,
    'date'        => current_time( 'mysql' ),
    'title'       => $title,
    'content'     => $content,
    'type_slug'   => $source_settings[ 'review_type' ],
    'rating_data' => maybe_serialize( $rating_data ),
    'rating'      => $rating,
    'approved'    => filter_var( $source_settings[ 'need_approve' ], FILTER_VALIDATE_BOOLEAN ) ? 0 : 1,
    'pinned'      => 0,
  );

  $insert_data = $reviewsData::get_instance()->add_new_review( $prepared_data );

  if ( ! $insert_data ) {
    return rest_ensure_response( array (
      'success' => false,
      'code'    => 'db-error',
      'message' => __( 'DataBase Error', 'jet-reviews' ),
      'data'    => $rating_data,
    ) );
  }

  $insert_id = $insert_data[ 'insert_id' ];

  if ( $is_guest ) {
    $prepared_guest_data = array (
      'guest_id' => $author_id,
      'name'     => $author_name,
      'mail'     => $author_mail,
    );

    $insert_guest_id = jet_reviews()->user_manager->add_new_guest( $prepared_guest_data );
  }

  $review_list = get_user_meta( $author_id, 'reviewed_list' , true );
  if (empty( $review_list )) {
    $tempArr = array();
    $tempArr[] = $source_id;
    add_user_meta( $author_id, 'reviewed_list', $tempArr);
  }else{
    if(is_array($review_list)){
      if (!in_array($source_id, $review_list)){
        $review_list[] = $source_id;
        update_user_meta( $author_id, 'reviewed_list', $review_list);
      }
    }
  }

  /**
   * Maybe update average rating post meta field
   */
  if ( filter_var( $source_settings[ 'metadata' ], FILTER_VALIDATE_BOOLEAN ) ) {
    $base_class->maybe_update_rating_metadata( $source_id, $source_settings[ 'metadata_rating_key' ], $insert_data[ 'rating' ], $source_settings[ 'metadata_ratio_bound' ] );
  }

  /**
   * Check if nessesary moderator approving
   */
  if ( filter_var( $source_settings[ 'need_approve' ], FILTER_VALIDATE_BOOLEAN ) ) {
    return rest_ensure_response( array (
      'success' => true,
      'code'    => 'need-approve',
      'message' => __( '*Your review must be approved by the moderator', 'jet-reviews' ),
      'data'    => [],
    ) );
  }

  $author_data = jet_reviews()->user_manager->get_raw_user_data( $author_id );

  $review_verification_data = jet_reviews()->user_manager->get_verification_data( $source_settings[ 'verifications' ], array (
    'user_id' => $author_data[ 'id' ],
    'post_id' => $source_id,
  ) );

  $return_data = array (
    'id'            => $insert_id,
    'source'        => $source,
    'source_type'   => $source_type,
    'author'        => array (
      'id'     => $author_data[ 'id' ],
      'name'   => $author_data[ 'name' ],
      'mail'   => $author_data[ 'mail' ],
      'avatar' => $author_data[ 'avatar' ],
      'roles'  => $author_data[ 'roles' ],
    ),
    'date'          => array (
      'raw'        => $prepared_data[ 'date' ],
      'human_diff' => jet_reviews_tools()->human_time_diff_by_date( $prepared_data[ 'date' ] ),
    ),
    'title'         => $title,
    'content'       => $content,
    'type_slug'     => $prepared_data[ 'type_slug' ],
    'rating_data'   => $rating_data,
    'rating'        => $rating,
    'comments'      => array (),
    'approved'      => $source_settings[ 'need_approve' ],
    'like'          => 0,
    'dislike'       => 0,
    'approval'      => jet_reviews()->user_manager->get_review_approval_data( $insert_id ),
    'pinned'        => false,
    'verifications' => $review_verification_data,
  );

  return rest_ensure_response( array (
    'success' => true,
    'message' => __( '*Already reviewed', 'jet-reviews' ),
    'code'    => 'already-created',
    'data'    => array (
      'item'   => $return_data,
      'rating' => $insert_data[ 'rating' ],
      'user_meta'=> $review_list
    ),
  ) );
}

function rest_delete_review( $request ) {
 // $delete_class = new \Jet_Reviews\Endpoints\Delete_Review();
  $reviewsData = new \Jet_Reviews\Reviews\Data();


  $args = $request->get_params();

  $author_id = $args['author'];
  $post_id = isset( $args['post_id'] ) && ! empty( $args['post_id'] ) ? $args['post_id'] : false;
  $review_id = isset( $args['id'] ) && ! empty( $args['id'] ) ? $args['id'] : false;

  if ( ! $review_id ) {
    return rest_ensure_response( array(
      'success' => false,
      'message' => __( 'Error', 'jet-reviews' ),
    ) );
  }

  jet_reviews()->user_manager->delete_user_approval_review( $review_id );

  $delete_review = $reviewsData::get_instance()->delete_review_by_id( $review_id );

  if ( 0 === $delete_review ) {
    return rest_ensure_response( array(
      'success' => false,
      'message' => __( 'The review has not been deleted', 'jet-reviews' ),
    ) );
  }

  $review_list = get_user_meta( $author_id, 'reviewed_list' , true );

  if (!empty( $review_list )) {
    if(is_array($review_list)){
      if (in_array($post_id, $review_list)){
        $review_list = array_diff($review_list, array($post_id));
        update_user_meta( $author_id, 'reviewed_list', $review_list);
      }
    }
  }

  return rest_ensure_response( array(
    'success'  => true,
    'message' => __( 'The review have been deleted', 'jet-reviews' ),
    'data'    => array (
    'user_meta'=> $review_list
    ),
  ) );
}

//WC Bookings Rest api
if(class_exists('WC_Bookings_REST_Booking_Controller')){
class Bookings_REST_Booking_Controller extends WC_Bookings_REST_Booking_Controller {

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'rest_bookings';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'wc_booking';
  

  /**
	 * Prepare objects query.
	 *
	 * @since  3.0.0
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array
	 */
	protected function prepare_objects_query( $request ) {
		$args                        = array();
		$args['offset']              = $request['offset'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
    $args['author']             = $request['author'];
		$args['paged']               = $request['page'];
		$args['post__in']            = $request['include'];
		$args['post__not_in']        = $request['exclude'];
		$args['posts_per_page']      = $request['per_page'];
		$args['name']                = $request['slug'];
		$args['post_parent__in']     = $request['parent'];
		$args['post_parent__not_in'] = $request['parent_exclude'];
		$args['s']                   = $request['search'];
		$args['fields']              = $this->get_fields_for_response( $request );

		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date ID';
		}

		$date_query = array();
		$use_gmt    = $request['dates_are_gmt'];

    if (isset($request['customer_id'] )) {
      $cus_id = $request['customer_id'];
			$args['meta_query'] = array(
        array(
        'key' => '_booking_customer_id',
        'value' => $cus_id,
        'compare' => '=',
        ),
      );
		}

		if ( isset( $request['before'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_date_gmt' : 'post_date',
				'before' => $request['before'],
			);
		}

		if ( isset( $request['after'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_date_gmt' : 'post_date',
				'after'  => $request['after'],
			);
		}

		if ( isset( $request['modified_before'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_modified_gmt' : 'post_modified',
				'before' => $request['modified_before'],
			);
		}

		if ( isset( $request['modified_after'] ) ) {
			$date_query[] = array(
				'column' => $use_gmt ? 'post_modified_gmt' : 'post_modified',
				'after'  => $request['modified_after'],
			);
		}

		if ( ! empty( $date_query ) ) {
			$date_query['relation'] = 'AND';
			$args['date_query']     = $date_query;
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		/**
		 * Filter the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post
		 * collection request.
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args = apply_filters( "woocommerce_rest_{$this->post_type}_object_query", $args, $request );

		return $this->prepare_items_query( $args, $request );
	}

  /**
	 * Prepare a single product output for response.
	 *
	 * @param WC_Booking      $object  Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

    $pdt_id = $object->get_product_id( $context );

		$data = array(
			'id'                       => $object->get_id(),
			'all_day'                  => $object->get_all_day( $context ),
			'cost'                     => $object->get_cost( $context ),
			'customer_id'              => $object->get_customer_id( $context ),
			'date_created'             => $object->get_date_created( $context ),
			'date_modified'            => $object->get_date_modified( $context ),
			'end'                      => $object->get_end( $context ),
			'google_calendar_event_id' => $object->get_google_calendar_event_id( $context ),
			'order_id'                 => $object->get_order_id( $context ),
			'order_item_id'            => $object->get_order_item_id( $context ),
			'parent_id'                => $object->get_parent_id( $context ),
			'person_counts'            => $object->get_person_counts( $context ),
			'product_id'               => $pdt_id,
      'product_thumb'            => get_the_post_thumbnail_url($pdt_id),
      'product_title'            => get_the_title($pdt_id),
			'resource_id'              => $object->get_resource_id( $context ),
			'start'                    => $object->get_start( $context ),
			'status'                   => $object->get_status( $context ),
			'local_timezone'           => $object->get_local_timezone( $context ),
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );
		$response->add_links( $this->prepare_links( $object, $request ) );

		/**
		 * Filter the data for a response.
		 *
		 * The dynamic portion of the hook name, $this->post_type,
		 * refers to object type being prepared for the response.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WC_Data          $object   Object data.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "woocommerce_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

}
}

//Woocommerce register fields

// Add Billing Fields To Registration Form

// Save Billing Fields At Registration
add_action( 'woocommerce_created_customer', function( $customer_id ) {

	if( isset( $_POST['billing_phone'] ) ) {
		update_user_meta(
			$customer_id, 'billing_phone', sanitize_text_field( $_POST['billing_phone'] )
		);
	}

}, 10, 1 );

  add_action( 'elementor_pro/forms/validation/email', function( $field, $record, $ajax_handler ) {
    // validate email format
    /* if ( ! is_email( $field['value'] ) ) {
      $ajax_handler->add_error( $field['id'], 'Invalid Email address, it must be in the format XX@XX.XX' );
      return;
    } */
  
    $valid_domains = array("gmail.com","yahoo.com","hotmail.com","live.com","aol.com","outlook.com");
  
    $email_domain = explode( '@', $field['value'] )[1];
  
    if ( !in_array( $email_domain, $valid_domains ) ) {
      $ajax_handler->add_error( $field['id'], 'Invalid Email address, emails from the domain ' . $email_domain . ' are not allowed.' );
      return;
    }
  }, 10, 3 );

  //Rename attachments
  // The filter runs when resizing an image to make a thumbnail or intermediate size.
add_filter( 'image_make_intermediate_size', 'wpse_123240_rename_intermediates' );
function wpse_123240_rename_intermediates( $image ) {
    // Split the $image path into directory/extension/name
    $info = pathinfo($image);
    $dir = $info['dirname'] . '/';
    $ext = '.' . $info['extension'];
    $name = wp_basename( $image, "$ext" );

    // Get image information 
    // Image edtor is used for this
    $img = wp_get_image_editor( $image );
    // Get image size, width and height
    $img_size = $img->get_size();

    // Image prefix for builtin sizes
    // Note: beware possible conflict with custom images sizes with the same width
    $widths = [];
    $size_based_img_name_suffix = '';
    foreach ( get_intermediate_image_sizes() as $_size ) {
        if ( in_array( $_size, [ 'thumbnail', 'medium', 'medium_large', 'large', 'big_thumb' ] ) ) {
            $width = get_option( "{$_size}_size_w" );
            if ( ! isset( $widths[ $width ] ) ) {
                $widths[ $width ]  = $_size;
            }
        }
    }
    if ( array_key_exists( $img_size[ 'width' ], $widths ) ) {
        $size_based_img_name_suffix = '-' . $widths[ $img_size['width'] ];
        $name_prefix = substr( $name, 0, strrpos( $name, '-' ) );
    } else {
        $name_prefix = $name;
    }

    // Build our new image name
    $new_name = $dir  . $name_prefix . $size_based_img_name_suffix . $ext;

    // Rename the intermediate size
    $did_it = rename( $image, $new_name );

    // Renaming successful, return new name
    if( $did_it )
        return $new_name;

    return $image;
}

//Modifies ajax requests from our select2 dropdown, $args = WP_Term_Query arguments
function custom_acf_taxonomy_hierarchy( $args, $field, $post_id ){
  //default to 0 (top-level terms only) unless we get a different parent with the request
  $args['parent'] = isset($_POST['parent']) ? intval($_POST['parent']) : 0;
  //$args['parent'] = 701;
  
  //To allow multiple top-level selections, we can go back to the top of the taxonomy tree after we reach the end of a branch
  /*
  if(!empty($args['parent'])){
      $child_terms = get_term_children($args['parent'],'YOUR_TAXONOMY_SLUG'); //change to your taxonomy slug
      if(empty($child_terms) || is_wp_error($child_terms)) $args['parent'] = 0;
  }
  */
  //var_dump($_POST["parent"]);
  return $args;
}
add_filter('acf/fields/related_terms/query/key=field_6658b886e5430', 'custom_acf_taxonomy_hierarchy',10,3);


//Make the javascript available in the admin when editing a post with ACF
function custom_admin_enqueue_scripts(){        
  wp_enqueue_script('custom-acf-admin-js', get_stylesheet_directory_uri() . '/assets/custom-acf-admin.js',['jquery']);
}
add_action('acf/input/admin_enqueue_scripts','custom_admin_enqueue_scripts');

//Remove dokan redirect
remove_filter( 'woocommerce_login_redirect', 'dokan_after_login_redirect', 1, 2 );

function add_media_upload_scripts() {
  if ( is_admin() ) {
       return;
     }
  wp_enqueue_media();
}
add_action('wp_enqueue_scripts', 'add_media_upload_scripts');