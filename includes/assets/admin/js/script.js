jQuery(document).ready(function ($) {
  $("#speed-mate-trash-spam-cleanup-btn").on("click", function (e) {
    e.preventDefault();

    // Disable the button while processing
    const $button = $(this);
    $button.prop("disabled", true).text("Running Cleanup...");

    // Send AJAX request
    $.ajax({
      url: speedMateAjax.ajaxUrl,
      type: "POST",
      dataType: "json",
      data: {
        action: "speed_mate_trash_spam_cleanup",
        nonce: speedMateAjax.trashSpamNonce,
      },
      success: function (response) {
        if (response.success) {
          // Show success message
          $("#speed-mate-trash-spam-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("updated");
        } else {
          // Show error message
          $("#speed-mate-trash-spam-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("error");
        }
      },
      error: function () {
        // Show general error message
        $("#speed-mate-trash-spam-success-message")
          .text("An error occurred while running the cleanup.")
          .removeClass("hidden")
          .addClass("error");
      },
      complete: function () {
        // Re-enable the button
        $button.prop("disabled", false).text("Run Cleanup Now");
      },
    });
  });

  // revisions cleanup button
  $("#speed-mate-revisions-cleanup-btn").on("click", function (e) {
    e.preventDefault();

    // Disable the button while processing
    const $button = $(this);
    $button.prop("disabled", true).text("Running Cleanup...");

    // Send AJAX request
    $.ajax({
      url: speedMateAjax.ajaxUrl,
      type: "POST",
      dataType: "json",
      data: {
        action: "speed_mate_revisions_cleanup",
        nonce: speedMateAjax.revisionsCleanupNonce,
      },
      success: function (response) {
        if (response.success) {
          // Show success message
          $("#speed-mate-revisions-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("updated");
        } else {
          // Show error message
          $("#speed-mate-revisions-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("error");
        }
      },
      error: function () {
        // Show general error message
        $("#speed-mate-revisions-success-message")
          .text("An error occurred while running the cleanup.")
          .removeClass("hidden")
          .addClass("error");
      },
      complete: function () {
        // Re-enable the button
        $button.prop("disabled", false).text("Run Cleanup Now");
      },
    });
  });
});
