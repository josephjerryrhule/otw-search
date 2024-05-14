jQuery(document).ready(function ($) {
  // Function to load search contents when the search icon is clicked
  function loadSearchContents() {
    // Show loading indicator
    $(".otw-search-results-container").addClass("loading");

    // Load search contents via AJAX
    $.ajax({
      url: ajax_data.ajax_url,
      type: "POST",
      data: {
        action: "custom_search_action",
        search_term: "",
      },
      success: function (response) {
        // Hide loading indicator
        $(".otw-search-results-container").removeClass("loading");

        // Update categories and products area with the AJAX response
        $(".otw-search-results-container").html(response);
      },
      error: function (error) {
        console.log(error);
      },
    });
  }

  // Load search contents when the search icon is clicked
  $(".otw-search-icon").on("click", function () {
    $(".otw-search-parent-container").addClass("open");
    loadSearchContents();
  });

  // Close search when close icon is clicked
  $(".otw-search-close-icon").on("click", function () {
    $(".otw-search-parent-container").removeClass("open");
  });
});

jQuery(document).ready(function ($) {
  $('.otw-search-input-container input[type="search"]').on(
    "input",
    function () {
      // Show loading indicator
      $(".otw-search-results-container").addClass("loading");

      var searchTerm = $(this).val();
      $.ajax({
        url: ajax_data.ajax_url, // WordPress AJAX URL
        type: "POST",
        data: {
          action: "custom_search_action", // AJAX action name
          search_term: searchTerm,
        },
        success: function (response) {
          // Hide loading indicator
          $(".otw-search-results-container").removeClass("loading");

          // Update categories and products area with the AJAX response
          $(".otw-search-results-container").html(response);
        },
        error: function (error) {
          console.log(error);
        },
      });
    }
  );
});

jQuery(document).ready(function ($) {
  // Function to add product to recently viewed
  function addToRecentlyViewed(productId) {
    // AJAX request to add product to recently viewed
    $.ajax({
      url: ajax_data.ajax_url, // WordPress AJAX URL
      type: "POST",
      data: {
        action: "add_to_recently_viewed", // AJAX action hook
        product_id: productId, // Product ID to add
      },
      success: function (response) {
        console.log("Product added to recently viewed:", productId);
      },
      error: function (error) {
        console.error("Error adding product to recently viewed:", error);
      },
    });
  }

  // Example: Listen for click events on product links and add them to recently viewed
  $(".product").on("click", function (e) {
    e.preventDefault();
    var productId = $(this).data("product-id"); // Get product ID from data attribute
    addToRecentlyViewed(productId); // Add product to recently viewed
  });
});

jQuery(document).ready(function ($) {
  // Function to fetch recently viewed products via AJAX
  function fetchRecentlyViewedProducts() {
    $.ajax({
      url: ajax_data.ajax_url,
      type: "POST",
      data: {
        action: "get_recently_viewed_products",
      },
      success: function (response) {
        if (response.success) {
          // Handle the response data (recently viewed products HTML)
          var recentlyViewedProductsHtml = response.data;
          // Display the recently viewed products HTML on the page
          displayRecentlyViewedProducts(recentlyViewedProductsHtml);
        } else {
          console.error("Error fetching recently viewed products");
        }
      },
      error: function (error) {
        console.error("Error fetching recently viewed products:", error);
      },
    });
  }

  // Function to display recently viewed products HTML on the page
  function displayRecentlyViewedProducts(recentlyViewedProductsHtml) {
    // Display the HTML markup in the recently viewed container
    $(
      ".otw-search-recently-viewed-area .otw-search-results-products-area-content"
    ).html(recentlyViewedProductsHtml);
  }

  // Fetch recently viewed products when the page loads
  fetchRecentlyViewedProducts();
});
