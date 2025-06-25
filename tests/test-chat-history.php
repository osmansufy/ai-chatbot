<?php
/**
 * Test file for Dokan Chatbot Chat History functionality
 * 
 * This file demonstrates how to use the chat history features
 * and test the REST API endpoints.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class DokanChatbotHistoryTest {
    
    public function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    public function init() {
        // Only run tests in development environment
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_action('wp_ajax_test_chat_history', [$this, 'test_chat_history']);
            add_action('wp_ajax_nopriv_test_chat_history', [$this, 'test_chat_history']);
        }
    }
    
    /**
     * Test chat history functionality
     */
    public function test_chat_history() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $user_id = get_current_user_id();
        $chat_history = new \WeDevs\Dokan\Chatbot\Utils\ChatHistory();
        
        echo "<h2>Dokan Chatbot History Test</h2>";
        
        // Test 1: Save some test messages
        echo "<h3>1. Saving Test Messages</h3>";
        $this->save_test_messages($chat_history, $user_id);
        
        // Test 2: Get recent messages
        echo "<h3>2. Getting Recent Messages</h3>";
        $recent_messages = $chat_history->get_recent_messages($user_id, 5);
        echo "<p>Recent messages count: " . count($recent_messages) . "</p>";
        
        // Test 3: Get messages with pagination
        echo "<h3>3. Getting Messages with Pagination</h3>";
        $messages = $chat_history->get_messages($user_id, 10, 0);
        echo "<p>Total messages (first 10): " . count($messages) . "</p>";
        
        // Test 4: Get total message count
        echo "<h3>4. Total Message Count</h3>";
        $total_messages = $chat_history->get_total_messages($user_id);
        echo "<p>Total messages for user: " . $total_messages . "</p>";
        
        // Test 5: Test REST API endpoints
        echo "<h3>5. Testing REST API Endpoints</h3>";
        $this->test_rest_endpoints($user_id);
        
        // Test 6: Test conversation summary
        echo "<h3>6. Conversation Summary</h3>";
        $summary = $chat_history->get_conversation_summary($user_id, 7);
        echo "<p>Last 7 days summary:</p>";
        echo "<ul>";
        echo "<li>Total messages: " . $summary['total_messages'] . "</li>";
        echo "<li>Active days: " . $summary['active_days'] . "</li>";
        echo "<li>First message: " . ($summary['first_message'] ?: 'None') . "</li>";
        echo "<li>Last message: " . ($summary['last_message'] ?: 'None') . "</li>";
        echo "</ul>";
        
        echo "<h3>Test Complete!</h3>";
        echo "<p><a href='" . admin_url() . "'>Back to Admin</a></p>";
        
        wp_die();
    }
    
    /**
     * Save test messages for demonstration
     */
    private function save_test_messages($chat_history, $user_id) {
        $test_messages = [
            [
                'role' => 'customer',
                'message' => 'Hello, I need help with my order',
                'response' => 'I\'d be happy to help you with your order. Could you please provide your order number?',
                'vendor_id' => null,
            ],
            [
                'role' => 'vendor',
                'message' => 'How can I improve my store performance?',
                'response' => 'Here are some tips to improve your store performance: 1. Optimize product images 2. Write compelling product descriptions 3. Offer competitive pricing 4. Provide excellent customer service',
                'vendor_id' => 1,
            ],
            [
                'role' => 'customer',
                'message' => 'What are your shipping options?',
                'response' => 'We offer several shipping options: Standard (3-5 business days), Express (1-2 business days), and Overnight (next day delivery). Shipping costs vary based on your location and the option you choose.',
                'vendor_id' => 1,
            ],
        ];
        
        foreach ($test_messages as $test_msg) {
            $success = $chat_history->save_message(
                $user_id,
                $test_msg['vendor_id'],
                $test_msg['role'],
                $test_msg['message'],
                $test_msg['response'],
                ['test_message' => true]
            );
            
            if ($success) {
                echo "<p>✓ Saved test message: " . substr($test_msg['message'], 0, 50) . "...</p>";
            } else {
                echo "<p>✗ Failed to save test message</p>";
            }
        }
    }
    
    /**
     * Test REST API endpoints
     */
    private function test_rest_endpoints($user_id) {
        $rest_url = rest_url('dokan/v1/chatbot/');
        
        echo "<p>Testing REST endpoints:</p>";
        echo "<ul>";
        
        // Test history endpoint
        $history_url = $rest_url . 'history?limit=5&offset=0';
        echo "<li><strong>History Endpoint:</strong> <a href='{$history_url}' target='_blank'>{$history_url}</a></li>";
        
        // Test suggestions endpoint
        $suggestions_url = $rest_url . 'suggestions?role=customer';
        echo "<li><strong>Suggestions Endpoint:</strong> <a href='{$suggestions_url}' target='_blank'>{$suggestions_url}</a></li>";
        
        echo "</ul>";
        
        echo "<p><strong>Note:</strong> You need to be logged in to access these endpoints. Open them in a new tab while logged in to test.</p>";
    }
}

// Initialize the test class
new DokanChatbotHistoryTest();

/**
 * Add test button to admin bar (development only)
 */
function dokan_chatbot_add_test_button($wp_admin_bar) {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
        $wp_admin_bar->add_node([
            'id' => 'dokan-chatbot-test',
            'title' => 'Test Chat History',
            'href' => admin_url('admin-ajax.php?action=test_chat_history'),
        ]);
    }
}
add_action('admin_bar_menu', 'dokan_chatbot_add_test_button', 100);

/**
 * Frontend test button (development only)
 */
function dokan_chatbot_frontend_test_button() {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
        echo '<div style="position: fixed; top: 100px; right: 20px; z-index: 9999; background: #fff; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">';
        echo '<a href="' . admin_url('admin-ajax.php?action=test_chat_history') . '" target="_blank" style="color: #0073aa; text-decoration: none;">Test Chat History</a>';
        echo '</div>';
    }
}
add_action('wp_footer', 'dokan_chatbot_frontend_test_button'); 