<?php

/**
 * Search Widget Addon for Elementor
 * 
 * 
 * @package otwsearch
 */


namespace OTWSEARCH\ElementorWidgets\Widgets;

use Elementor\Widget_Base;

use function OTWSEARCH\ElementorWidgets\get_recently_viewed_product_ids;

class search extends Widget_Base
{

  public function get_name()
  {
    return 'otw-search';
  }

  public function get_title()
  {
    return __('OTW Search', 'otwsearch');
  }

  public function get_icon()
  {
    return 'eicon-elementor';
  }

  public function get_categories()
  {
    return ['otw-search'];
  }

  public function get_style_depends()
  {
    wp_register_style('otw-search-style', plugins_url('scss/otw-style.css', __FILE__));

    return ['otw-search-style'];
  }

  public function get_script_depends()
  {
    wp_register_script('otw-search-script', plugins_url('js/otwsearch.js', __FILE__, true));
    wp_localize_script('otw-search-script', 'ajax_data', [
      'ajax_url' => admin_url('admin-ajax.php'),
    ]);
    return ['otw-search-script'];
  }

  public function register_controls()
  {
    $this->start_controls_section(
      'content',
      [
        'label' => __('Content', 'otwsearch'),
        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'placeholder',
      [
        'label' => __('Placeholder', 'otwsearch'),
        'type' => \Elementor\Controls_Manager::TEXT,
        'placeholder' => __('Add Placeholder here', 'otwsearch'),
        'default' => __('Add Placeholder here', 'otwsearch'),
      ]
    );

    $this->add_control(
      'color',
      [
        'label' => __('Accent Color', 'otwsearch'),
        'type' => \Elementor\Controls_Manager::COLOR,
        'default' => '#333',
      ]
    );

    $this->add_control(
      'show_categories',
      [
        'label' => esc_html__('Show Categories', 'otwsearch'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Show', 'otwsearch'),
        'label_off' => esc_html__('Hide', 'otwsearch'),
        'return_value' => 'yes',
        'default' => 'yes',
      ]
    );

    $this->add_control(
      'show_products',
      [
        'label' => esc_html__('Show Products', 'otwsearch'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Show', 'otwsearch'),
        'label_off' => esc_html__('Hide', 'otwsearch'),
        'return_value' => 'yes',
        'default' => 'yes',
      ]
    );

    $this->add_control(
      'show_bestselling',
      [
        'label' => esc_html__('Show Best Selling', 'otwsearch'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Show', 'otwsearch'),
        'label_off' => esc_html__('Hide', 'otwsearch'),
        'return_value' => 'yes',
        'default' => 'yes',
      ]
    );

    $this->add_control(
      'show_recentlyviewed',
      [
        'label' => esc_html__('Show Recently Viewed', 'otwsearch'),
        'type' => \Elementor\Controls_Manager::SWITCHER,
        'label_on' => esc_html__('Show', 'otwsearch'),
        'label_off' => esc_html__('Hide', 'otwsearch'),
        'return_value' => 'yes',
        'default' => 'yes',
      ]
    );

    $this->end_controls_section();
  }

  protected function render()
  {
    $settings = $this->get_settings_for_display();
    $placeholder = $settings['placeholder'];
    $color = $settings['color'];

    //Store Settings in WordPress options table
    update_option('otwsearch_settings', $settings);

?>
    <style>
      .otw-search-parent-container {
        --e-global-otw-color-accent: <?php echo $color; ?>
      }
    </style>


    <span class="otw-search-icon">
      <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
        <mask id="mask0_95_650" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="26" height="26">
          <path d="M25.5 0.5H0.5V25.5H25.5V0.5Z" fill="white" />
        </mask>
        <g mask="url(#mask0_95_650)">
          <path d="M11.4375 18.2082C15.1769 18.2082 18.2083 15.1768 18.2083 11.4373C18.2083 7.69791 15.1769 4.6665 11.4375 4.6665C7.69807 4.6665 4.66666 7.69791 4.66666 11.4373C4.66666 15.1768 7.69807 18.2082 11.4375 18.2082Z" stroke="black" stroke-width="1.04167" stroke-linejoin="round" />
          <path d="M20.965 21.7018C21.1684 21.9052 21.4982 21.9052 21.7017 21.7018C21.905 21.4984 21.905 21.1686 21.7017 20.9652L20.965 21.7018ZM21.7017 20.9652L16.4933 15.7568L15.7567 16.4935L20.965 21.7018L21.7017 20.9652Z" fill="black" />
        </g>
      </svg>
    </span>

    <div class="otw-search-parent-container">
      <div class="otw-search-content-container">

        <span class="otw-search-close-icon">
          <svg width="25" height="25" viewBox="0 0 25 25" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M20.0073 6.46552C20.197 6.26906 20.302 6.00593 20.2997 5.73281C20.2973 5.45969 20.1877 5.19843 19.9946 5.00529C19.8015 4.81216 19.5402 4.70261 19.2671 4.70023C18.994 4.69786 18.7308 4.80286 18.5344 4.9926L12.5 11.027L6.46562 4.9926C6.26916 4.80286 6.00603 4.69786 5.73291 4.70023C5.45979 4.70261 5.19853 4.81216 5.0054 5.00529C4.81226 5.19843 4.70271 5.45969 4.70034 5.73281C4.69796 6.00593 4.80296 6.26906 4.99271 6.46552L11.0271 12.4999L4.99271 18.5343C4.89322 18.6304 4.81386 18.7453 4.75927 18.8724C4.70467 18.9995 4.67594 19.1362 4.67474 19.2745C4.67354 19.4128 4.69989 19.55 4.75227 19.678C4.80464 19.806 4.88199 19.9223 4.97979 20.0201C5.0776 20.1179 5.1939 20.1953 5.32192 20.2476C5.44994 20.3 5.5871 20.3264 5.72542 20.3252C5.86373 20.324 6.00042 20.2952 6.1275 20.2406C6.25459 20.186 6.36953 20.1067 6.46562 20.0072L12.5 13.9728L18.5344 20.0072C18.7308 20.1969 18.994 20.3019 19.2671 20.2996C19.5402 20.2972 19.8015 20.1876 19.9946 19.9945C20.1877 19.8014 20.2973 19.5401 20.2997 19.267C20.302 18.9939 20.197 18.7307 20.0073 18.5343L13.9729 12.4999L20.0073 6.46552Z" fill="black" />
          </svg>
        </span>
        <div class="otw-search-input-container">
          <span>
            <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
              <mask id="mask0_95_650" style="mask-type:luminance" maskUnits="userSpaceOnUse" x="0" y="0" width="26" height="26">
                <path d="M25.5 0.5H0.5V25.5H25.5V0.5Z" fill="white" />
              </mask>
              <g mask="url(#mask0_95_650)">
                <path d="M11.4375 18.2082C15.1769 18.2082 18.2083 15.1768 18.2083 11.4373C18.2083 7.69791 15.1769 4.6665 11.4375 4.6665C7.69807 4.6665 4.66666 7.69791 4.66666 11.4373C4.66666 15.1768 7.69807 18.2082 11.4375 18.2082Z" stroke="black" stroke-width="1.04167" stroke-linejoin="round" />
                <path d="M20.965 21.7018C21.1684 21.9052 21.4982 21.9052 21.7017 21.7018C21.905 21.4984 21.905 21.1686 21.7017 20.9652L20.965 21.7018ZM21.7017 20.9652L16.4933 15.7568L15.7567 16.4935L20.965 21.7018L21.7017 20.9652Z" fill="black" />
              </g>
            </svg>
          </span>
          <input type="search" name="" id="" placeholder="<?php echo __($placeholder, 'otwsearch'); ?>">
        </div>

        <!-- Results Container -->
        <div class="otw-search-results-container">


        </div>
        <!-- End of Results Container -->
      </div>
    </div>
<?php
  }
}
