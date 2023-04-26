<?php

// Enqueue child theme style.css
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'child-style', get_stylesheet_uri() );

    if ( is_rtl() ) {
    	wp_enqueue_style( 'mylisting-rtl', get_template_directory_uri() . '/rtl.css', [], wp_get_theme()->get('Version') );
    }
}, 500 );

// Happy Coding :)


 function edit_theme_caps() {
  // gets the author role
  $role = get_role( 'subscriber' );

  $role->add_cap( 'list_users' ); 
}
add_action( 'admin_init', 'edit_theme_caps');

//Define Constants

$frontend_link = 'https://lyvecity.vercel.app';

//Image sizes

add_action( 'init', 'custom_theme_setup' );
function custom_theme_setup() {
  $sizes_arr = ['2048x2048', '1536x1536', 'woocommerce_thumbnail', 'shop_catalog', 'woocommerce_gallery_thumbnail', 'woocommerce_single' ];
  foreach($sizes_arr as $size){
  remove_image_size( $size );
  }
  add_image_size( 'big_thumb', 400, 400, false );
}

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

//Edit listing fields
function add_listing_fields($post_id) {
  // If this is a revision, get real post ID
  if ( $parent_id = wp_is_post_revision( $post_id ) ) 
      $post_id = $parent_id;
      
   $excer = get_post_meta($post_id, '_short-description', true);
   $featImg = get_post_meta($post_id, '_featured-image', true)[0];

   $imId = attachment_url_to_postid( $featImg);

    $my_args = array( 
      'ID' => $post_id,
      'post_excerpt' => $excer,
    );

    $listing = \MyListing\Src\Listing::get( $post_id );
  
    remove_action('save_post_job_listing', 'add_listing_fields');
      set_post_thumbnail( $post_id, $imId );
      wp_update_post( $my_args );
      update_post_meta( $post_id, 'listing_logo', $listing->get_logo());
      update_post_meta( $post_id, 'listing_cover', $listing->get_cover_image());

      add_action('save_post_job_listing', 'add_listing_fields');
}
add_action( 'save_post_job_listing', 'add_listing_fields' );


//Product fields
function add_product_fields($post_id) {
  // If this is a revision, get real post ID
  if ( $parent_id = wp_is_post_revision( $post_id ) ) 
      $post_id = $parent_id;

      $meta_arr = array(); 

      $listingId = intval(get_post_meta($post_id, 'listing', true)[0]);
      $listing_meta = get_post_meta($listingId);
  
      $meta_arr['phone'] = $listing_meta['_job_phone'][0] ?? null;
      $meta_arr['cover'] = $listing_meta['listing_cover'][0] ?? null;
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

   $response['user'] = $user_obj;
 }
 return $response;
}, 10, 2);


//Google map api
function my_acf_init() {
	
	acf_update_setting('google_api_key', 'AIzaSyDquFA71wYW2IHiZOADRsHKG2NFs1X6ZG0');
}

add_action('acf/init', 'my_acf_init');

function my_acf_google_map_api( $api ){
	
	$api['key'] = 'AIzaSyDquFA71wYW2IHiZOADRsHKG2NFs1X6ZG0';
	
	return $api;
	
}

add_filter('acf/fields/google_map/api', 'my_acf_google_map_api');

//show acf fields in admin
add_filter( 'acf/settings/show_admin', '__return_true', 50 );


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

  $args = array(
        'post_type'  => $type,
        'posts_per_page' => -1,
        'status'    => 'publish',
      );
 	
	$query = new WP_Query($args);
    $posts = $query->get_posts();
    if (empty($posts)) {
    return new WP_Error( 'no_items', 'No item matching your filters', array('status' => 200) );

    }
    
  	$postList = array();
  
  	foreach($posts as $post){
      $postList[] = $post-> ID;
    }

    $response = new WP_REST_Response($postList);
    $response->set_status(200);
  
  	$total = $query->found_posts; 	
    $pages = $query->max_num_pages;   	 	
    $response->header( 'X-WP-Total', $total ); 	
    $response->header( 'X-WP-TotalPages', $pages );  	

    return $response;
}


 //$customName = "m-api/v1";
