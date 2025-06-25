<?php

namespace WeDevs\Dokan\Chatbot;

use WeDevs\Dokan\Contracts\Hookable;
use Exception;

class Assets implements Hookable
{
    /**
     * Register hooks
     */
    public function register_hooks(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets(): void
    {
        // Only enqueue if chatbot should be loaded
        if (!$this->should_load_chatbot()) {
            return;
        }

        try {
            $this->enqueue_chatbot_scripts();
            $this->localize_script();
        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error enqueuing frontend assets: " . $e->getMessage());
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(): void
    {
        $screen = get_current_screen();

        // Only enqueue on Dokan settings pages
        if (!$screen || !in_array($screen->id, ['toplevel_page_dokan', 'dokan_page_dokan-settings'])) {
            return;
        }

        try {
            // Check if admin build files exist
            $admin_js_path = DOKAN_CHATBOT_PATH . 'assets/js/build/admin.js';
            $admin_asset_path = DOKAN_CHATBOT_PATH . 'assets/js/build/admin.asset.php';
            
            if (file_exists($admin_js_path) && file_exists($admin_asset_path)) {
                $asset_data = include $admin_asset_path;
                
                wp_enqueue_script(
                    'dokan-chatbot-admin',
                    DOKAN_CHATBOT_ASSETS . 'js/build/admin.js',
                    $asset_data['dependencies'] ?? ['wp-element', 'wp-components', 'wp-i18n'],
                    $asset_data['version'] ?? DOKAN_CHATBOT_VERSION,
                    true
                );

                // Enqueue react build css
                wp_enqueue_style(
                    'dokan-chatbot-react-build-css',
                    DOKAN_CHATBOT_ASSETS . 'js/build/admin.css',
                    [],
                    time()
                );
                // Localize admin script
                wp_localize_script('dokan-chatbot-admin', 'dokanChatbotAdmin', [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'restUrl' => untrailingslashit(rest_url('dokan/v1/chatbot')),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'strings' => $this->get_admin_localized_strings(),
                ]);

            } else {
                // Fallback to old admin assets if build files don't exist
                wp_enqueue_style(
                    'dokan-chatbot-admin-style',
                    DOKAN_CHATBOT_ASSETS . 'css/admin.css',
                    [],
                    DOKAN_CHATBOT_VERSION
                );

                wp_enqueue_script(
                    'dokan-chatbot-admin-script',
                    DOKAN_CHATBOT_ASSETS . 'js/admin.js',
                    ['jquery'],
                    DOKAN_CHATBOT_VERSION,
                    true
                );
            }
        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error enqueuing admin assets: " . $e->getMessage());
        }
    }

    /**
     * Enqueue chatbot scripts
     */
    private function enqueue_chatbot_scripts(): void
    {
        // Check if React build files exist
        $chatbot_js_path = DOKAN_CHATBOT_PATH . 'assets/js/build/chatbot.js';
        $chatbot_asset_path = DOKAN_CHATBOT_PATH . 'assets/js/build/chatbot.asset.php';
        
        if (file_exists($chatbot_js_path) && file_exists($chatbot_asset_path)) {
            // Use React build files
            $asset_data = include $chatbot_asset_path;
            
            wp_enqueue_script(
                'dokan-chatbot-script',
                DOKAN_CHATBOT_ASSETS . 'js/build/chatbot.js',
                $asset_data['dependencies'] ?? ['wp-element', 'wp-components', 'wp-i18n', 'wp-api-fetch'],
                $asset_data['version'] ?? DOKAN_CHATBOT_VERSION,
                true
            );

            // Enqueue react build css
            wp_enqueue_style(
                'dokan-chatbot-react-build-css',
                DOKAN_CHATBOT_ASSETS . 'js/build/chatbot.css',
                [],
                time()
            );
        } else {
            // Fallback to old vanilla JS if build files don't exist
            wp_enqueue_style(
                'dokan-chatbot-style',
                DOKAN_CHATBOT_ASSETS . 'css/chatbot.css',
                [],
                DOKAN_CHATBOT_VERSION
            );
            
            wp_enqueue_script(
                'dokan-chatbot-script',
                DOKAN_CHATBOT_ASSETS . 'js/chatbot.js',
                ['jquery', 'wp-api-fetch'],
                DOKAN_CHATBOT_VERSION,
                true
            );
        }
    }

    /**
     * Localize script with data
     */
    private function localize_script(): void
    {
        wp_localize_script('dokan-chatbot-script', 'dokanChatbot', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => untrailingslashit(rest_url('dokan/v1/chatbot')),
            'nonce' => wp_create_nonce('wp_rest'),
            'userRole' => $this->get_user_role(),
            'userId' => get_current_user_id(),
            'vendorId' => dokan_get_current_user_id(),
            'widgetPosition' => get_option('dokan_chatbot_widget_position', 'bottom-right'),
            'strings' => $this->get_localized_strings(),
            'settings' => $this->get_chatbot_settings(),
        ]);
    }

    /**
     * Get localized strings
     */
    private function get_localized_strings(): array
    {
        return [
            'title' => __('AI Assistant', 'dokan-chatbot'),
            'chatWithAI' => __('Chat with AI', 'dokan-chatbot'),
            'sendMessage' => __('Send Message', 'dokan-chatbot'),
            'inputPlaceholder' => __('Type your message...', 'dokan-chatbot'),
            'switchToVendor' => __('Switch to Vendor Mode', 'dokan-chatbot'),
            'switchToCustomer' => __('Switch to Customer Mode', 'dokan-chatbot'),
            'loading' => __('AI is thinking...', 'dokan-chatbot'),
            'error' => __('Something went wrong. Please try again.', 'dokan-chatbot'),
            'welcomeTitle' => __('Welcome to AI Assistant', 'dokan-chatbot'),
            'welcomeMessage' => get_option('dokan_chatbot_welcome_message', __('How can I help you today?', 'dokan-chatbot')),
            'rateLimitExceeded' => __('Rate limit exceeded. Please wait before sending another message.', 'dokan-chatbot'),
            'invalidMessage' => __('Invalid message. Please check your input.', 'dokan-chatbot'),
            'suggestionsTitle' => __('Quick suggestions:', 'dokan-chatbot'),
            'clearChat' => __('Clear chat', 'dokan-chatbot'),
            'closeChat' => __('Close chat', 'dokan-chatbot'),
            'messagesLeft' => __('Messages left:', 'dokan-chatbot'),
        ];
    }

    /**
     * Get admin localized strings
     */
    private function get_admin_localized_strings(): array
    {
        return [
            'saveSettings' => __('Save Settings', 'dokan-chatbot'),
            'saving' => __('Saving...', 'dokan-chatbot'),
            'settingsSaved' => __('Settings saved successfully!', 'dokan-chatbot'),
            'saveError' => __('Error saving settings. Please try again.', 'dokan-chatbot'),
            'enableChatbot' => __('Enable Chatbot', 'dokan-chatbot'),
            'enableChatbotDesc' => __('Enable or disable the chatbot functionality.', 'dokan-chatbot'),
            'apiKey' => __('API Key', 'dokan-chatbot'),
            'apiKeyPlaceholder' => __('Enter your API key', 'dokan-chatbot'),
            'aiModel' => __('AI Model', 'dokan-chatbot'),
            'maxMessages' => __('Max Messages Per Session', 'dokan-chatbot'),
            'temperature' => __('Temperature (Creativity)', 'dokan-chatbot'),
            'focused' => __('Focused', 'dokan-chatbot'),
            'creative' => __('Creative', 'dokan-chatbot'),
        ];
    }

    /**
     * Get chatbot settings
     */
    private function get_chatbot_settings(): array
    {
        return [
            'enabled' => get_option('dokan_chatbot_enabled', 'yes') === 'yes',
            'vendorAccess' => get_option('dokan_chatbot_vendor_access', 'yes') === 'yes',
            'customerAccess' => get_option('dokan_chatbot_customer_access', 'yes') === 'yes',
            'maxMessagesPerSession' => (int) get_option('dokan_chatbot_max_messages_per_session', 50),
            'conversationRetentionDays' => (int) get_option('dokan_chatbot_conversation_retention_days', 30),
            'model' => get_option('dokan_chatbot_ai_model', 'gpt-3.5-turbo'),
            'temperature' => (float) get_option('dokan_chatbot_temperature', 0.7),
        ];
    }

    /**
     * Check if chatbot should be loaded
     */
    private function should_load_chatbot(): bool
    {
        // Check if chatbot is enabled
        if ('yes' !== get_option('dokan_chatbot_enabled', 'yes')) {
            return false;
        }

        // Check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }

        // Check if Dokan AI is configured
        if (!$this->is_dokan_ai_configured()) {
            return false;
        }

        // Check role-based access
        $user_role = $this->get_user_role();
        if ('vendor' === $user_role && 'yes' !== get_option('dokan_chatbot_vendor_access', 'yes')) {
            return false;
        }

        if ('customer' === $user_role && 'yes' !== get_option('dokan_chatbot_customer_access', 'yes')) {
            return false;
        }

        return true;
    }

    /**
     * Get user role for chatbot
     */
    private function get_user_role(): string
    {
        $user_id = get_current_user_id();

        if (dokan_is_user_seller($user_id)) {
            return 'vendor';
        }

        return 'customer';
    }

    /**
     * Check if Dokan AI is configured
     */
    private function is_dokan_ai_configured(): bool
    {
        if (!class_exists('WeDevs\Dokan\Intelligence\Manager')) {
            return false;
        }

        try {
            $container = dokan()->get_container();
            if (!$container) {
                return false;
            }

            $manager = $container->get('WeDevs\Dokan\Intelligence\Manager');
            return $manager && $manager->is_configured();
        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error checking AI configuration: " . $e->getMessage());
            return false;
        }
    }
}
