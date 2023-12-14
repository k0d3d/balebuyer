<?php
/*
Plugin Name: Hide Price for Balebuyer.com
Description: A WooCommerce plugin to hide product prices if a user has not purchased wholesale plan.
Version: 1.0
Author: k0d3d 
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

// Main class for the plugin
class Hide_Price_For_User_Role
{

  // Constructor
  // Constructor
  public function __construct()
  {
    // Initialize the plugin
    $this->init();

    // Add options page
    add_action('admin_menu', array($this, 'add_plugin_options_page'));

    // Register settings and fields
    add_action('admin_init', array($this, 'register_hide_price_options'));
  }

  // Initialize the plugin
  private function init()
  {
    // Add hooks and filters here
    add_filter('woocommerce_get_price_html', array($this, 'hide_prices'), 10, 2);
  }

  // Function to hide prices and display link/button for a specified product category or user purchase
  public function hide_prices($price, $product)
  {

    $options = get_option('hide_price_options');
    $specified_category = !empty($options['specified_category']) ? $options['specified_category'] : '';
    $specified_product_id = !empty($options['specified_product_id']) ? $options['specified_product_id'] : '';
    $buy_plan_button = !empty($options['buy_plan_button']) ? $options['buy_plan_button'] : 'View Price';

    // Check if the current user can view prices or if the product is in the specified category
    if (has_term($specified_category, 'product_cat', $product->get_id())) {
      if ($this->user_has_purchased($specified_product_id)) {
        // If the user has purchased the specified product, return the original price
        return $price;
      }


      remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
      remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart');

      // Optionally, you can add a message or customize the output here
      // return __('This product is not available for purchase.', 'your-text-domain');
      // For other products, display a link or button to the specified product
      $specified_product_permalink = get_permalink($specified_product_id);
      $button_html = '<a href="' . esc_url($specified_product_permalink) . '" class="button">' . __($buy_plan_button, 'bale-buyer') . '</a>';
      return $button_html;
      return $price; // Return the original price
    } else {
      return $price;
    }
  }

  // Function to check if the user has purchased a specific product
  private function user_has_purchased($product_id)
  {
    $current_user = wp_get_current_user();
    $customer_orders = wc_get_orders(array(
      'customer' => $current_user->ID,
      'status' => array('completed'),
    ));

    foreach ($customer_orders as $order) {
      foreach ($order->get_items() as $item) {
        if ($item->get_product_id() == $product_id) {
          return true; // User has purchased the specified product
        }
      }
    }

    return false; // User has not purchased the specified product
  }


  // Add an options page to the WordPress admin menu
  public function add_plugin_options_page()
  {
    add_menu_page(
      'Hide Price',
      'Hide Price Options',
      'manage_options',
      'bale_buyer_hide_price_options',
      array($this, 'render_options_page')
    );
  }

  // Render the content of the options page
  public function render_options_page()
  {

    require_once(plugin_dir_path(__FILE__) . 'balebuyer-admin-display.php');
  }

  // Register settings and fields
  public function register_hide_price_options()
  {
    // Register the settings
    register_setting('hide_price_options_group', 'hide_price_options', array($this, 'validate_options'));

    // Add a section to the settings page
    add_settings_section('hide_price_options_section', 'Hide Price Options', array($this, 'section_callback'), 'hide_price_options_page');

    // Add fields to the section
    add_settings_field('specified_category', 'Wholesale Category ID', array($this, 'category_field_callback'), 'hide_price_options_page', 'hide_price_options_section');
    add_settings_field('specified_product_id', 'Specified Product ID', array($this, 'product_id_field_callback'), 'hide_price_options_page', 'hide_price_options_section');
    add_settings_field('buy_plan_button', 'View Price Button Label', array($this, 'buy_plan_field_callback'), 'hide_price_options_page', 'hide_price_options_section');
  }

  // Callback for the section
  public function section_callback()
  {
    echo '<p>Specify options for hiding prices.</p>';
  }

  // Callback for the category field
  public function category_field_callback()
  {
    $options = get_option('hide_price_options');
    $specified_category = isset($options['specified_category']) ? $options['specified_category'] : '';
    echo '<input type="text" name="hide_price_options[specified_category]" value="' . esc_attr($specified_category) . '" />';
  }

  // Callback for the View Wholesale button
  public function buy_plan_field_callback()
  {
    $options = get_option('hide_price_options');
    $buy_plan_button = isset($options['buy_plan_button']) ? $options['buy_plan_button'] : '';
    echo '<input type="text" name="hide_price_options[buy_plan_button]" value="' . esc_attr($buy_plan_button) . '" />';
  }

  // Callback for the product ID field
  public function product_id_field_callback()
  {
    $options = get_option('hide_price_options');
    $specified_product_id = isset($options['specified_product_id']) ? $options['specified_product_id'] : '';
    echo '<input type="text" name="hide_price_options[specified_product_id]" value="' . esc_attr($specified_product_id) . '" />';
  }

  // Validate the options
  public function validate_options($input)
  {
    $validated = array();

    // Validate specified category
    if (isset($input['specified_category'])) {
      $validated['specified_category'] = sanitize_text_field($input['specified_category']);
    }

    // Validate specified product ID
    if (isset($input['specified_product_id'])) {
      $validated['specified_product_id'] = absint($input['specified_product_id']);
    }

    return $validated;
  }
}

// Instantiate the main class
new Hide_Price_For_User_Role();
