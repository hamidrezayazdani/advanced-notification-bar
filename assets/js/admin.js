jQuery(function ($) {
  // Initialize Select2 for posts
  $('.posts-select2').select2({
    placeholder: 'Search and select posts...',
    minimumInputLength: 2,
    ajax: {
      url: notificationBar.ajaxurl,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          action: 'search_posts',
          nonce: notificationBar.nonce
        };
      },
      processResults: function (data) {
        return {
          results: data.results
        };
      },
      cache: true
    }
  });

  // Initialize Select2 for pages
  $('.pages-select2').select2({
    placeholder: 'Search and select pages...',
    minimumInputLength: 2,
    ajax: {
      url: notificationBar.ajaxurl,
      dataType: 'json',
      delay: 250,
      data: function (params) {
        return {
          search: params.term,
          action: 'search_pages',
          nonce: notificationBar.nonce
        };
      },
      processResults: function (data) {
        return {
          results: data.results
        };
      },
      cache: true
    }
  });

  // Handle visibility radio buttons
  $('input[name="notification_bar_settings[visibility_type]"]').change(function () {
    const selectedValue = $(this).val();

    // Hide all select containers first
    $('#posts-select, #pages-select').hide();

    // Show the relevant select container
    if (selectedValue === 'specific-posts') {
      $('#posts-select').show();
    } else if (selectedValue === 'specific-pages') {
      $('#pages-select').show();
    }
  });

  // Trigger change event on page load to show/hide relevant fields
  $('input[name="notification_bar_settings[visibility_type]"]:checked').trigger('change');
});