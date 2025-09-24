/* Auto AI Studio Admin JavaScript */

(function ($) {
  "use strict";

  const AutoAIStudio = {
    init: function () {
      this.bindEvents();
      this.initializeTooltips();
      this.loadDashboardStats();
    },

    bindEvents: function () {
      // Test AI Connection
      $(document).on("click", ".test-connection-btn", this.testConnection);

      // Campaign type selection
      $(document).on("click", ".campaign-type", this.selectCampaignType);

      // Generation method selection
      $(document).on("click", ".method-option", this.selectGenerationMethod);

      // Frequency selection
      $(document).on("click", ".frequency-option", this.selectFrequency);

      // Toggle switches
      $(document).on("change", ".toggle-switch input", this.handleToggleChange);

      // Form submissions
      $(document).on("click", ".create-campaign-btn", this.createCampaign);
      $(document).on("click", ".save-settings-btn", this.saveSettings);

      // Real-time stats updates
      setInterval(this.updateStats, 30000); // Update every 30 seconds
    },

    initializeTooltips: function () {
      // Initialize tooltips for help icons
      $("[data-tooltip]").hover(
        function () {
          const tooltip = $(
            '<div class="tooltip">' + $(this).data("tooltip") + "</div>"
          );
          $("body").append(tooltip);
          tooltip
            .css({
              position: "absolute",
              top: $(this).offset().top - tooltip.outerHeight() - 10,
              left:
                $(this).offset().left +
                $(this).outerWidth() / 2 -
                tooltip.outerWidth() / 2,
              backgroundColor: "#333",
              color: "#fff",
              padding: "8px 12px",
              borderRadius: "6px",
              fontSize: "12px",
              zIndex: 10000,
            })
            .fadeIn(200);
        },
        function () {
          $(".tooltip").fadeOut(200, function () {
            $(this).remove();
          });
        }
      );
    },

    testConnection: function (e) {
      e.preventDefault();

      const $btn = $(this);
      const originalText = $btn.text();

      $btn.text("Testing...").prop("disabled", true);

      $.ajax({
        url: autoAIStudio.ajaxurl,
        type: "POST",
        data: {
          action: "auto_ai_studio_test_connection",
          nonce: autoAIStudio.nonce,
        },
        success: function (response) {
          if (response.success) {
            AutoAIStudio.showNotification("Connection successful!", "success");
            $(".connection-status").html(
              '<div class="status-success">' +
                '<span class="dashicons dashicons-yes"></span> ' +
                response.data.message +
                "</div>"
            );
          } else {
            AutoAIStudio.showNotification(
              "Connection failed: " + response.data.message,
              "error"
            );
            $(".connection-status").html(
              '<div class="status-error">' +
                '<span class="dashicons dashicons-no"></span> ' +
                response.data.message +
                "</div>"
            );
          }
        },
        error: function () {
          AutoAIStudio.showNotification("Ajax request failed", "error");
        },
        complete: function () {
          $btn.text(originalText).prop("disabled", false);
        },
      });
    },

    selectCampaignType: function () {
      $(".campaign-type").removeClass("selected");
      $(this).addClass("selected");

      const type = $(this).data("type");
      $("#campaign-type").val(type);

      // Show/hide relevant options based on type
      AutoAIStudio.updateOptionsForType(type);
    },

    selectGenerationMethod: function () {
      $(".method-option").removeClass("selected");
      $(this).addClass("selected");

      const method = $(this).data("method");
      $("#generation-method").val(method);

      // Update form fields based on method
      if (method === "smart") {
        $(".smart-options").show();
        $(".title-options").hide();
      } else {
        $(".smart-options").hide();
        $(".title-options").show();
      }
    },

    selectFrequency: function () {
      $(".frequency-option").removeClass("selected");
      $(this).addClass("selected");

      const frequency = $(this).data("frequency");
      $("#frequency").val(frequency);

      // Update publishing stats
      AutoAIStudio.updatePublishingStats(frequency);
    },

    updateOptionsForType: function (type) {
      // Hide all type-specific options
      $(".type-options").hide();

      // Show relevant options
      $("." + type + "-options").show();

      // Update configuration fields
      switch (type) {
        case "news":
          $(".news-sources").show();
          $(".keyword-field label").text("Search Keywords");
          break;
        case "videos":
          $(".video-sources").show();
          $(".keyword-field label").text("Video Topics");
          break;
        case "podcast":
          $(".podcast-sources").show();
          $(".keyword-field label").text("Podcast Topics");
          break;
        default:
          $(".keyword-field label").text("Article Keywords");
      }
    },

    updatePublishingStats: function (frequency) {
      let perDay, perWeek, perMonth, costPerArticle;

      switch (frequency) {
        case "every_15_minutes":
          perDay = 96;
          costPerArticle = 0.015;
          break;
        case "every_30_minutes":
          perDay = 48;
          costPerArticle = 0.02;
          break;
        case "hourly":
          perDay = 24;
          costPerArticle = 0.025;
          break;
        case "daily":
          perDay = 1;
          costPerArticle = 0.05;
          break;
        default:
          perDay = 24;
          costPerArticle = 0.025;
      }

      perWeek = perDay * 7;
      perMonth = perDay * 30;

      const dailyCost = perDay * costPerArticle;
      const monthlyCost = perMonth * costPerArticle;

      // Update the stats display
      $(".stat-per-day .number").text(perDay);
      $(".stat-per-week .number").text(perWeek);
      $(".stat-per-month .number").text(perMonth);
      $(".stat-cost .number").text("$" + costPerArticle.toFixed(3));
      $(".daily-cost .cost-amount").text("$" + dailyCost.toFixed(2));
      $(".monthly-cost .cost-amount").text("$" + monthlyCost.toFixed(2));
    },

    handleToggleChange: function () {
      const $toggle = $(this);
      const setting = $toggle.data("setting");
      const value = $toggle.is(":checked");

      // Handle specific toggle changes
      switch (setting) {
        case "humanization":
          if (value) {
            $(".humanization-options").slideDown();
          } else {
            $(".humanization-options").slideUp();
          }
          break;
        case "auto-publish":
          if (value) {
            $(".publishing-options").slideDown();
          } else {
            $(".publishing-options").slideUp();
          }
          break;
        case "include-images":
          if (value) {
            $(".image-options").slideDown();
          } else {
            $(".image-options").slideUp();
          }
          break;
      }
    },

    createCampaign: function (e) {
      e.preventDefault();

      const $btn = $(this);
      const $form = $btn.closest("form");

      // Validate form
      if (!AutoAIStudio.validateCampaignForm($form)) {
        return false;
      }

      const originalText = $btn.text();
      $btn.text("Creating Campaign...").prop("disabled", true);

      // Collect form data
      const formData = {
        action: "auto_ai_studio_create_campaign",
        nonce: autoAIStudio.nonce,
        name: $("#campaign-name").val(),
        type: $("#campaign-type").val(),
        keywords: $("#keywords").val(),
        frequency: $("#frequency").val(),
        settings: AutoAIStudio.collectCampaignSettings(),
      };

      $.ajax({
        url: autoAIStudio.ajaxurl,
        type: "POST",
        data: formData,
        success: function (response) {
          if (response.success) {
            AutoAIStudio.showNotification(
              "Campaign created successfully!",
              "success"
            );

            // Redirect to campaign manager or reset form
            setTimeout(function () {
              window.location.href = "admin.php?page=auto-ai-studio-campaigns";
            }, 2000);
          } else {
            AutoAIStudio.showNotification(
              "Failed to create campaign: " + response.data.message,
              "error"
            );
          }
        },
        error: function () {
          AutoAIStudio.showNotification("Ajax request failed", "error");
        },
        complete: function () {
          $btn.text(originalText).prop("disabled", false);
        },
      });
    },

    collectCampaignSettings: function () {
      return {
        article_type:
          $(".campaign-type.selected").data("subtype") || "standard",
        generation_method: $("#generation-method").val(),
        word_count: $("#word-count").val() || 800,
        enable_humanization: $("#enable-humanization").is(":checked"),
        humanization_provider: $("#humanization-provider").val(),
        humanization_mode: $("#humanization-mode").val(),
        writing_tone: $("#writing-tone").val(),
        model: $("#openrouter-model").val(),
        auto_publish: $("#auto-publish").is(":checked"),
        content_mode: $("#content-mode").val(),
        author_id: $("#author").val(),
        categories: $("#categories").val(),
        include_images: $("#include-images").is(":checked"),
        language: $("#language").val(),
        country: $("#country").val(),
        source_languages: $("#source-languages").val(),
      };
    },

    validateCampaignForm: function ($form) {
      let isValid = true;
      const errors = [];

      // Campaign name
      if (!$("#campaign-name").val().trim()) {
        errors.push("Campaign name is required");
        isValid = false;
      }

      // Campaign type
      if (!$(".campaign-type.selected").length) {
        errors.push("Please select a campaign type");
        isValid = false;
      }

      // Keywords
      if (!$("#keywords").val().trim()) {
        errors.push("Keywords are required");
        isValid = false;
      }

      // Frequency
      if (!$(".frequency-option.selected").length) {
        errors.push("Please select a frequency");
        isValid = false;
      }

      if (!isValid) {
        AutoAIStudio.showNotification(errors.join("<br>"), "error");
      }

      return isValid;
    },

    loadDashboardStats: function () {
      $.ajax({
        url: autoAIStudio.ajaxurl,
        type: "POST",
        data: {
          action: "auto_ai_studio_get_stats",
          nonce: autoAIStudio.nonce,
        },
        success: function (response) {
          if (response.success) {
            AutoAIStudio.updateDashboardStats(response.data);
          }
        },
      });
    },

    updateDashboardStats: function (stats) {
      $(".total-campaigns .stat-number").text(stats.total_campaigns || 0);
      $(".active-campaigns .stat-number").text(stats.active_campaigns || 0);
      $(".total-posts .stat-number").text(stats.total_posts || 0);
      $(".posts-today .stat-number").text(stats.posts_today || 0);
      $(".automation-types .stat-number").text(stats.automation_types || 4);
      $(".minimum-frequency .stat-number").text(
        stats.minimum_frequency || "10min"
      );
      $(".automated-status .stat-number").text(
        stats.automated_status || "24/7"
      );
    },

    updateStats: function () {
      AutoAIStudio.loadDashboardStats();
    },

    showNotification: function (message, type) {
      // Remove existing notifications
      $(".auto-ai-notification").remove();

      const notificationClass =
        "auto-ai-notification " +
        (type === "success" ? "notice-success" : "notice-error");
      const icon = type === "success" ? "yes" : "no";

      const notification = $(`
                <div class="${notificationClass}" style="position: fixed; top: 32px; right: 20px; z-index: 10001; max-width: 400px; padding: 12px 16px; background: white; border-left: 4px solid ${
        type === "success" ? "#46b450" : "#dc3232"
      }; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 4px;">
                    <div style="display: flex; align-items: center;">
                        <span class="dashicons dashicons-${icon}" style="margin-right: 8px; color: ${
        type === "success" ? "#46b450" : "#dc3232"
      };"></span>
                        <div>${message}</div>
                    </div>
                </div>
            `);

      $("body").append(notification);

      // Auto-hide after 5 seconds
      setTimeout(function () {
        notification.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);

      // Allow manual close
      notification.click(function () {
        $(this).fadeOut(200, function () {
          $(this).remove();
        });
      });
    },

    saveSettings: function (e) {
      e.preventDefault();

      const $btn = $(this);
      const originalText = $btn.text();

      $btn.text("Saving...").prop("disabled", true);

      // Show success message after a short delay (simulated)
      setTimeout(function () {
        AutoAIStudio.showNotification(
          "Settings saved successfully!",
          "success"
        );
        $btn.text(originalText).prop("disabled", false);
      }, 1000);
    },
  };

  // Initialize when document is ready
  $(document).ready(function () {
    AutoAIStudio.init();
  });
})(jQuery);
