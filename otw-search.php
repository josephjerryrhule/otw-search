<?php

/**
 * Plugin Name: OTW Search Elementor
 * Author: OTW Design
 * Version: 0.1.2
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


    // Hook into WooCommerce to add product to recently viewed when product page is loaded
    add_action('woocommerce_before_single_product', array($this, 'add_product_to_recently_viewed'));
    add_action('wp_ajax_get_recently_viewed_products', [$this, 'get_recently_viewed_products']);
    add_action('wp_ajax_nopriv_get_recently_viewed_products', [$this, 'get_recently_viewed_products']);
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
    // Count the total number of products matching the search term
    $total_products_count = $this->get_total_product_count($search_term);


    $args = array(
      'status'     => 'publish',
      'limit'      => 12, // Limit to 12 products
      's'          => $search_term, // Search term
      'meta_query' => array(
        'relation' => 'OR',
        array(
          'key'     => '_sku',
          'value'   => $search_term,
          'compare' => 'LIKE',
        ),
      ),
    );

    // Fetch WooCommerce products with search term filter and SKU search
    $products = wc_get_products($args);

    // Fetch product categories associated with the matching products
    // Fetch WooCommerce product categories with search term filter
    $categories = get_terms('product_cat', array(
      'hide_empty' => false,
      'number'     => 12, // Limit to 4 categories
      'search'     => $search_term, // Search term filter
    ));

    // Output HTML for categories and products
    ob_start();

    // Output HTML for categories
    if (!empty($categories)) :
?>
      <!-- Categories Area -->
      <div class="otw-search-results-category-area" style="--col-width:15%;">
        <span class="otw-search-results-title"><?php echo __('Categories', 'otwsearch'); ?></span>
        <ul>
          <?php foreach ($categories as $category) : ?>
            <li><a href="<?php echo get_term_link($category); ?>"><?php echo __($category->name, 'otwsearch'); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php
    else :
    ?>
      <div class="otw-search-results-category-area">
        <span class="otw-search-results-title"><?php echo __('Categories', 'otwsearch'); ?></span>
        <span>
          <?php echo __('No Category found with search term', 'otwsearch'); ?>
        </span>
      </div>
    <?php
    endif;

    // Output HTML for products
    if (!empty($products) && !is_wp_error($products)) :
    ?>
      <!-- Products Area -->
      <div class="otw-search-results-products-area" style="--col-width:35%;">
        <span class="otw-search-results-title">
          <?php echo __('Products', 'otwsearch'); ?>
        </span>
        <div class="otw-search-results-products-area-content product">
          <?php foreach ($products as $product) : ?>
            <div class="otw-search-results-product-item">
              <a href="<?php echo $product->get_permalink(); ?>">
                <?php
                echo $product->get_image();
                ?>
                <div class="otw-search-results-product-item-content">
                  <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                  <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                </div>
              </a>
              <div class="otw-search-results-product-item-popup">
                <?php echo $product->get_image(); ?>
                <div class="otw-search-results-product-item-content">
                  <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                  <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                </div>
                <a class="otw-search-results-product-item-link" href="<?php echo $product->get_permalink(); ?>">Buy Now</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php
        // Output HTML for "View all products" link with the total product count
        echo '<a href="' . home_url('/?s=' . $search_term . '&post_type=product&dgwt_wcas=1') . '" class="text-center">';
        printf(_n('View all product (%s)', 'View all products (%s)', $total_products_count, 'otwsearch'), number_format_i18n($total_products_count));
        echo '</a>';
        ?>
      </div>
    <?php
    else :
    ?>
      <div class="otw-search-results-products-area" style="--col-width:35%;">
        <span class="otw-search-results-title">
          <?php echo __('Products', 'otwsearch'); ?>
        </span>
        <?php
        echo __('No Products Found', 'otwsearch');
        ?>
      </div>
    <?php
    endif;
    ?>
    <!-- Best Seller Area -->
    <div class="otw-search-best-seller-area" style="--col-width:25%;">
      <span class="otw-search-results-title">
        <?php echo __('Best Sellers', 'otwsearch'); ?>
      </span>

      <div class="otw-search-results-products-area-content">
        <?php
        // Fetch WooCommerce products
        $products = wc_get_products([
          'status' => 'publish',
          'limit' => 6, // Limit to 6 products
          'orderby' => 'meta_value_num',
          'order' => 'DESC',
          'meta_key' => 'total_sales', // Sort by total sales
        ]);
        if (!empty($products) && !is_wp_error($products)) :
          foreach ($products as $product) :
        ?>
            <div class="otw-search-results-product-item">
              <a href="<?php echo $product->get_permalink(); ?>">
                <?php
                echo $product->get_image();
                ?>
                <div class="otw-search-results-product-item-content">
                  <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                  <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                </div>
              </a>
              <div class="otw-search-results-product-item-popup">
                <?php echo $product->get_image(); ?>
                <div class="otw-search-results-product-item-content">
                  <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                  <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                </div>
                <a class="otw-search-results-product-item-link" href="<?php echo $product->get_permalink(); ?>">Buy Now</a>
              </div>
            </div>
        <?php
          endforeach;
        endif;
        ?>
      </div>
    </div>
    <!-- End of Best Seller Area -->

    <!-- Recently Viewed -->
    <?php
    // Fetch recently viewed product IDs
    $recently_viewed = $this->get_recently_viewed_product_ids();

    // Fetch recently viewed products
    $recently_viewed_products = wc_get_products(array(
      'include' => $recently_viewed,
      'limit' => 6, // Limit to 6 products
    ));
    ?>
    <div class="otw-search-best-seller-area otw-search-recently-viewed-area" style="--col-width:25%;">
      <span class="otw-search-results-title"><?php echo __('Recently Viewed', 'otwsearch'); ?></span>

      <?php
      if (!empty($recently_viewed_products) && !is_wp_error($recently_viewed_products)) :
      ?>
        <div class="otw-search-results-products-area-content">
          <?php foreach ($recently_viewed_products as $product) : ?>
            <div class="otw-search-results-product-item">
              <a href="<?php echo $product->get_permalink(); ?>">
                <?php echo $product->get_image(); ?>
                <div class="otw-search-results-product-item-content">
                  <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                  <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                </div>
              </a>
              <div class="otw-search-results-product-item-popup">
                <?php echo $product->get_image(); ?>
                <div class="otw-search-results-product-item-content">
                  <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                  <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                </div>
                <a class="otw-search-results-product-item-link" href="<?php echo $product->get_permalink(); ?>">Buy Now</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php
      else :
        echo __('No Products Found', 'otwsearch');
      endif;

      ?>

    </div>
    <!-- End of Recently Viewed -->

<?php

    // Output HTML
    $output = ob_get_clean();
    echo $output;

    wp_die();
  }

  // Function to retrieve the total count of products matching the search term
  private function get_total_product_count($search_term)
  {
    $args = array(
      'status'     => 'publish',
      's'          => $search_term, // Search term filter
      'return'     => 'ids',
      'limit' => -1, // Return only product IDs
    );

    // Fetch WooCommerce products with search term filter and return only IDs
    $product_ids = wc_get_products($args);

    // Return the count of retrieved product IDs
    return count($product_ids);
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

  // Function to add product to recently viewed
  public function add_product_to_recently_viewed()
  {
    if (is_product()) {
      $product_id = get_the_ID(); // Get the product ID
      $this->add_to_recently_viewed($product_id); // Add product to recently viewed
    }
  }

  // Function to add product ID to recently viewed products cookie
  public function add_to_recently_viewed($product_id)
  {
    if (!defined('DAY_IN_SECONDS')) {
      define('DAY_IN_SECONDS', 86400);
    }

    if (!defined('COOKIEPATH')) {
      define('COOKIEPATH', '/');
    }

    if (!defined('COOKIE_DOMAIN')) {
      define('COOKIE_DOMAIN', $_SERVER['HTTP_HOST']);
    }

    $recently_viewed = isset($_COOKIE['recently_viewed']) ? json_decode(stripslashes($_COOKIE['recently_viewed']), true) : array();

    // Add the product ID to the recently viewed array
    if (!in_array($product_id, $recently_viewed)) {
      $recently_viewed[] = $product_id;
    }

    // Limit recently viewed products to 6
    $recently_viewed = array_slice($recently_viewed, -6);

    // Store the recently viewed products in a cookie for 30 days
    setcookie('recently_viewed', json_encode($recently_viewed), time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
  }

  private function get_recently_viewed_product_ids()
  {
    $recently_viewed = isset($_COOKIE['recently_viewed']) ? json_decode(stripslashes($_COOKIE['recently_viewed']), true) : array();
    return $recently_viewed;
  }


  public function get_recently_viewed_products()
  {

    $recently_viewed_ids = isset($_COOKIE['recently_viewed']) ? json_decode(stripslashes($_COOKIE['recently_viewed']), true) : array();

    // Fetch recently viewed products based on their IDs
    $recently_viewed_products = wc_get_products(array(
      'include' => $recently_viewed_ids,
      'limit' => -1,
    ));

    // Initialize an empty string to store the HTML markup
    $recently_viewed_html = '';

    // Loop through the recently viewed products and generate HTML markup for each product
    foreach ($recently_viewed_products as $product) {
      // Generate HTML markup for the product
      $recently_viewed_html .= '<div class="otw-search-results-product-item">';
      $recently_viewed_html .= '<a href="' . $product->get_permalink() . '">';
      $recently_viewed_html .= $product->get_image();
      $recently_viewed_html .= '<div class="otw-search-results-product-item-content">';
      $recently_viewed_html .= '<span class="otw-search-results-product-item-title">' . $product->get_name() . '</span>';
      $recently_viewed_html .= '<span class="otw-search-results-product-item-price">' . $product->get_price_html() . '</span>';
      $recently_viewed_html .= '<span class="otw-search-results-product-item-link desktop-hidden">' . __('Buy Now', 'otwsearch') . '</span>';
      $recently_viewed_html .= '</div></a>';
      $recently_viewed_html .= '<div class="otw-search-results-product-item-popup">';
      $recently_viewed_html .= $product->get_image();
      $recently_viewed_html .= '<div class="otw-search-results-product-item-content">';
      $recently_viewed_html .= '<span class="otw-search-results-product-item-title">' . $product->get_name() . '</span>';
      $recently_viewed_html .= '<span class="otw-search-results-product-item-price">' . $product->get_price_html() . '</span>';
      $recently_viewed_html .= '</div>';
      $recently_viewed_html .= '<a class="otw-search-results-product-item-link" href="' . $product->get_permalink() . '">' . __('Buy Now', 'otwsearch') . '</a>';
      $recently_viewed_html .= '</div>';
      $recently_viewed_html .= '</div>';
    }

    // Send the HTML markup as a success response
    wp_send_json_success($recently_viewed_html);
  }
}

otw_search::get_instance();