add_action('rest_api_init', function () {
  register_rest_route('m-api/v1', 'places(?:/(?P<id>\d+))?',array(
                'methods'  => 'GET',
                'callback' => 'directory_query',
    			'permission_callback' => '__return_true',
      ));

    register_rest_route( 'user-actions/v1', 'edit-user(?:/(?P<id>\d+))?', array(
        'methods' => 'POST',
        'callback' => 'listing_user_actions',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('m-api/v1', 'ids(?:/(?P<id>\d+))?',array(
      'methods'  => 'GET',
      'callback' => 'all_items_ids',
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
});


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
    $postId = $parameters['liked_id'] ? $parameters['liked_id'] : '';
    $removeId = $parameters['unliked_id'] ? $parameters['unliked_id'] : '';
    $userId = $parameters['user_id'] ? $parameters['user_id'] : '';

    
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

function my_rest_prepare_listing( $data, $post, $request ) {
    $_data = $data->data;

    //$category = get_the_category ( $post->ID );
  	$acf_data = get_fields($post->ID);
    $thumbnail = get_the_post_thumbnail_url( $post->ID, 'thumbnail' );
    $large_thumbnail = get_the_post_thumbnail_url( $post->ID, 'medium' );
  	$cats = get_the_terms( $post->ID, 'job_listing_category' );
    $locs = get_the_terms( $post->ID, 'region' );
    
    $catIds = array();
    if($cats){
      foreach($cats as $cat){
        $catIds[] = $cat->term_id;
      }
    }
   
        $views = get_visits($post->ID);

    if($catIds){
      $category = get_term( $catIds[0], 'job_listing_category' );
      $cat_meta = get_term_meta($catIds[0]);
      $category->rl_awesome = $cat_meta['rl_awesome'][0]   ?? null;
      $category->color = $cat_meta['color'][0]   ?? null;
    }

    $meta = get_post_meta( $post->ID );
    $excerpt = get_the_excerpt( $post->ID);
    $the_content = apply_filters('the_content', get_the_content());

    $hours = get_post_meta($post->ID, '_work_hours', true);
    $food_menu = get_post_meta($post->ID, '_food-drinks-menu', true);
    $social_links = get_post_meta($post->ID, '_links', true);
    $phone = get_post_meta($post->ID, '_job_phone', true);
    $tagline = get_post_meta($post->ID, '_job_tagline', true);
    $cover = get_post_meta($post->ID, '_job_cover', true);
    $logo = get_post_meta($post->ID, '_job_logo', true);
    $gallery = get_post_meta($post->ID, '_job_gallery', true);
    $author = get_the_author_meta('ID');
    $comment_num = get_comments_number($post->ID);
    $team = get_post_meta($post->ID, '_team', true);
    //$cover = get_post_meta($post->ID, 'listing_cover', true);
    //$logo = get_post_meta($post->ID, 'listing_logo', true);

   
    
    $_data['rating'] = $meta['user_rating'] ? intval($meta['user_rating'][0]) : null;
    $_data['food_menu'] = $food_menu   ?? null;
    $_data['about_us']['our_history'] = $meta['_our-history'][0]   ?? null; 
    $_data['about_us']['our_vision'] = $meta['_our-vision'][0]   ?? null;
    $_data['about_us']['opening_date'] = $meta['_date-we-started'][0]   ?? null; 
    $_data['about_us']['our_mission'] = $meta['_our-mission'][0]   ?? null;  
    $_data['author_id'] = $author;
    $_data['comment_num'] = $comment_num;
    $_data['tagline'] = $tagline   ?? null; 
    $_data['category'] = $category  ?? null;
    $_data['home'] = $meta['_listing-home-page'][0] ?? null; 
    $_data['phone'] = $phone  ?? null;
    $_data['page_views'] = $views  ?? null;
    $_data['content'] = $the_content ?? null;
    $_data['team'] = $team ?? null;
    $_data['short_desc'] = $excerpt;
    $_data['latitude'] = $meta['geolocation_lat'] ? floatval($meta['geolocation_lat'][0]) : null;
    $_data['longitude'] = $meta['geolocation_long'] ? floatval($meta['geolocation_long'][0]) : null;
    $_data['address'] = $meta['_job_location'][0]  ?? null;
    $_data['greeting'] = $meta['_greeting'][0]  ?? null;
    $_data['cover'] = $cover  ? $cover[0] : null;
    $_data['gallery'] = $gallery ?? null;
    $_data['logo'] = $logo  ? $logo[0] : null;
    $_data['website'] = $meta['_job_website'][0]  ?? null;
    $_data['type'] = $meta['_case27_listing_type'][0]  ?? null;
    $_data['level'] =  $meta['_featured'][0] ? intval($meta['_featured'][0]) : 0;
    $_data['social'] = $social_links   ?? null;
    $_data['schedule'] = $hours   ?? null;
    $_data['acf'] = $acf_data ?? null;
    $_data['thumbnail'] = $thumbnail   ?? null;
    $_data['large_thumb'] = $large_thumbnail   ?? null;
  	$_data['categories'] = $cats   ?? null;
    $_data['locations'] = $locs   ?? null;

    $data->data = $_data;
    return $data;
}
add_filter( 'rest_prepare_job_listing', 'my_rest_prepare_listing', 10, 3 );

//Rest prepare listing category

function filter_listing_category( $response, $item, $request ) { 
  // make filter magic happen here...
  if (empty($response->data))
        return $response; 

  return $response; 
}; 
       
add_filter( "rest_prepare_job_listing_category", 'filter_listing_category', 10, 3 ); 


//Post thumbanil in rest

function my_rest_prepare_post( $data, $post, $request ) {
    $_data = $data->data;

  	$postMeta = get_post_meta($post->ID);
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

class MY_REST_Comments_Controller extends WP_REST_Comments_Controller {

  public function get_items( $request ) {

    $response = parent::get_items($request); 

    $prepared_args = array('comment__in' => $request['include'], 'comment__not_in' => $request['exclude'], 'number' => $request['per_page'], 'post_id' => $request['post'] ? $request['post'] : '', 'parent' => isset($request['parent']) ? $request['parent'] : '', 'search' => $request['search'], 'offset' => $request['offset'], 'orderby' => $this->normalize_query_param($request['orderby']), 'order' => $request['order'], 'status' => 'approve', 'type' => 'comment', 'no_found_rows' => false, 'hierarchical' => 'threaded');
    if (empty($request['offset'])) {
        $prepared_args['offset'] = $prepared_args['number'] * (absint($request['page']) - 1);
    }
    if (current_user_can('edit_posts')) {
        $protected_args = array('user_id' => $request['author'] ? $request['author'] : '', 'status' => $request['status'], 'type' => isset($request['type']) ? $request['type'] : '', 'author_email' => isset($request['author_email']) ? $request['author_email'] : '', 'karma' => isset($request['karma']) ? $request['karma'] : '', 'post_author' => isset($request['post_author']) ? $request['post_author'] : '', 'post_name' => isset($request['post_slug']) ? $request['post_slug'] : '', 'post_parent' => isset($request['post_parent']) ? $request['post_parent'] : '', 'post_status' => isset($request['post_status']) ? $request['post_status'] : '', 'post_type' => isset($request['post_type']) ? $request['post_type'] : '');
        $prepared_args = array_merge($prepared_args, $protected_args);
    }
    
    $prepared_args = apply_filters('rest_comment_query', $prepared_args, $request);
    $query = new WP_Comment_Query();
    $query_result = $query->query($prepared_args);
    $comments = array();
    foreach ($query_result as $comment) {
        $post = get_post($comment->comment_post_ID);
        if (!$this->check_read_post_permission($post) || !$this->check_read_permission($comment)) {
            continue;
        }
        $data = $this->prepare_item_for_response($comment, $request);
        $comments[] = $this->prepare_response_for_collection($data);
    }
    $total_comments = (int) $query->found_comments;
    $max_pages = (int) $query->max_num_pages;
    if ($total_comments < 1) {
        // Out-of-bounds, run the query again without LIMIT for total count
        unset($prepared_args['number']);
        unset($prepared_args['offset']);
        $query = new WP_Comment_Query();
        $prepared_args['count'] = true;
        $total_comments = $query->query($prepared_args);
        $max_pages = ceil($total_comments / $request['per_page']);
    }
    $response = rest_ensure_response($comments);
    $response->header('X-WP-Total', $total_comments);
    $response->header('X-WP-TotalPages', $max_pages);
    $base = add_query_arg($request->get_query_params(), rest_url('/wp/v2/comments'));
    if ($request['page'] > 1) {
        $prev_page = $request['page'] - 1;
        if ($prev_page > $max_pages) {
            $prev_page = $max_pages;
        }
        $prev_link = add_query_arg('page', $prev_page, $base);
        $response->link_header('prev', $prev_link);
    }
    if ($max_pages > $request['page']) {
        $next_page = $request['page'] + 1;
        $next_link = add_query_arg('page', $next_page, $base);
        $response->link_header('next', $next_link);
    }
    return $response;
  }

  public function prepare_item_for_response( $comment, $request ) {

    $response = parent::prepare_item_for_response($comment, $request);

    if (empty($response->data))
        return $response;

       if($comment->get_children()){
        $response->data['kids'] = $comment->get_children();
      } 

      $id = intval($comment->comment_ID);
      $args = array(
        'parent'    => $id,
      );
      $comments_query = new WP_Comment_Query();
      $comments = $comments_query->query($args);
      $childArray = array();

      foreach ( $comments as $comment ) {
 
        $data       = $this->prepare_item_for_response( $comment, $request );
        $childArray[] = $this->prepare_response_for_collection( $data );
      }

      $childResponse = rest_ensure_response( $childArray );
      $count = count($comments);

      $response->data['count'] = $count;
      $response->data['New Kids'] = $childResponse;

      return apply_filters( 'rest_prepare_comment', $response, $comment, $request );

  }

  public function get_item( $request ) {
    $comment = $this->get_comment( $request['id'] );
    if ( is_wp_error( $comment ) ) {
        return $comment;
    }

    $data     = $this->prepare_item_for_response( $comment, $request );
    $response = rest_ensure_response( $data );

    return $response;
}
}

if(class_exists('MY_REST_Comments_Controller'))
{
    new MY_REST_Comments_Controller;
}

//custom rest comment item
add_filter( 'rest_prepare_comment', 'my_rest_prepare_comment', 10, 3 );

function my_rest_prepare_comment($response, $comment, $request){
  if (empty($response->data))
        return $response;

       if($comment->get_children()){
        $response->data['kids'] = $comment->get_children();
      } 

      $id = intval($comment->comment_ID);
      $args = array(
       // 'count' => true,
        'parent'    => $id,
      );
      $comments_query = new WP_Comment_Query();
      $comments = $comments_query->query($args);
      $count = count($comments);

      $response->data['count'] = $count;
      $response->data['request'] = $request->get_params();
      $response->data['New Kids'] = $comments;

      return $response;

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
    
        //$response->data['attributes'] = $attributes;
        $response->data['discount_rate'] = intval($pdt_discount);
 
    $listing_data = get_post_meta($id, 'listing_data', true);

    $response->data['listing'] = $listing_data;
    return $response;
}


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
        $meta_0bj->image_url = $meta['icon_image'] ? wp_get_attachment_url(number_format($meta['icon_image'][0])) : null;
        $meta_0bj->color = $meta['color'][0]  ?? null;
      	$meta_0bj->icon = $meta['icon'][0]  ?? null;
        $meta_0bj->rl_awesome = $meta['rl_awesome'][0]  ?? null;
        $meta_0bj->iconify = $meta['iconify'][0]  ?? null;
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

//places in posts rest

// Extend the `WP_REST_Posts_Controller` class
class Custom_Posts_Controller extends WP_REST_Posts_Controller
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
        $filters  = $params['filter']  ?? null;
        $page  = $params['page']  ?? null;
        $per_page  = $params['per_page']  ?? null;
        $keyword  = $params['keyword']  ?? null;
        $randomize = $params['random']  ?? null;
       
        
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

// Create an instance of `Custom_Posts_Controller` and call register_routes() methods
add_action('rest_api_init', function () {
    $listingsController = new Custom_Posts_Controller('job_listing');
    $listingsController->register_routes();
});


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

//Record vsist


function record_visit($request ) {
    
    $parameters = $request->get_params();
    $post_id = $parameters['listing_id'];

		// Get visitor data and insert visit.
    	$visitor = \MyListing\Src\Visitor::instance();
    	$ref = $visitor->get_referrer();
    	$os = $visitor->get_os();
    	$location = $visitor->get_location();

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
			'country_code' => $location ?: null,
			'city' => null,
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

    $sql = $this->_apply_query_rules( $sql, $args );
    $sql = join( "\n", $sql );

    $query = $wpdb->get_row( $sql, OBJECT );

    $data = is_object( $query ) && ! empty( $query->count ) ? (int) $query->count : 0;
    $response = rest_ensure_response( $data );

    return $response;
}


//Get Visits
function get_visits($post_id ) {
    
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

    $sql = _apply_query_rules( $sql, $args );
    $sql = join( "\n", $sql );

    $query = $wpdb->get_row( $sql, OBJECT );

    return is_object( $query ) && ! empty( $query->count ) ? (int) $query->count : 0;
    //$response = rest_ensure_response( $data );
}


function _apply_query_rules( $sql, $args ) {
    global $wpdb;

    // Get stats for a single author.
    if ( ! empty( $args['user_id'] ) ) {
        $sql[] = sprintf( " AND {$wpdb->posts}.post_author = %d ", $args['user_id'] );
    }

    // Get stats for a single listing.
    if ( ! empty( $args['listing_id'] ) ) {
        $sql[] = sprintf( " AND {$wpdb->prefix}mylisting_visits.listing_id = %d ", $args['listing_id'] );
    }

    // Limit visit timeframe.
    if ( ! empty( $args['time'] ) && in_array( $args['time'], ['lastday', 'lastweek', 'lastmonth', 'lasthalfyear', 'lastyear'] ) ) {
        $time_modifiers = [ 'lastday' => '-1 day', 'lastweek' => '-7 days', 'lastmonth' => '-30 days', 'lasthalfyear' => '-182 days', 'lastyear' => '-365 days' ];
        $sql[] = sprintf(
            " AND {$wpdb->prefix}mylisting_visits.time >= '%s' ",
            c27()->utc()->modify( $time_modifiers[ $args['time'] ] )->format('Y-m-d H:i:s')
        );
    }

    return $sql;
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

$custom_messages_endpoint = new BP_REST_Messages_Endpoint;

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
//add_filter(array( 'custom_messages_endpoint', 'get_items_permissions_check' ), 'custom_get_messages_permission', 11, 2 );


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

  $thread = BP_REST_Messages_Endpoint::get_thread_object( $request['id'] );

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

function listing_pdts_edit( $value, $post_id, $field  ) {
	
	// vars
	$field_name = $field['name'];
	$field_key = $field['key'];
	$global_name = 'is_updating_' . $field_name;
	
	// - this prevents an inifinte loop
	if( !empty($GLOBALS[ $global_name ]) ) return $value;
	
	// set global variable to avoid inifite loop
	$GLOBALS[ $global_name ] = 1;
	
	// loop over selected posts and add this $post_id
	if( is_array($value) ) {

    foreach($value as $pdt_id) {

    //$pdt_id = $value[0];
    
     $meta_arr = array(); 

      //$listingId = intval(get_post_meta($post_id, 'listing', true)[0]);
      $listing_meta = get_post_meta($post_id);
      
      $meta_arr['id'] = intval($post_id);
      $meta_arr['title'] = get_the_title($post_id);
      $meta_arr['phone'] = $listing_meta['_job_phone'][0];
      $meta_arr['cover'] = $listing_meta['listing_cover'][0];
      $meta_arr['logo'] = $listing_meta['listing_logo'][0];
      $meta_arr['whatsapp'] = $listing_meta['_whatsapp-number'][0];
  
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

add_filter('acf/update_value/key=field_618be43e2b26b', 'listing_pdts_edit', 10, 3);

add_filter( 'mylisting\links-list', function( $links ) {
  // Add new link
  $links['WhatsApp'] = [
      'name' => 'WhatsApp',
      'key' => 'WhatsApp',
      'icon' => 'fa fa-whatsapp',
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
    
    if($sale > 0){
        $discount = round( 100 - ( $sale / $regular * 100));
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
add_action('save_post_product', 'update_vendor_categories', 20);
function update_vendor_categories($post_id)
{
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