<?php

namespace WeDevs\Dokan\Chatbot\Services;

use Exception;
use WeDevs\Dokan\Intelligence\Services\EngineFactory;
use WeDevs\Dokan\Chatbot\Utils\ChatHistory;
use WeDevs\Dokan\Chatbot\Utils\PromptTemplates;
use function __;
use function current_time;

class ChatbotService {

    /**
     * Advanced intent detection from user message
     *
     * @param string $message
     * @return array [ 'type' => string, ... ]
     */
    private function detect_intent( string $message ): array {
        $msg = strtolower( $message );
        // Product search
        if ( preg_match( '/search (?:for )?(?:product|products) (.+)/i', $message, $matches ) ) {
            return [ 'type' => 'search_product', 'query' => trim( $matches[1] ) ];
        }
        // Check order
        if ( preg_match( '/order\s+#?(\d+)/i', $message, $matches ) ) {
            return [ 'type' => 'check_order', 'order_id' => intval( $matches[1] ) ];
        }
        // Add more advanced intent detection here as needed
        // e.g. regex for analytics, reviews, etc.
        return [ 'type' => 'unknown' ];
    }

    /**
     * AI-based intent detection from user message, with regex fallback
     *
     * @param string $message
     * @return array [ 'type' => string, ... ]
     */
    private function detect_intent_with_ai( string $message ): array {
        $prompt = <<<EOT
Classify the following user message into one of these intents: search_product, check_order, get_reviews, general_question.
If possible, extract any relevant parameters (e.g., product name, order id).
Respond ONLY in this JSON format:
{"intent": "...", "parameters": {...}}

User message: "{$message}"
EOT;

        $ai_service = EngineFactory::create();
        $response = $ai_service->process($prompt, [
            'chatbot_mode' => false,
            'role' => 'system',
        ]);

        $intent_data = json_decode($response['response'], true);
        if (is_array($intent_data) && isset($intent_data['intent'])) {
            // Normalize to match previous structure
            $type = $intent_data['intent'];
            $params = isset($intent_data['parameters']) && is_array($intent_data['parameters']) ? $intent_data['parameters'] : [];
            $result = array_merge(['type' => $type], $params);
            return $result;
        }

        // Fallback to regex-based detection
        return $this->detect_intent($message);
    }

