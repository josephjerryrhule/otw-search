jQuery(document).ready(function ($) {
  $(".otw-search-icon").on("click", function () {
    $(".otw-search-parent-container").addClass("open");
  });

  $(".otw-search-close-icon").on("click", function () {
    $(".otw-search-parent-container").removeClass("open");
  });

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
    var recentlyViewed = getRecentlyViewed(); // Retrieve recently viewed products from localStorage
    if (!recentlyViewed.includes(productId)) {
      recentlyViewed.push(productId); // Add product ID to recently viewed
      localStorage.setItem("recentlyViewed", JSON.stringify(recentlyViewed)); // Save updated recently viewed list to localStorage
    }
  }

  // Function to retrieve recently viewed products from localStorage
  function getRecentlyViewed() {
    var recentlyViewed = localStorage.getItem("recentlyViewed");
    return recentlyViewed ? JSON.parse(recentlyViewed) : []; // Parse JSON string to array
  }

  // Example: Listen for click events on product links and add them to recently viewed
  $(".product").on("click", function (e) {
    e.preventDefault();
    var productId = $(this).data("product-id"); // Get product ID from data attribute
    addToRecentlyViewed(productId); // Add product to recently viewed
  });
});
