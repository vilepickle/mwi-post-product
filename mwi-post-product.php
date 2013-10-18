<?php
/*
Plugin Name: MWI Post Product Listing
Plugin URI: 
Description: Adds a reminder to posts to select a featured product from your Magento store.
Author: David Lohmeyer
Version: 0.0.1
Author URI: http://www.vilepickle.com/
*/

/*  Copyright 2013  (email : vilepickle@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( !defined( 'MWI_POST_PRODUCT_URL' ) )
  define( 'MWI_POST_PRODUCT_URL', plugin_dir_url( __FILE__ ) );
if ( !defined( 'MWI_POST_PRODUCT_PATH' ) )
  define( 'MWI_POST_PRODUCT_PATH', plugin_dir_path( __FILE__ ) );

if(is_admin()){
  include_once('inc/mwi_post_product.class.php');
}

add_action( 'wp_insert_post', 'mwi_post_product_save_post_meta_box' );
add_action( 'edit_attachment', 'mwi_post_product_save_post_meta_box' );
add_action( 'add_attachment', 'mwi_post_product_save_post_meta_box' );
add_action( 'save_post', 'mwi_post_product_save_post_meta_box' );
add_filter( 'the_content', 'mwi_post_product_the_content_filter' );

global $pagenow;
if (('post.php' == $pagenow && isset($_GET['post']) ) || 'post-new.php' == $pagenow){
  global $mwi_post_product;
  $mwi_post_product = new mwi_post_product;
}

function mwi_post_product_after_loaded(){
  global $pagenow;
  if (('post.php' == $pagenow && isset($_GET['post']) ) || 'post-new.php' == $pagenow){
    global $mwi_post_product;
    $mwi_post_product = new mwi_post_product;
  }
}

function mwi_post_product_save_post_meta_box( $post_id, $post ) {
  if ( !current_user_can( 'edit_post', $post_id ) ){
    return $post_id;
  }
  $new_meta_value = array(stripslashes( $_POST['mwi-post-product-sku']),  stripslashes( $_POST['mwi-post-product-include-product'] ));
  update_post_meta( $post_id, 'mwi_post_product', $new_meta_value );
  if ( $new_meta_value && '' == $meta_value ){
    add_post_meta( $post_id, 'mwi_post_product', $new_meta_value, true );
  }elseif ( $new_meta_value != $meta_value ){
    update_post_meta( $post_id, 'mwi_post_product', $new_meta_value );
  }elseif ( '' == $new_meta_value && $meta_value ){
    delete_post_meta( $post_id, 'mwi_post_product', $meta_value );
  } 
}

function mwi_post_product_the_content_filter( $content) {
  global $post;
  $selected_product[] = get_post_meta( $post->ID, 'mwi_post_product', true );
  if ( is_single() && $selected_product[0][1] == 1 && $selected_product[0][0] != 0 ){
    if(mwi_post_product_shortcode_exists('mwi_product')){
      $shortcode = 'mwi_product';
    }else{
      $shortcode = 'product';
    }
    $content = $content . '[' . $shortcode . ' sku="' . $selected_product[0][0] . '"]';
  }
  return $content;
}

function mwi_post_product_shortcode_exists( $shortcode = false ) {
  global $shortcode_tags;
  if ( ! $shortcode ){
    return false;
  }
  if ( array_key_exists( $shortcode, $shortcode_tags ) ){
    return true;
  }
  return false;
}