    /**
     * Process chatbot message
     *
     * @param string $message
     * @param array $context
     * @param array $query_params Optional query parameters for context building
     * @return array
     * @throws Exception
     */
    public function process_message( string $message, array $context = [], array $query_params = [] ): array {
        try {
            // Validate input
            $this->validate_input($message, $context);

            $user_id = $context['user_id'] ?? get_current_user_id();
            $role = $context['role'] ?? 'customer';
            $vendor_id = $context['vendor_id'] ?? null;

            $allowed_intents = ['search_product', 'check_order']; // Expand as needed

            if (empty($query_params['intent_confirmed'])) {
                // Step 1: Detect intent (AI-based, with fallback)
                $intent = $this->detect_intent_with_ai($message);
                // If intent is allowed and not confirmed, return intent to frontend for confirmation/followup
                if (in_array($intent['type'], $allowed_intents)) {
                    return [
                        'intent' => $intent,
                        'message' => __('Intent detected. Please confirm or provide more details.', 'dokan-chatbot'),
                        'requires_followup' => true,
                    ];
                }
            } else {
                // Use the provided intent and parameters directly
                $intent = $query_params;
            }

            // Validate query parameters
            $context_builder = new ContextBuilder();
            $validated_query_params = $context_builder->validate_query_params($query_params);

            // Build context for the AI (for specific intent, you may want to adjust context building)
            $enhanced_context = $context_builder->build_context( $user_id, $role, $vendor_id, $validated_query_params );

            // Get conversation history
            $chat_history = new ChatHistory();
            $recent_messages = $chat_history->get_recent_messages( $user_id, 5 );

            // Build the prompt with context (for specific intent, you may want to adjust prompt)
            $prompt = $this->build_chatbot_prompt( $message, $enhanced_context, $recent_messages, $role );

            // Get AI service from Dokan
            $ai_service = EngineFactory::create();

            // Process with AI
            $response = $ai_service->process( $prompt, [
                'chatbot_mode' => true,
                'role' => $role,
                'context' => $enhanced_context,
                'query_params' => $validated_query_params,
            ] );

            // Validate AI response
            if (empty($response['response'])) {
                throw new Exception(__('AI service returned an empty response.', 'dokan-chatbot'));
            }

            // If AI cannot answer and intent is specific, return intent for frontend to handle
            $ai_response = $response['response'];
            $needs_action = $this->ai_needs_action($ai_response);
            if ($needs_action && in_array($intent['type'], $allowed_intents)) {
                return [
                    'intent' => $intent,
                    'message' => __( 'Further action required for this intent.', 'dokan-chatbot' ),
                    'requires_followup' => true,
                    'context' => $enhanced_context,
                    'timestamp' => current_time( 'mysql' ),
                    'message_id' => $chat_history->get_last_message_id($user_id),
                    'query_params' => $validated_query_params,
                ];
            }

            // Fire action for extensibility
            do_action('dokan_chatbot_message_processed', $user_id, $message, $response['response'], $role, $vendor_id, $validated_query_params);

            return [
                'response' => $response['response'],
                'context' => $enhanced_context,
                'timestamp' => current_time( 'mysql' ),
                'message_id' => $chat_history->get_last_message_id($user_id),
                'query_params' => $validated_query_params,
            ];

        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error processing message: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate input parameters
     *
     * @param string $message
     * @param array $context
     * @throws Exception
     */
    private function validate_input(string $message, array $context): void
    {
        if (empty(trim($message))) {
            throw new Exception(__('Message cannot be empty.', 'dokan-chatbot'));
        }

        if (strlen($message) > 1000) {
            throw new Exception(__('Message is too long. Maximum 1000 characters allowed.', 'dokan-chatbot'));
        }

        $user_id = $context['user_id'] ?? get_current_user_id();
        if (!$user_id || !is_numeric($user_id)) {
            throw new Exception(__('Invalid user ID.', 'dokan-chatbot'));
        }

        $role = $context['role'] ?? 'customer';
        if (!in_array($role, ['vendor', 'customer'])) {
            throw new Exception(__('Invalid role specified.', 'dokan-chatbot'));
        }
    }

    /**
     * Build chatbot prompt with context
     *
     * @param string $message
     * @param array $context
     * @param array $recent_messages
     * @param string $role
     * @return string
     */
    private function build_chatbot_prompt( string $message, array $context, array $recent_messages, string $role ): string {
        $prompt_templates = new PromptTemplates();
        $base_prompt = $prompt_templates->get_role_prompt( $role );

        // Add context information
        $context_prompt = $this->format_context_for_prompt( $context );

        // Add conversation history
        $history_prompt = $this->format_history_for_prompt( $recent_messages );

        // Build final prompt
        $prompt = $base_prompt . "\n\n" . $context_prompt . "\n\n" . $history_prompt . "\n\nUser: " . $message . "\nAI Assistant:";

        return apply_filters( 'dokan_chatbot_prompt', $prompt, $message, $context, $recent_messages, $role );
    }

    /**
     * Format context for prompt
     *
     * @param array $context
     * @return string
     */
    private function format_context_for_prompt( array $context ): string {
        $context_parts = [];

        if ( ! empty( $context['store_info'] ) ) {
            $context_parts[] = "Store Information:\n" . $context['store_info'];
        }

        if ( ! empty( $context['user_info'] ) ) {
            $context_parts[] = "User Information:\n" . $context['user_info'];
        }

        if ( ! empty( $context['dashboard_stats'] ) ) {
            $context_parts[] = "Dashboard Statistics:\n" . $context['dashboard_stats'];
        }

        if ( ! empty( $context['analytics'] ) ) {
            $context_parts[] = "Analytics Data:\n" . $this->format_analytics_for_prompt( $context['analytics'] );
        }

        if ( ! empty( $context['sales_reports'] ) ) {
            $context_parts[] = "Sales Reports:\n" . $this->format_sales_reports_for_prompt( $context['sales_reports'] );
        }

        if ( ! empty( $context['products_summary'] ) ) {
            $context_parts[] = "Products Summary:\n" . $this->format_products_summary_for_prompt( $context['products_summary'] );
        }

        if ( ! empty( $context['orders_summary'] ) ) {
            $context_parts[] = "Orders Summary:\n" . $this->format_orders_summary_for_prompt( $context['orders_summary'] );
        }

        if ( ! empty( $context['store_products'] ) ) {
            $context_parts[] = "Store Products:\n" . $this->format_store_products_for_prompt( $context['store_products'] );
        }

        if ( ! empty( $context['store_reviews'] ) ) {
            $context_parts[] = "Store Reviews:\n" . $this->format_store_reviews_for_prompt( $context['store_reviews'] );
        }

        if ( ! empty( $context['recent_orders'] ) ) {
            $context_parts[] = "Recent Orders:\n" . $context['recent_orders'];
        }

        return implode( "\n\n", $context_parts );
    }

    /**
     * Format analytics data for prompt
     *
     * @param array $analytics
     * @return string
     */
    private function format_analytics_for_prompt( array $analytics ): string {
        $analytics_parts = [];

        if ( ! empty( $analytics['general'] ) ) {
            $analytics_parts[] = "General Analytics:\n" . $this->format_analytics_data( $analytics['general'] );
        }

        if ( ! empty( $analytics['geographic'] ) ) {
            $analytics_parts[] = "Geographic Analytics:\n" . $this->format_analytics_data( $analytics['geographic'] );
        }

        return implode( "\n\n", $analytics_parts );
    }

    /**
     * Format analytics data
     *
     * @param array $data
     * @return string
     */
    private function format_analytics_data( array $data ): string {
        if ( empty( $data ) ) {
            return 'No analytics data available.';
        }

        $formatted = [];
        foreach ( $data as $row ) {
            $dimensions = $row['dimensions'] ?? [];
            $metrics = $row['metrics'] ?? [];
            
            $row_info = [];
            if ( ! empty( $dimensions ) ) {
                $row_info[] = 'Dimensions: ' . implode( ', ', $dimensions );
            }
            if ( ! empty( $metrics ) ) {
                $row_info[] = 'Metrics: ' . implode( ', ', $metrics );
            }
            
            if ( ! empty( $row_info ) ) {
                $formatted[] = implode( ' | ', $row_info );
            }
        }

        return implode( "\n", $formatted );
    }

    /**
     * Format sales reports for prompt
     *
     * @param array $sales_reports
     * @return string
     */
    private function format_sales_reports_for_prompt( array $sales_reports ): string {
        if ( empty( $sales_reports ) ) {
            return 'No sales reports available.';
        }

        $formatted = [];
        foreach ( $sales_reports as $report ) {
            $report_info = [];
            
            if ( ! empty( $report['date'] ) ) {
                $report_info[] = 'Date: ' . $report['date'];
            }
            if ( ! empty( $report['sales'] ) ) {
                $report_info[] = 'Sales: ' . $report['sales'];
            }
            if ( ! empty( $report['orders'] ) ) {
                $report_info[] = 'Orders: ' . $report['orders'];
            }
            
            if ( ! empty( $report_info ) ) {
                $formatted[] = implode( ' | ', $report_info );
            }
        }

        return implode( "\n", $formatted );
    }

    /**
     * Format products summary for prompt
     *
     * @param array $products_summary
     * @return string
     */
    private function format_products_summary_for_prompt( array $products_summary ): string {
        if ( empty( $products_summary ) ) {
            return 'No products summary available.';
        }

        $formatted = [];
        foreach ( $products_summary as $product ) {
            $product_info = [];
            
            if ( ! empty( $product['name'] ) ) {
                $product_info[] = 'Name: ' . $product['name'];
            }
            if ( ! empty( $product['price'] ) ) {
                $product_info[] = 'Price: ' . $product['price'];
            }
            if ( ! empty( $product['stock'] ) ) {
                $product_info[] = 'Stock: ' . $product['stock'];
            }
            
            if ( ! empty( $product_info ) ) {
                $formatted[] = implode( ' | ', $product_info );
            }
        }

        return implode( "\n", $formatted );
    }

    /**
     * Format orders summary for prompt
     *
     * @param array $orders_summary
     * @return string
     */
    private function format_orders_summary_for_prompt( array $orders_summary ): string {
        if ( empty( $orders_summary ) ) {
            return 'No orders summary available.';
        }

        $formatted = [];
        foreach ( $orders_summary as $order ) {
            $order_info = [];
            
            if ( ! empty( $order['id'] ) ) {
                $order_info[] = 'Order ID: ' . $order['id'];
            }
            if ( ! empty( $order['status'] ) ) {
                $order_info[] = 'Status: ' . $order['status'];
            }
            if ( ! empty( $order['total'] ) ) {
                $order_info[] = 'Total: ' . $order['total'];
            }
            if ( ! empty( $order['date'] ) ) {
                $order_info[] = 'Date: ' . $order['date'];
            }
            
            if ( ! empty( $order_info ) ) {
                $formatted[] = implode( ' | ', $order_info );
            }
        }

        return implode( "\n", $formatted );
    }

    /**
     * Format store products for prompt
     *
     * @param array $store_products
     * @return string
     */
    private function format_store_products_for_prompt( array $store_products ): string {
        if ( empty( $store_products ) ) {
            return 'No store products available.';
        }

        $formatted = [];
        foreach ( $store_products as $product ) {
            $product_info = [];
            
            if ( ! empty( $product['name'] ) ) {
                $product_info[] = 'Name: ' . $product['name'];
            }
            if ( ! empty( $product['price'] ) ) {
                $product_info[] = 'Price: ' . $product['price'];
            }
            if ( ! empty( $product['stock'] ) ) {
                $product_info[] = 'Stock: ' . $product['stock'];
            }
            
            if ( ! empty( $product_info ) ) {
                $formatted[] = implode( ' | ', $product_info );
            }
        }

        return implode( "\n", $formatted );
    }

    /**
     * Format store reviews for prompt
     *
     * @param array $store_reviews
     * @return string
     */
    private function format_store_reviews_for_prompt( array $store_reviews ): string {
        if ( empty( $store_reviews ) ) {
            return 'No store reviews available.';
        }

        $formatted = [];
        foreach ( $store_reviews as $review ) {
            $review_info = [];
            
            if ( ! empty( $review['rating'] ) ) {
                $review_info[] = 'Rating: ' . $review['rating'];
            }
            if ( ! empty( $review['comment'] ) ) {
                $review_info[] = 'Comment: ' . substr( $review['comment'], 0, 100 ) . '...';
            }
            if ( ! empty( $review['date'] ) ) {
                $review_info[] = 'Date: ' . $review['date'];
            }
            
            if ( ! empty( $review_info ) ) {
                $formatted[] = implode( ' | ', $review_info );
            }
        }

        return implode( "\n", $formatted );
    }

    /**
     * Format conversation history for prompt
     *
     * @param array $recent_messages
     * @return string
     */
    private function format_history_for_prompt( array $recent_messages ): string {
        if ( empty( $recent_messages ) ) {
            return '';
        }

        $history_parts = [ 'Recent Conversation:' ];

        foreach ( $recent_messages as $msg ) {
            $history_parts[] = "User: " . $msg['message'];
            $history_parts[] = "AI: " . $msg['response'];
        }

        return implode( "\n", $history_parts );
    }

    /**
     * Get chatbot suggestions based on role and context
     *
     * @param string $role
     * @param array $context
     * @param array $query_params
     * @return array
     */
    public function get_suggestions( string $role, array $context = [], array $query_params = [] ): array {
        $suggestions = [];

        if ( 'vendor' === $role ) {
            $suggestions = [
                'How can I improve my store performance?',
                'Show me my recent orders',
                'What are my best-selling products?',
                'How can I optimize my product listings?',
                'Show me customer feedback and reviews',
                'What are my sales analytics?',
                'How can I increase my store visibility?',
            ];

            // Add analytics-specific suggestions if analytics are available
            if ( ! empty( $query_params['include_analytics'] ) ) {
                $suggestions[] = 'Show me my store analytics for the last 30 days';
                $suggestions[] = 'What are my top performing products?';
                $suggestions[] = 'Show me geographic analytics';
            }

            // Add sales-specific suggestions if sales data is requested
            if ( ! empty( $query_params['include_sales'] ) ) {
                $suggestions[] = 'Show me my sales reports';
                $suggestions[] = 'What are my monthly sales trends?';
                $suggestions[] = 'Show me my revenue breakdown';
            }
        } else {
            $suggestions = [
                'What products do you recommend?',
                'How can I track my order?',
                'What are your return policies?',
                'Show me similar products',
                'What are the shipping options?',
                'How can I contact customer support?',
                'What are the payment methods?',
            ];

            // Add store-specific suggestions if vendor_id is provided
            if ( ! empty( $context['vendor_id'] ) ) {
                $suggestions[] = 'Show me this store\'s products';
                $suggestions[] = 'What are the store reviews?';
                $suggestions[] = 'Tell me about this store';
            }
        }

        return apply_filters('dokan_chatbot_suggestions', $suggestions, $role, $context, $query_params);
    }

    /**
     * Validate message
     *
     * @param string $message
     * @return bool
     */
    public function validate_message( string $message ): bool {
        // Check if message is not empty
        if ( empty( trim( $message ) ) ) {
            return false;
        }

        // Check message length
        if ( strlen( $message ) > 1000 ) {
            return false;
        }

        // Check for spam/abuse patterns
        $spam_patterns = [
            '/\b(spam|scam|free.*money|make.*money.*fast)\b/i',
            '/\b(viagra|casino|poker|lottery)\b/i',
            '/\b(click.*here|buy.*now|limited.*time)\b/i',
        ];

        foreach ( $spam_patterns as $pattern ) {
            if ( preg_match( $pattern, $message ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Rate limit check
     *
     * @param int $user_id
     * @return bool
     */
    public function check_rate_limit( int $user_id ): bool {
        $max_messages = get_option( 'dokan_chatbot_max_messages_per_session', 50 );
        $time_window = 3600; // 1 hour

        $chat_history = new ChatHistory();
        $recent_count = $chat_history->get_message_count( $user_id, $time_window );

        return $recent_count < $max_messages;
    }

    /**
     * Get user's remaining messages for current session
     *
     * @param int $user_id
     * @return int
     */
    public function get_remaining_messages( int $user_id ): int {
        $max_messages = get_option( 'dokan_chatbot_max_messages_per_session', 50 );
        $time_window = 3600; // 1 hour

        $chat_history = new ChatHistory();
        $recent_count = $chat_history->get_message_count( $user_id, $time_window );

        return max(0, $max_messages - $recent_count);
    }

    /**
     * Clear user's conversation history
     *
     * @param int $user_id
     * @return bool
     */
    public function clear_conversation_history( int $user_id ): bool {
        try {
            $chat_history = new ChatHistory();
            return $chat_history->clear_user_history( $user_id );
        } catch (Exception $e) {
            error_log("Dokan Chatbot: Error clearing conversation history: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Detect if AI response is insufficient and needs action
     *
     * @param string $ai_response
     * @return bool
     */
    private function ai_needs_action( $ai_response ): bool {
        $needles = [
            'I need more information',
            'I am not sure',
            'I can\'t answer',
            'I do not have enough context',
            'Sorry, I don\'t have that information',
            'I am unable to',
            'I cannot',
            'I don\'t know',
        ];
        foreach ( $needles as $needle ) {
            if ( stripos( $ai_response, $needle ) !== false ) {
                return true;
            }
        }
        return false;
    }
}
