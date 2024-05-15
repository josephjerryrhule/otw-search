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

    //Retrive settings from WordPress options table
    $settings = get_option('otwsearch_settings');

    // Check if the settings are enabled and display items accordingly
    $show_categories = $settings['show_categories'];
    $show_products = $settings['show_products'];
    $show_bestselling = $settings['show_bestselling'];
    $show_recentlyviewed = $settings['show_recentlyviewed'];


    // Perform separate queries to search by title and SKU
    $args_title = array(
      'post_type'      => 'product',
      'posts_per_page' => 12,
      's'              => $search_term,
    );

    $args_sku = array(
      'post_type'      => 'product',
      'posts_per_page' => 12,
      'meta_query'     => array(
        array(
          'key'   => '_sku',
          'value' => $search_term,
          'compare' => 'LIKE',
        ),
      ),
    );

    // Query products by title


    add_filter('posts_search', [$this, 'ajax_posts_search'], 10, 2);
    $products_title = new \WP_Query($args_title);
    remove_filter('posts_where', [$this, 'ajax_posts_search']);

    // Query products by SKU
    $products_sku = new \WP_Query($args_sku);

    // Merge results from both queries
    $products = new \WP_Query();
    $title_search_ids = array();
    if ($products_title && is_array($products_title->posts) && count($products_title->posts) >= 1) {
      foreach ($products_title->posts as $single_post) {
        $title_search_ids[$single_post->ID] = $single_post;
      }
    }
    if ($products_sku && is_array($products_sku->posts) && count($products_sku->posts) >= 1 && $title_search_ids) {
      foreach ($products_sku->posts as $single_post) {
        if (array_key_exists($single_post->ID, $title_search_ids)) {
          unset($title_search_ids[$single_post->ID]);
        }
      }
    }
    $products->posts = array_merge($title_search_ids, $products_sku->posts);
    $products->post_count = count($products->posts);


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
    if ('yes' === $show_categories) :
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
    endif;

    if ('yes' === $show_products) :
      // Output HTML for products
      if ($products->have_posts()) :
      ?>
        <!-- Products Area -->
        <div class="otw-search-results-products-area" style="<?php if ('yes' === $show_products && 'yes' === $show_recentlyviewed) : echo '--col-width:35%;';
                                                              else : echo '--col-width:60%;';
                                                              endif; ?>">
          <span class="otw-search-results-title">
            <?php echo __('Products', 'otwsearch'); ?>
          </span>
          <div class="otw-search-results-products-area-content product">
            <?php while ($products->have_posts()) :
              $products->the_post();
              global $product;
            ?>
              <div class="otw-search-results-product-item">
                <a href="<?php echo $product->get_permalink(); ?>">
                  <?php
                  echo $product->get_image();
                  ?>
                  <div class="otw-search-results-product-item-content">
                    <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                    <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                    <span class="otw-search-results-product-item-link desktop-hidden"><?php echo __('Buy Now', 'otwsearch'); ?></span>

                  </div>
                </a>
                <div class="otw-search-results-product-item-popup">
                  <?php echo $product->get_image(); ?>
                  <div class="otw-search-results-product-item-content">
                    <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                    <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                  </div>
                  <a class="otw-search-results-product-item-link" href="<?php echo $product->get_permalink(); ?>"><?php echo __('Buy Now', 'otwsearch'); ?></a>
                </div>
              </div>
            <?php endwhile;
            wp_reset_postdata(); ?>
          </div>
          <?php
          if (!empty($search_term)) :
            // Perform separate query to count the number of products matching the search term
            $args_count = array(
              'post_type'      => 'product',
              'posts_per_page' => -1, // Retrieve all products
              's'              => $search_term,
            );

            $count_query = new \WP_Query($args_count);
            $total_products_count = $count_query->found_posts;

            // Output HTML for "View all products" link with the total product count
            echo '<a href="' . home_url('/?s=' . $search_term . '&post_type=product&dgwt_wcas=1') . '" class="text-center">';
            printf(_n('View all product (%s)', 'View all products (%s)', $total_products_count, 'otwsearch'), number_format_i18n($total_products_count));
            echo '</a>';
          else :
            // Get the total number of products
            $total_products = wp_count_posts('product')->publish;

            // Output HTML for "View all products" link with the total product count
            echo '<a href="' . home_url('/?post_type=product') . '" class="text-center">';
            printf(_n('View all product (%s)', 'View all products (%s)', $total_products, 'otwsearch'), number_format_i18n($total_products));
            echo '</a>';
          endif;
          ?>
        </div>
      <?php
      else :
      ?>
        <div class="otw-search-results-products-area" style="<?php if ('yes' === $show_products && 'yes' === $show_recentlyviewed) : echo '--col-width:35%;';
                                                              else : echo '--col-width:60%;';
                                                              endif; ?>">
          <span class="otw-search-results-title">
            <?php echo __('Products', 'otwsearch'); ?>
          </span>
          <?php
          echo __('No Products Found', 'otwsearch');
          ?>
        </div>
      <?php
      endif;
    endif;

    if ('yes' === $show_bestselling) :
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
                    <span class="otw-search-results-product-item-link desktop-hidden"><?php echo __('Buy Now', 'otwsearch'); ?></span>
                  </div>
                </a>
                <div class="otw-search-results-product-item-popup">
                  <?php echo $product->get_image(); ?>
                  <div class="otw-search-results-product-item-content">
                    <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                    <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                  </div>
                  <a class="otw-search-results-product-item-link" href="<?php echo $product->get_permalink(); ?>"><?php echo __('Buy Now', 'otwsearch'); ?></a>
                </div>
              </div>
          <?php
            endforeach;
          endif;
          ?>
        </div>
      </div>

      <!-- End of Best Seller Area -->
    <?php
    endif;
    ?>

    <!-- Recently Viewed -->
    <?php
    if ('yes' === $show_recentlyviewed) :

      $recently_viewed = $this->get_recently_viewed_product_ids();

      if ($recently_viewed) :
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
                      <span class="otw-search-results-product-item-link desktop-hidden"><?php echo __('Buy Now', 'otwsearch'); ?></span>
                    </div>
                  </a>
                  <div class="otw-search-results-product-item-popup">
                    <?php echo $product->get_image(); ?>
                    <div class="otw-search-results-product-item-content">
                      <span class="otw-search-results-product-item-title"><?php echo $product->get_name(); ?></span>
                      <span class="otw-search-results-product-item-price"><?php echo $product->get_price_html(); ?></span>
                    </div>
                    <a class="otw-search-results-product-item-link" href="<?php echo $product->get_permalink(); ?>"><?php echo __('Buy Now', 'otwsearch'); ?></a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php

          endif;
        else :
          ?>
          <div class="otw-search-best-seller-area otw-search-recently-viewed-area" style="--col-width:25%;">
            <span class="otw-search-results-title"><?php echo __('Recently Viewed', 'otwsearch'); ?></span>
            <span><?php echo __('No Recently Viewed Products', 'otwsearch'); ?></span>
          </div>
        <?php
        endif;
        ?>

        </div>
        <!-- End of Recently Viewed -->

  <?php
    endif;
    // Output HTML
    $output = ob_get_clean();
    echo $output;

    wp_die();
  }



  public function ajax_posts_search($search, $wp_query)
  {
    global $wpdb;
    if ($wp_query->get('s')) {
      $search = ' AND ' . $wpdb->posts . '.post_title LIKE "%' . $wp_query->get('s') . '%"';
    }

    return $search;
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
}

otw_search::get_instance();
