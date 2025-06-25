<?php

/**
 * Plugin Name: Dokan AI Chatbot
 * Plugin URI: https://dokan.co/
 * Description: AI-powered chatbot for Dokan marketplace with role-based assistance for vendors and customers.
 * Version: 1.0.1
 * Author: WeDevs
 * Author URI: https://wedevs.com/
 * Text Domain: dokan-chatbot
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('DOKAN_CHATBOT_VERSION', '1.0.1');
define('DOKAN_CHATBOT_FILE', __FILE__);
define('DOKAN_CHATBOT_PATH', plugin_dir_path(__FILE__));
define('DOKAN_CHATBOT_URL', plugin_dir_url(__FILE__));
define('DOKAN_CHATBOT_ASSETS', DOKAN_CHATBOT_URL . 'assets/');

use WeDevs\Dokan\Intelligence\Manager;
use WeDevs\Dokan\Chatbot\Manager as ChatbotManager;
use WeDevs\Dokan\Chatbot\Assets;
use WeDevs\Dokan\Chatbot\Admin\Settings;

/**
 * Main Dokan AI Chatbot Class
 */
final class DokanAIChatbot
{
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = DOKAN_CHATBOT_VERSION;

    /**
     * Plugin instance
     *
     * @var DokanAIChatbot
     */
    private static $instance = null;

    /**
     * Plugin components
     *
     * @var array
     */
    private $components = [];

