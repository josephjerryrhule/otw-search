<?php

/**
 * Plugin Name: OTW Search Elementor
 * Author: OTW Design
 * Version: 0.1.1
 * text-domain: otwsearch
 * 
 * @package otwsearch
 */

namespace OTWSEARCH\ElementorWidgets;

use OTWSEARCH\ElementorWidgets\Widgets\search;

if (!defined('ABSPATH')) {
  exit;
}

final class otw_search
{
  private static $_instance = null;

  public static function get_instance()
  {
    if (is_null(self::$_instance)) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  public function __construct()
  {
    add_action('elementor/init', [$this, 'init']);
    register_activation_hook(__FILE__, [$this, 'plugin_activation']);
  }

  public function init()
  {
    add_action('elementor/elements/categories_registered', [$this, 'create_new_category']);
    add_action('elementor/widgets/register', [$this, 'init_widgets']);
    add_action('wp_ajax_custom_search_action', [$this, 'custom_search_callback']);
    add_action('wp_ajax_nopriv_custom_search_action', [$this, 'custom_search_callback']);
  }

  public function create_new_category($elements_manager)
  {
    $elements_manager->add_category(
      'otw-search',
      [
        'title' => __('OTW Search', 'otwsearch')
      ]
    );
  }

  public function init_widgets($widgets_manager)
  {

    //Require Widgets
    require_once __DIR__ . '/widgets/search.php';

    //Instantiate Widgets
    $widgets_manager->register(new search());
  }



  public function custom_search_callback()
  {
    $search_term = sanitize_text_field($_POST['search_term']);

    // Fetch WooCommerce products with search term filter
    $products = wc_get_products(array(
      'status'     => 'publish',
      'limit'      => 4, // Retrieve all matching products
      's'          => $search_term, // Search term filter
    ));

    // Fetch product categories associated with the matching products
    $categories = array();
    foreach ($products as $product) {
      $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
      foreach ($product_categories as $product_category) {
        if (!in_array($product_category, $categories)) {
          $categories[] = $product_category;
        }
      }
    }

    // Output HTML for categories and products
    ob_start();

    // Output HTML for categories
    if (!empty($categories)) :
?>
      <!-- Categories Area -->
      <div class="otw-search-results-category-area">
        <span class="otw-search-results-title"><?php echo __('Categories', 'otwsearch'); ?></span>
        <ul>
          <?php foreach ($categories as $category) : ?>
            <li><a href="<?php echo get_term_link($category); ?>"><?php echo __($category->name, 'otwsearch'); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php
    endif;

    // Output HTML for products
    if (!empty($products) && !is_wp_error($products)) :
    ?>
      <!-- Products Area -->
      <div class="otw-search-results-products-area">
        <div class="otw-search-results-products-area-content">
          <?php foreach ($products as $product) : ?>
            <div class="otw-search-results-product-item">
              <a href="<?php echo $product->get_permalink(); ?>">
                <?php echo $product->get_image(); ?>
                <span><?php echo $product->get_name(); ?></span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php
    endif;
    ?>
    <!-- Shortcode Area -->
    <div class="otw-search-results-shortcode-area">
      <span class="otw-search-results-title">
        <?php echo __('Best Selling', 'otwsearch'); ?>
      </span>
    </div>
    <!-- End of Shortcode Area -->

<?php

    // Output HTML
    $output = ob_get_clean();
    echo $output;

    wp_die();
  }

  // Function to add indexes to WooCommerce database tables
  public function add_custom_indexes()
  {
    global $wpdb;

    // Add index to wp_posts table for product titles
    $wpdb->query("ALTER TABLE {$wpdb->posts} ADD INDEX idx_product_title (post_title)");

    // Add index to wp_posts table for product SKUs
    $wpdb->query("ALTER TABLE {$wpdb->posts} ADD INDEX idx_product_sku (post_excerpt)");

    // Add index to wp_terms table for term names
    $wpdb->query("ALTER TABLE {$wpdb->terms} ADD INDEX idx_term_name (name)");

    // Add index to wp_term_taxonomy table for term taxonomy
    $wpdb->query("ALTER TABLE {$wpdb->term_taxonomy} ADD INDEX idx_term_taxonomy (taxonomy)");

    // Add more indexes as needed for other fields or tables
  }

  // Function to run on plugin activation
  public function plugin_activation()
  {
    $this->add_custom_indexes();
  }
}

otw_search::get_instance();
