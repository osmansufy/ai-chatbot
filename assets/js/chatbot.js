/**
 * Dokan AI Chatbot JavaScript
 *
 * @package DokanChatbot
 */

(function ($) {
  "use strict";

  class DokanChatbot {
    constructor() {
      this.isOpen = false;
      this.isLoading = false;
      this.currentRole = dokanChatbot.userRole || "customer";
      this.messages = [];
      this.suggestions = [];
      this.remainingMessages =
        dokanChatbot.settings?.maxMessagesPerSession || 50;

      this.init();
    }

    init() {
      this.bindEvents();
      this.loadSuggestions();
      this.setupRoleSelector();
      this.updateRemainingMessages();
    }

    bindEvents() {
      // Toggle chatbot
      $("#dokan-chatbot-toggle").on("click", () => this.toggleChat());

      // Close chatbot
      $("#dokan-chatbot-close").on("click", () => this.closeChat());

      // Send message
      $("#dokan-chatbot-send").on("click", () => this.sendMessage());
      $("#dokan-chatbot-input").on("keypress", (e) => {
        if (e.which === 13 && !e.shiftKey) {
          e.preventDefault();
          this.sendMessage();
        }
      });

      // Auto-resize textarea
      $("#dokan-chatbot-input").on("input", function () {
        this.style.height = "auto";
        this.style.height = Math.min(this.scrollHeight, 100) + "px";
      });

      // Role change
      $("#dokan-chatbot-role").on("change", (e) =>
        this.switchRole(e.target.value)
      );

      // Click outside to close
      $(document).on("click", (e) => {
        if (
          !$(e.target).closest("#dokan-chatbot-widget").length &&
          this.isOpen
        ) {
          this.closeChat();
        }
      });

      // Clear chat button
      $("#dokan-chatbot-clear").on("click", () => this.clearChat());
    }

    toggleChat() {
      if (this.isOpen) {
        this.closeChat();
      } else {
        this.openChat();
      }
    }

    openChat() {
      this.isOpen = true;
      $("#dokan-chatbot-interface").addClass("active");
      $("#dokan-chatbot-input").focus();
      this.scrollToBottom();
    }

    closeChat() {
      this.isOpen = false;
      $("#dokan-chatbot-interface").removeClass("active");
    }

    async sendMessage() {
      const input = $("#dokan-chatbot-input");
      const message = input.val().trim();

      if (!message || this.isLoading) {
        return;
      }

      // Check remaining messages
      if (this.remainingMessages <= 0) {
        this.addMessage(dokanChatbot.strings.rateLimitExceeded, "ai", "error");
        return;
      }

      // Add user message to chat
      this.addMessage(message, "user");
      input.val("").trigger("input");

      // Show loading
      this.showLoading();

      try {
        const response = await this.makeApiRequest("chat", {
          message: message,
          role: this.currentRole,
          vendor_id: dokanChatbot.vendorId || null,
        });
        console.log(response);

        if (response.response) {
          // hide suggestions
          $("#dokan-chatbot-suggestions").hide();
          this.addMessage(response.response, "ai");
          this.remainingMessages--;
          this.updateRemainingMessages();
        } else {
          this.handleApiError(response);
        }
      } catch (error) {
        console.error("Chatbot error:", error);
        let errorMessage = dokanChatbot.strings.error;
        if ( error && error.message ) {
            errorMessage = error.message;
        }
        this.addMessage(errorMessage, "ai", "error");
      } finally {
        this.hideLoading();
      }
    }

    handleApiError(response) {
      let errorMessage = dokanChatbot.strings.error;

      if (response.data && response.data.message) {
        errorMessage = response.data.message;
      } else if ( response.message ) {
        errorMessage = response.message;
      } else if (response.code === "rate_limit_exceeded") {
        errorMessage = dokanChatbot.strings.rateLimitExceeded;
        this.remainingMessages = 0;
        this.updateRemainingMessages();
      } else if (response.code === "invalid_message") {
        errorMessage = dokanChatbot.strings.invalidMessage;
      }

      this.addMessage(errorMessage, "ai", "error");
    }

    addMessage(content, type, messageType = "normal") {
      const messageHtml = this.createMessageHtml(content, type, messageType);
      $("#dokan-chatbot-messages").append(messageHtml);
      this.scrollToBottom();
      this.messages.push({ content, type, timestamp: new Date(), messageType });
    }

    createMessageHtml(content, type, messageType = "normal") {
      const time = new Date().toLocaleTimeString([], {
        hour: "2-digit",
        minute: "2-digit",
      });
      const messageClass =
        type === "user"
          ? "dokan-chatbot-message-user"
          : "dokan-chatbot-message-ai";
      const errorClass =
        messageType === "error" ? "dokan-chatbot-message-error" : "";

      return `
                <div class="dokan-chatbot-message ${messageClass} ${errorClass}">
                    <div class="dokan-chatbot-message-content">
                        <p>${this.escapeHtml(content)}</p>
                    </div>
                    <div class="dokan-chatbot-message-time">${time}</div>
                </div>
            `;
    }

    async loadSuggestions() {
      try {
        const response = await this.makeApiRequest("suggestions", {
          role: this.currentRole,
        }, "GET");

        if (response.suggestions) {
          this.suggestions = response.suggestions;
          this.renderSuggestions();
        }
      } catch (error) {
        console.error("Failed to load suggestions:", error);
      }
    }

    renderSuggestions() {
      const container = $("#dokan-chatbot-suggestions");
      container.empty();

      this.suggestions.forEach((suggestion) => {
        const suggestionHtml = `
                    <div class="dokan-chatbot-suggestion" data-suggestion="${this.escapeHtml(
                      suggestion
                    )}">
                        ${this.escapeHtml(suggestion)}
                    </div>
                `;
        container.append(suggestionHtml);
      });

      // Bind suggestion clicks
      $(".dokan-chatbot-suggestion").on("click", (e) => {
        const suggestion = $(e.currentTarget).data("suggestion");
        $("#dokan-chatbot-input").val(suggestion).trigger("input");
        this.sendMessage();
      });
    }

    async switchRole(newRole) {
      if (newRole === this.currentRole) {
        return;
      }

      try {
        const response = await this.makeApiRequest("role-switch", {
          role: newRole,
        });

        if (response.success) {
          this.currentRole = newRole;
          this.loadSuggestions();
          this.addMessage(
            `Switched to ${newRole} mode. How can I help you?`,
            "ai"
          );
        }
      } catch (error) {
        console.error("Failed to switch role:", error);
        // Revert role selector
        $("#dokan-chatbot-role").val(this.currentRole);
      }
    }

    async clearChat() {
      if (!confirm("Are you sure you want to clear the chat history?")) {
        return;
      }

      try {
        const response = await this.makeApiRequest("clear-history", {});

        if (response.success) {
          $("#dokan-chatbot-messages").empty();
          this.messages = [];
          this.addMessage(dokanChatbot.strings.welcomeMessage, "ai");
          this.remainingMessages =
            dokanChatbot.settings?.maxMessagesPerSession || 50;
          this.updateRemainingMessages();
        }
      } catch (error) {
        console.error("Failed to clear chat:", error);
      }
    }

    setupRoleSelector() {
      // Set initial role
      $("#dokan-chatbot-role").val(this.currentRole);

      // Disable vendor option if user is not a vendor
      if (dokanChatbot.userRole !== "vendor") {
        $('#dokan-chatbot-role option[value="vendor"]').prop("disabled", true);
      }
    }

    updateRemainingMessages() {
      const remainingElement = $("#dokan-chatbot-remaining");
      if (remainingElement.length) {
        remainingElement.text(`${this.remainingMessages} messages remaining`);

        if (this.remainingMessages <= 5) {
          remainingElement.addClass("dokan-chatbot-remaining-warning");
        } else {
          remainingElement.removeClass("dokan-chatbot-remaining-warning");
        }
      }
    }

    async makeApiRequest(endpoint, data = {}, method = "POST") {
      let url = dokanChatbot.restUrl + "/" + endpoint;
      let fetchOptions = {
        method: method,
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": dokanChatbot.nonce,
        },
      };

      if (method === "GET") {
        // Append query params for GET
        const params = new URLSearchParams(data).toString();
        if (params) {
          url += (url.includes("?") ? "&" : "?") + params;
        }
      } else {
        fetchOptions.body = JSON.stringify(data);
      }

      const response = await fetch(url, fetchOptions);
      if (!response.ok) {
        let errorData;
        try {
          errorData = await response.json();
        } catch (e) {
          errorData = { message: response.statusText };
        }
        throw errorData;
      }
      return await response.json();
    }

    showLoading() {
      this.isLoading = true;
      $("#dokan-chatbot-loading").show();
      $("#dokan-chatbot-send").prop("disabled", true);
    }

    hideLoading() {
      this.isLoading = false;
      $("#dokan-chatbot-loading").hide();
      $("#dokan-chatbot-send").prop("disabled", false);
    }

    scrollToBottom() {
      const messagesContainer = $("#dokan-chatbot-messages");
      messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
    }

    escapeHtml(text) {
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    }
  }

  // Initialize chatbot when DOM is ready
  $(document).ready(function () {
    if (typeof dokanChatbot !== "undefined") {
      new DokanChatbot();
    }
  });
})(jQuery);