    /**
     * Get plugin instance
     *
     * @return DokanAIChatbot
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', [$this, 'init'], 0);
        add_action('init', [$this, 'load_textdomain']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Check if Dokan is active
        if (!$this->is_dokan_active()) {
            add_action('admin_notices', [$this, 'dokan_missing_notice']);
            return;
        }

        // Check if Dokan AI is configured
        if (!$this->is_dokan_ai_configured()) {
            add_action('admin_notices', [$this, 'dokan_ai_notice']);
            return;
        }

        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        $this->includes();
        $this->init_components();
        
        // Fire action after plugin is fully loaded
        do_action('dokan_chatbot_loaded', $this);
    }

    /**
     * Include required files
     */
    private function includes()
    {
        // Core classes
        $files = [
            'includes/Chatbot/Manager.php',
            'includes/Chatbot/Assets.php',
            'includes/Chatbot/Admin/Settings.php',
            'includes/Chatbot/REST/ChatbotController.php',
            'includes/Chatbot/Services/ChatbotService.php',
            'includes/Chatbot/Services/ContextBuilder.php',
            'includes/Chatbot/Services/RoleManager.php',
            'includes/Chatbot/Utils/ChatHistory.php',
            'includes/Chatbot/Utils/PromptTemplates.php',
        ];

        foreach ($files as $file) {
            $file_path = DOKAN_CHATBOT_PATH . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("Dokan Chatbot: Missing required file: {$file}");
            }
        }
    }

    /**
     * Initialize components
     */
    private function init_components()
    {
        try {
            // Initialize core components
            $this->components['manager'] = new ChatbotManager();
            $this->components['assets'] = new Assets();
            $this->components['settings'] = new Settings();
            
            // Register hooks for each component
            foreach ($this->components as $component) {
                if (method_exists($component, 'register_hooks')) {
                    $component->register_hooks();
                }
            }
        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error initializing components: " . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>' . 
                     esc_html__('Dokan AI Chatbot: Error initializing components. Please check error logs.', 'dokan-chatbot') . 
                     '</p></div>';
            });
        }
    }

    /**
     * Check if Dokan is active
     */
    private function is_dokan_active()
    {
        return  function_exists('dokan');
    }

    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active()
    {
        return class_exists('WooCommerce');
    }

    /**
     * Check if Dokan AI is configured
     */
    private function is_dokan_ai_configured()
    {
        if (!class_exists('WeDevs\Dokan\Intelligence\Manager')) {
            return false;
        }

        try {
            $container = dokan()->get_container();
            if (!$container) {
                return false;
            }

            $manager = $container->get(Manager::class);
            return $manager && $manager->is_configured();
        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error checking AI configuration: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Load text domain
     */
    public function load_textdomain()
    {
        load_plugin_textdomain('dokan-chatbot', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Check requirements
        if (!$this->check_requirements()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                esc_html__('Dokan AI Chatbot requires Dokan and WooCommerce to be installed and activated.', 'dokan-chatbot'),
                esc_html__('Plugin Activation Error', 'dokan-chatbot'),
                ['back_link' => true]
            );
        }

        // Create database tables
        $this->create_tables();

        // Set default options
        $this->set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Fire activation hook
        do_action('dokan_chatbot_activated');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
        do_action('dokan_chatbot_deactivated');
    }

    /**
     * Plugin uninstall
     */
    public static function uninstall()
    {
        // Remove options
        $options = [
            'dokan_chatbot_enabled',
            'dokan_chatbot_vendor_access',
            'dokan_chatbot_customer_access',
            'dokan_chatbot_conversation_retention_days',
            'dokan_chatbot_max_messages_per_session',
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        // Drop tables (optional - uncomment if you want to remove data)
        // self::drop_tables();

        do_action('dokan_chatbot_uninstalled');
    }

    /**
     * Check plugin requirements
     */
    private function check_requirements()
    {
        return $this->is_dokan_active() && $this->is_woocommerce_active();
    }

    /**
     * Create database tables
     */
    private function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Conversations table
        $conversations_table = $wpdb->prefix . 'dokan_chatbot_conversations';
        $sql_conversations = "CREATE TABLE $conversations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            vendor_id bigint(20) DEFAULT NULL,
            role varchar(20) NOT NULL,
            message text NOT NULL,
            response text NOT NULL,
            context_data longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY vendor_id (vendor_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        // User preferences table
        $preferences_table = $wpdb->prefix . 'dokan_chatbot_preferences';
        $sql_preferences = "CREATE TABLE $preferences_table (
            user_id bigint(20) NOT NULL,
            preferred_role varchar(20) DEFAULT 'customer',
            chat_enabled tinyint(1) DEFAULT 1,
            notifications_enabled tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $result1 = dbDelta($sql_conversations);
        $result2 = dbDelta($sql_preferences);

        // Log any errors
        if (!empty($result1) || !empty($result2)) {
            error_log("Dokan Chatbot: Database table creation results: " . print_r([$result1, $result2], true));
        }
    }

    /**
     * Drop database tables (for uninstall)
     */
    private static function drop_tables()
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'dokan_chatbot_conversations',
            $wpdb->prefix . 'dokan_chatbot_preferences',
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Set default options
     */
    private function set_default_options()
    {
        $default_options = [
            'dokan_chatbot_enabled' => 'yes',
            'dokan_chatbot_vendor_access' => 'yes',
            'dokan_chatbot_customer_access' => 'yes',
            'dokan_chatbot_conversation_retention_days' => 30,
            'dokan_chatbot_max_messages_per_session' => 50,
            'dokan_chatbot_widget_position' => 'bottom-right',
            'dokan_chatbot_welcome_message' => __('Hello! I\'m your AI assistant. How can I help you today?', 'dokan-chatbot'),
        ];

        foreach ($default_options as $option => $value) {
            if (!get_option($option)) {
                add_option($option, $value);
            }
        }
    }

    /**
     * Get component instance
     */
    public function get_component($name)
    {
        return $this->components[$name] ?? null;
    }

    /**
     * Dokan missing notice
     */
    public function dokan_missing_notice()
    {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Dokan AI Chatbot requires Dokan plugin to be installed and activated.', 'dokan-chatbot'); ?></p>
        </div>
        <?php
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice()
    {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Dokan AI Chatbot requires WooCommerce to be installed and activated.', 'dokan-chatbot'); ?></p>
        </div>
        <?php
    }

    /**
     * Dokan AI not configured notice
     */
    public function dokan_ai_notice()
    {
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e('Dokan AI Chatbot requires Dokan AI to be properly configured. Please configure AI settings in Dokan > Settings > AI Assist.', 'dokan-chatbot'); ?></p>
        </div>
        <?php
    }
}

/**
 * Initialize the plugin
 */
function dokan_ai_chatbot()
{
    return DokanAIChatbot::instance();
}

// Start the plugin
dokan_ai_chatbot();
