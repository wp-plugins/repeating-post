<?php
if(!is_admin())
return;

/**
 * Utility for finding ultimate parent
 */
function get_topmost_parent_id($post_id){
  // error_log('[get_topmost_parent] '.$post_id);
  $parent_id = get_post($post_id)->post_parent;
  if($parent_id == 0){
    return $post_id;
  }else{
    return get_topmost_parent_id($parent_id);
  }
}

/**
 * Loops through dates and creates new posts
 */
function create_repeating_posts($post, $days, $start_date, $end_date){
  // error_log('[create_repeating_posts] Template: '.$post->ID);

  // We don't want to clone revisions
  if ($post->post_type == 'revision') return;

  // unhook this function so it doesn't loop infinitely
  remove_action('save_post', 'repeating_post_save_as_new_post');

  $interval = new DateInterval('P'.$days.'D');
  $start_date = DateTime::createFromFormat('Y/m/d', $start_date);
  $end_date = DateTime::createFromFormat('Y/m/d', $end_date);

  // $topmost_parent = get_topmost_parent_id($post->id);

  $date = $start_date;
  do {
    // error_log($date->format('Y-m-d H:i:s'));

    $new_post = array(
    'menu_order' => $post->menu_order,
    'comment_status' => $post->comment_status,
    'ping_status' => $post->ping_status,
    'post_author' => $new_post_author->ID,
    'post_content' => $post->post_content,
    'post_excerpt' => (get_option('duplicate_post_copyexcerpt') == '1') ? $post->post_excerpt : "",
    'post_mime_type' => $post->post_mime_type,
    'post_parent' => 0,
    'post_password' => $post->post_password,
    'post_status' => 'future',
    'post_title' => $prefix.$post->post_title.$suffix,
    'post_type' => $post->post_type,
    'post_date' => $date->format('Y-m-d '.'00:00:00'),
    'post_date_gmt' => $date->format('Y-m-d '.'00:00:00'),
    );

    $new_post_id = wp_insert_post($new_post, true);

    // error_log('New Post ID: '.$new_post_id.' :: '.$post->post_type);

    $date = $date->add($interval);
  } while ($date < $end_date);

  // re-hook this function
  add_action('save_post', 'repeating_post_save_as_new_post');

  return;
}

/**
 * Create repeating posts and redirect
 */
function repeating_post_save_as_new_post( $post_id ) {
  // error_log("[repeating_post_save_as_new_post] Post ID: ".$post_id);
  // First we need to check if the current user is authorised to do this action.
  if ( 'page' == $_POST['post_type'] ) {
    if ( ! current_user_can( 'edit_page', $post_id ) )
        return;
  } else {
    if ( ! current_user_can( 'edit_post', $post_id ) )
        return;
  }

  // error_log("post:".implode(" | ", $_POST));

  // Secondly we need to check if the user intended to change this value.
  $days = $_POST['repeating-post-days'];
  $start_date = $_POST['repeating-post-start-date'];
  $end_date = $_POST['repeating-post-end-date'];
  // error_log("empty date: ".empty($date));
  // error_log("empty days: ".empty($days));

  if ( empty($start_date) || empty($end_date) || empty($days) )
    return;

  // Get the original post
  // $id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
  $post_id = get_topmost_parent_id($post_id);
  $post = get_post($post_id);

  // Copy the post and insert it
  if (isset($post) && $post!=null)
  {
    $result = create_repeating_posts($post, $days, $start_date, $end_date);

    wp_redirect( admin_url( '/edit.php') );
    exit;
  }
}

add_action( 'save_post', 'repeating_post_save_as_new_post' );

/**
 * Retrieve duplicate post link for post.
 *
 *
 * @param int $id Optional. Post ID.
 * @param string $context Optional, default to display. How to write the '&', defaults to '&amp;'.
 * @param string $draft Optional, default to true
 * @return string
 */
function repeating_post_get_clone_post_link( $id = 0, $context = 'display', $draft = true ) {
  
  if ( !$post = &get_post( $id ) )
  return;

  if ($draft)
  $action_name = "repeating_post_save_as_new_post_draft";
  else
  $action_name = "repeating_post_save_as_new_post";

  if ( 'display' == $context )
  $action = '?action='.$action_name.'&amp;post='.$post->ID;
  else
  $action = '?action='.$action_name.'&post='.$post->ID;

  $post_type_object = get_post_type_object( $post->post_type );
  if ( !$post_type_object )
  return;

  return apply_filters( 'repeating_post_get_clone_post_link', admin_url( "admin.php". $action ), $post->ID, $context );
}

add_action('admin_action_repeating_post_save_as_new_post', 'repeating_post_save_as_new_post', 10, 3);

/**
 * Calls the class on the post edit screen
 */
function call_repeating_post_meta_box() 
{
    return new repeating_post_meta_box();
}
if ( is_admin() )
    add_action( 'load-post.php', 'call_repeating_post_meta_box' );

/** 
 * The Class
 */
define('WPFC_META_BOX_URL', plugins_url() . '/sermon-manager-for-wordpress/includes/meta-box/');
class repeating_post_meta_box
{
    const LANG = 'some_textdomain';

    public function __construct()
    {
        add_action( 'add_meta_boxes', array( &$this, 'add_some_meta_box' ) );
    }

    /**
     * Adds the meta box container
     */
    public function add_some_meta_box()
    {
        add_meta_box( 
             'some_meta_box_name'
            ,__( 'Repeating Post', self::LANG )
            ,array( &$this, 'render_meta_box_content' )
            ,'post' 
            ,'advanced'
            ,'high'
        );
    }


    /**
     * Render Meta Box content
     */
    public function render_meta_box_content() 
    {
      // Use nonce for verification
      wp_nonce_field( plugin_basename( __FILE__ ), 'repeating-post' );

      wp_register_script( 'repeating-post-scripts', plugins_url() . '/repeating-post/repeating-post-scripts.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker' ) );
      wp_enqueue_script( 'repeating-post-scripts' );
      $repeating_post_get_clone_post_link = repeating_post_get_clone_post_link();
      require_once('repeating-post-view.php');
    }
}


?>