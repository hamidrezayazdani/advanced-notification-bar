jQuery(function ($) {
  const notificationBar = $('.advanced-notification-bar');
  const closeButton = $('.close-button');

  if (notificationBar.length) {
    function updateBodyPadding() {
      const notificationHeight = notificationBar.outerHeight();
      $('body').css('padding-top', notificationHeight + 'px');
    }

    // Initial padding update
    updateBodyPadding();

    // Update padding on window resize
    $(window).on('resize', updateBodyPadding);

    // Handle close button click
    closeButton.on('click', function() {
      notificationBar.addClass('is-hidden');
      $('body').css('padding-top', 0);

      // Store the closed state in session storage
      sessionStorage.setItem('notification-bar-closed', 'true');
    });

    // Check if notification was previously closed
    if (sessionStorage.getItem('notification-bar-closed') === 'true') {
      notificationBar.addClass('is-hidden');
      $('body').css('padding-top', 0);
    }
  }
});