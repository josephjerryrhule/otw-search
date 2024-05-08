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
      var searchTerm = $(this).val();
      $.ajax({
        url: ajax_data.ajax_url, // WordPress AJAX URL
        type: "POST",
        data: {
          action: "custom_search_action", // AJAX action name
          search_term: searchTerm,
        },
        success: function (response) {
          // Update categories and products area with the AJAX response
          $(".otw-search-results-container").html(response);
          console.log(response); // Log the response data
        },
        error: function (error) {
          console.log(error);
        },
      });
    }
  );
});
