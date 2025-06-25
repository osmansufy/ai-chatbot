<?php

namespace WeDevs\Dokan\Chatbot;

use WeDevs\Dokan\Contracts\Hookable;
use Exception;

class Manager implements Hookable
{
    /**
     * Register hooks
     */
    public function register_hooks(): void
    {
        add_action('wp_footer', [$this, 'render_chatbot_widget']);
        // add_filter('dokan_rest_api_class_map', [$this, 'rest_api_class_map']);

        // Register REST routes directly on `rest_api_init`
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    /**
     * Initialize chatbot
     */
    public function init_chatbot(): void
    {
        // Add chatbot widget to vendor dashboard
        if (dokan_is_seller_dashboard()) {
            add_action('dokan_dashboard_content_inside_after', [$this, 'render_vendor_chatbot']);
        }
    }

    /**
     * Register REST routes
     */
    public function register_rest_routes(): void
    {
        try {
            $controller = new REST\ChatbotController();
            $controller->register_routes();
            error_log("Dokan Chatbot: REST routes registered successfully.");
        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error registering REST routes: " . $e->getMessage());
        }
    }

    /**
     * Map REST API classes
     */
    public function rest_api_class_map(array $class_map): array
    {

        $class_map[DOKAN_CHATBOT_PATH . 'includes/Chatbot/REST/ChatbotController.php'] = 'WeDevs\Dokan\Chatbot\REST\ChatbotController';
        return $class_map;
    }

    /**
     * Render chatbot widget
     */
    public function render_chatbot_widget(): void
    {
        if (!$this->should_load_chatbot()) {
            return;
        }

        include DOKAN_CHATBOT_PATH . 'templates/chatbot/widget.php';
    }

    /**
     * Render vendor chatbot in dashboard
     */
    public function render_vendor_chatbot(): void
    {
        if (!$this->should_load_chatbot()) {
            return;
        }

        echo '<div id="dokan-vendor-chatbot" class="dokan-chatbot-dashboard"></div>';
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

    /**
     * Get chatbot service instance
     */
    public function get_chatbot_service(): Services\ChatbotService
    {
        return new Services\ChatbotService();
    }

    /**
     * Get context builder instance
     */
    public function get_context_builder(): Services\ContextBuilder
    {
        return new Services\ContextBuilder();
    }

    /**
     * Get role manager instance
     */
    public function get_role_manager(): Services\RoleManager
    {
        return new Services\RoleManager();
    }

    /**
     * Get chat history instance
     */
    public function get_chat_history(): Utils\ChatHistory
    {
        return new Utils\ChatHistory();
    }

    /**
     * Get prompt templates instance
     */
    public function get_prompt_templates(): Utils\PromptTemplates
    {
        return new Utils\PromptTemplates();
    }
}
