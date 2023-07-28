<?php
/*
Plugin Name: Testimonials-Form
Plugin URI: your-plugin-url
Description: A plugin to collect and display user testimonials.
Version: 1.0
Author: MakingYouABrand
Author URI: your-website-url
License: GPL2
*/


function testimonials_enqueue_styles() {
  wp_enqueue_style('testimonials-styles', plugins_url('/css/testimonials-styles.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'testimonials_enqueue_styles');


function render_testimonials_form() {
  ob_start();
  ?>
  <form id="testimonials-form" method="post" enctype="multipart/form-data">
    <label for="testimonial-name">Your Name:</label>
    <input type="text" name="testimonial_name" id="testimonial-name" required>

    <label for="testimonial-email">Your Email:</label>
    <input type="email" name="testimonial_email" id="testimonial-email">


    <label for="testimonial-message">Testimonial:</label>
    <textarea name="testimonial_message" id="testimonial-message" rows="5" required></textarea>

    <label for="testimonial-image">Your Image:</label>
    <input type="file" name="testimonial_image" id="testimonial-image">

    <input type="submit" value="Submit Testimonial">
  </form>
  <?php
  return ob_get_clean();
}

function save_testimonial() {
  if (isset($_POST['testimonial_name']) && isset($_POST['testimonial_message'])) {
    $name = sanitize_text_field($_POST['testimonial_name']);
    $email = sanitize_email($_POST['testimonial_email']);
    $message = sanitize_textarea_field($_POST['testimonial_message']);

    // Create a new post of 'testimonial' post type
    $testimonial_post = array(
      'post_title' => $name,
      'post_content' => $message,
      'post_type' => 'testimonial',
      'post_status' => 'publish',
    );

    $testimonial_id = wp_insert_post($testimonial_post);

    // Save additional data as custom fields (e.g., email)
    update_post_meta($testimonial_id, 'testimonial_email', $email);

    // Handle the uploaded image and set it as the featured image
    if (!empty($_FILES['testimonial_image']['name'])) {
      require_once ABSPATH . 'wp-admin/includes/image.php';
      require_once ABSPATH . 'wp-admin/includes/file.php';
      require_once ABSPATH . 'wp-admin/includes/media.php';

      $attachment_id = media_handle_upload('testimonial_image', $testimonial_id);

      if (is_wp_error($attachment_id)) {
        // Handle the error if the image upload fails
        error_log('Testimonial Image Upload Error: ' . $attachment_id->get_error_message());
      } else {
        // Set the uploaded image as the featured image for the testimonial post
        set_post_thumbnail($testimonial_id, $attachment_id);
      }
    }
  }
}
add_action('init', 'save_testimonial');

function display_testimonials() {
  $args = array(
    'post_type' => 'testimonial',
    'post_status' => 'publish',
    'posts_per_page' => -1, // Show all testimonials
  );

  $testimonials = get_posts($args);

  if ($testimonials) {
    $output = '<div class="testimonials-container">';
    foreach ($testimonials as $testimonial) {
      $name = esc_html($testimonial->post_title);
      $message = esc_html($testimonial->post_content);
      $email = esc_html(get_post_meta($testimonial->ID, 'testimonial_email', true));
      $image_url = get_the_post_thumbnail_url($testimonial->ID, 'thumbnail');

      $output .= '<div class="testimonial-card">';
      if ($image_url) {
        $output .= '<div class="testimonial-image">';
        $output .= '<img src="' . esc_url($image_url) . '" alt="' . $name . '">';
        $output .= '</div>';
      }
      $output .= '<div class="testimonial-content">';
      $output .= '<h3 class="testimonial-name">' . $name . '</h3>';
      if ($email) {
        $output .= '<p class="testimonial-email">' . $email . '</p>';
      }
      $output .= '<p class="testimonial-message">' . $message . '</p>';
      $output .= '</div>';
      $output .= '</div>';
    }
    $output .= '</div>';
  } else {
    $output = '<p>No testimonials found.</p>';
  }

  return $output;
}

function testimonials_shortcode() {
  
  $output = render_testimonials_form();
  $output .= display_testimonials();
  
  return $output;
}
add_shortcode('testimonials', 'testimonials_shortcode');
