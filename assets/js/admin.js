/**
 * Dokan AI Chatbot Admin JavaScript
 */

(function($) {
    'use strict';

    class DokanChatbotAdmin {
        constructor() {
            this.init();
        }

        init() {
            this.bindEvents();
            this.setupConditionalFields();
        }

        bindEvents() {
            // Handle enable/disable toggle
            $('#dokan_chatbot_enabled').on('change', function() {
                const isEnabled = $(this).is(':checked');
                $('.dokan-chatbot-dependent-field').toggle(isEnabled);
            });

            // Handle vendor access toggle
            $('#dokan_chatbot_vendor_access').on('change', function() {
                const isEnabled = $(this).is(':checked');
                $('.dokan-chatbot-vendor-dependent').toggle(isEnabled);
            });

            // Handle customer access toggle
            $('#dokan_chatbot_customer_access').on('change', function() {
                const isEnabled = $(this).is(':checked');
                $('.dokan-chatbot-customer-dependent').toggle(isEnabled);
            });

            // Save settings with AJAX
            $('.dokan-settings-form').on('submit', function(e) {
                // Add loading state
                const submitButton = $(this).find('input[type="submit"]');
                const originalText = submitButton.val();
                submitButton.val('Saving...').prop('disabled', true);

                // Re-enable after a delay (in case of errors)
                setTimeout(() => {
                    submitButton.val(originalText).prop('disabled', false);
                }, 5000);
            });
        }

        setupConditionalFields() {
            // Initial state setup
            const chatbotEnabled = $('#dokan_chatbot_enabled').is(':checked');
            const vendorEnabled = $('#dokan_chatbot_vendor_access').is(':checked');
            const customerEnabled = $('#dokan_chatbot_customer_access').is(':checked');

            $('.dokan-chatbot-dependent-field').toggle(chatbotEnabled);
            $('.dokan-chatbot-vendor-dependent').toggle(vendorEnabled);
            $('.dokan-chatbot-customer-dependent').toggle(customerEnabled);
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        new DokanChatbotAdmin();
    });

})(jQuery); 