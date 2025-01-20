jQuery(document).ready(function ($) {
  $("#wp-fastify-trash-spam-cleanup-btn").on("click", function (e) {
    e.preventDefault();

    // Disable the button while processing
    const $button = $(this);
    $button.prop("disabled", true).text("Running Cleanup...");

    // Send AJAX request
    $.ajax({
      url: wpFastifyAjax.ajaxUrl,
      type: "POST",
      dataType: "json",
      data: {
        action: "wp_fastify_trash_spam_cleanup",
        nonce: wpFastifyAjax.trashSpamNonce,
      },
      success: function (response) {
        if (response.success) {
          // Show success message
          $("#wp-fastify-trash-spam-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("updated");
        } else {
          // Show error message
          $("#wp-fastify-trash-spam-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("error");
        }
      },
      error: function () {
        // Show general error message
        $("#wp-fastify-trash-spam-success-message")
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
  $("#wp-fastify-revisions-cleanup-btn").on("click", function (e) {
    e.preventDefault();

    // Disable the button while processing
    const $button = $(this);
    $button.prop("disabled", true).text("Running Cleanup...");

    // Send AJAX request
    $.ajax({
      url: wpFastifyAjax.ajaxUrl,
      type: "POST",
      dataType: "json",
      data: {
        action: "wp_fastify_revisions_cleanup",
        nonce: wpFastifyAjax.revisionsCleanupNonce,
      },
      success: function (response) {
        if (response.success) {
          // Show success message
          $("#wp-fastify-revisions-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("updated");
        } else {
          // Show error message
          $("#wp-fastify-revisions-success-message")
            .text(response.data.message)
            .removeClass("hidden")
            .addClass("error");
        }
      },
      error: function () {
        // Show general error message
        $("#wp-fastify-revisions-success-message")
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
