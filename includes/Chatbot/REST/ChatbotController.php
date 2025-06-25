<?php

namespace WeDevs\Dokan\Chatbot\REST;

use Exception;
use WeDevs\Dokan\Chatbot\Services\ChatbotService;
use WeDevs\Dokan\Chatbot\Services\RoleManager;
use WeDevs\Dokan\Chatbot\Utils\ChatHistory;
use WeDevs\Dokan\REST\DokanBaseVendorController;
use WP_Error;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

class ChatbotController extends DokanBaseVendorController {
    /**
     * Version
     *
     * @var string
     */
    protected string $version = 'v1';

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'dokan';

    /**
     * Route name
     *
     * @var string
     */
    protected $rest_base = 'chatbot';

    /**
     * Register routes
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/chat',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'handle_chat' ],
                    'args'                => $this->get_chat_args(),
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/history',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_history' ],
                    'args'                => $this->get_history_args(),
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/suggestions',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_suggestions' ],
                    'args'                => $this->get_suggestions_args(),
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/role-switch',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'switch_role' ],
                    'args'                => $this->get_role_switch_args(),
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
            ]
        );

        register_rest_route(
            $this->namespace . '/' . $this->version,
            '/' . $this->rest_base . '/clear-history',
            [
                [
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => [ $this, 'clear_history' ],
                    'permission_callback' => [ $this, 'check_permission' ],
                ],
            ]
        );
    }

    /**
     * Get chat request arguments
     */
    public function get_chat_args(): array {
        return [
            'message' => [
                'type'        => 'string',
                'required'    => true,
                'description' => __( 'Chat message', 'dokan-chatbot' ),
            ],
            'role' => [
                'type'        => 'string',
                'required'    => false,
                'default'     => 'customer',
                'enum'        => [ 'vendor', 'customer' ],
                'description' => __( 'User role for chatbot', 'dokan-chatbot' ),
            ],
            'vendor_id' => [
                'type'        => 'integer',
                'required'    => false,
                'description' => __( 'Vendor ID for customer context', 'dokan-chatbot' ),
            ],
            // Context building query parameters
            'include_analytics' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include analytics data in context', 'dokan-chatbot' ),
            ],
            'include_sales' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include sales reports in context', 'dokan-chatbot' ),
            ],
            'include_products' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include products summary in context', 'dokan-chatbot' ),
            ],
            'include_orders' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include orders summary in context', 'dokan-chatbot' ),
            ],
            'include_reviews' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include store reviews in context', 'dokan-chatbot' ),
            ],
            'include_geo_analytics' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include geographic analytics in context', 'dokan-chatbot' ),
            ],
            'analytics_start_date' => [
                'type'        => 'string',
                'required'    => false,
                'default'     => '30daysAgo',
                'description' => __( 'Analytics start date (e.g., 30daysAgo, 7daysAgo)', 'dokan-chatbot' ),
            ],
            'analytics_end_date' => [
                'type'        => 'string',
                'required'    => false,
                'default'     => 'today',
                'description' => __( 'Analytics end date (e.g., today, yesterday)', 'dokan-chatbot' ),
            ],
            'sales_from' => [
                'type'        => 'string',
                'required'    => false,
                'description' => __( 'Sales report start date (ISO 8601 format)', 'dokan-chatbot' ),
            ],
            'sales_to' => [
                'type'        => 'string',
                'required'    => false,
                'description' => __( 'Sales report end date (ISO 8601 format)', 'dokan-chatbot' ),
            ],
            'sales_group_by' => [
                'type'        => 'string',
                'required'    => false,
                'default'     => 'day',
                'enum'        => [ 'day', 'week', 'month', 'year' ],
                'description' => __( 'Sales report grouping', 'dokan-chatbot' ),
            ],
            'orders_page' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 1,
                'minimum'     => 1,
                'description' => __( 'Orders page number', 'dokan-chatbot' ),
            ],
            'orders_per_page' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 10,
                'minimum'     => 1,
                'maximum'     => 100,
                'description' => __( 'Orders per page', 'dokan-chatbot' ),
            ],
            'orders_status' => [
                'type'        => 'string',
                'required'    => false,
                'description' => __( 'Filter orders by status', 'dokan-chatbot' ),
            ],
            'products_page' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 1,
                'minimum'     => 1,
                'description' => __( 'Products page number', 'dokan-chatbot' ),
            ],
            'products_per_page' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 10,
                'minimum'     => 1,
                'maximum'     => 100,
                'description' => __( 'Products per page', 'dokan-chatbot' ),
            ],
            'products_category' => [
                'type'        => 'string',
                'required'    => false,
                'description' => __( 'Filter products by category', 'dokan-chatbot' ),
            ],
            'products_search' => [
                'type'        => 'string',
                'required'    => false,
                'description' => __( 'Search products by name', 'dokan-chatbot' ),
            ],
            'reviews_page' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 1,
                'minimum'     => 1,
                'description' => __( 'Reviews page number', 'dokan-chatbot' ),
            ],
            'reviews_per_page' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 10,
                'minimum'     => 1,
                'maximum'     => 100,
                'description' => __( 'Reviews per page', 'dokan-chatbot' ),
            ],
            'reviews_rating' => [
                'type'        => 'integer',
                'required'    => false,
                'minimum'     => 1,
                'maximum'     => 5,
                'description' => __( 'Filter reviews by rating', 'dokan-chatbot' ),
            ],
            'intent_confirmed' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Intent confirmed', 'dokan-chatbot' ),
            ],
            'type' => [
                'type'        => 'string',
                'required'    => false,
                'description' => __( 'Intent type', 'dokan-chatbot' ),
            ],
        ];
    }

    /**
     * Get history request arguments
     */
    public function get_history_args(): array {
        return [
            'limit' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 20,
                'minimum'     => 1,
                'maximum'     => 100,
                'description' => __( 'Number of messages to retrieve', 'dokan-chatbot' ),
            ],
            'offset' => [
                'type'        => 'integer',
                'required'    => false,
                'default'     => 0,
                'minimum'     => 0,
                'description' => __( 'Offset for pagination', 'dokan-chatbot' ),
            ],
        ];
    }

    /**
     * Get suggestions request arguments
     */
    public function get_suggestions_args(): array {
        return [
            'role' => [
                'type'        => 'string',
                'required'    => false,
                'default'     => 'customer',
                'enum'        => [ 'vendor', 'customer' ],
                'description' => __( 'User role for suggestions', 'dokan-chatbot' ),
            ],
            'vendor_id' => [
                'type'        => 'integer',
                'required'    => false,
                'description' => __( 'Vendor ID for customer context', 'dokan-chatbot' ),
            ],
            // Context building query parameters for suggestions
            'include_analytics' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include analytics data in context', 'dokan-chatbot' ),
            ],
            'include_sales' => [
                'type'        => 'boolean',
                'required'    => false,
                'default'     => false,
                'description' => __( 'Include sales reports in context', 'dokan-chatbot' ),
            ],
        ];
    }

    /**
     * Get role switch request arguments
     */
    public function get_role_switch_args(): array {
        return [
            'role' => [
                'type'        => 'string',
                'required'    => true,
                'enum'        => [ 'vendor', 'customer' ],
                'description' => __( 'New role to switch to', 'dokan-chatbot' ),
            ],
        ];
    }

    /**
     * Handle chat request
     */
    public function handle_chat( $request ) {
        $user_id = get_current_user_id();
        $message = sanitize_textarea_field( $request->get_param( 'message' ) );
        $role = $request->get_param( 'role' );
        $vendor_id = $request->get_param( 'vendor_id' );

        // Validate message
        $chatbot_service = new ChatbotService();
        if ( ! $chatbot_service->validate_message( $message ) ) {
            return new WP_Error(
                'invalid_message',
                __( 'Invalid message. Please check your input.', 'dokan-chatbot' ),
                [ 'status' => 400 ]
            );
        }

        // Check rate limit
        if ( ! $chatbot_service->check_rate_limit( $user_id ) ) {
            return new WP_Error(
                'rate_limit_exceeded',
                __( 'Rate limit exceeded. Please wait before sending another message.', 'dokan-chatbot' ),
                [ 'status' => 429 ]
            );
        }

        // Build context
        $context = [
            'user_id' => $user_id,
            'role' => $role,
            'vendor_id' => $vendor_id,
        ];

        // Extract query parameters for context building
        $query_params = $request->get_params();

        // If intent_confirmed and type is present, handle specific intent action directly
        if ( ! empty( $query_params['intent_confirmed'] ) && ! empty( $query_params['type'] ) ) {
            $intent_type = $query_params['type'];
            switch ( $intent_type ) {
                case 'search_product':
                    // Pass all extra params to search_products
                    $search_query = $query_params['query'] ?? "";
                    $product_type = $query_params['product_type'] ?? null;
                    $products_category = $query_params['products_category'] ?? null;
                    $products_page = $query_params['products_page'] ?? 1;
                    $products_per_page = $query_params['products_per_page'] ?? 10;
                    $result = $this->search_products_advanced( $search_query, $vendor_id, $product_type, $products_category, $products_page, $products_per_page );
                    return rest_ensure_response([
                        'response' => $result,
                        'context' => [],
                        'timestamp' => current_time( 'mysql' ),
                        'message_id' => null,
                        'query_params' => $query_params,
                    ]);
                // Add more intent types as needed
                default:
                    // Fallback to normal processing
                    break;
            }
        }

        try {
            $response = $chatbot_service->process_message( $message, $context, $query_params );
            $ai_response = $response['response'] ?? '';

            // Fallback: If AI response is insufficient, trigger action
            if ( $this->ai_needs_action( $ai_response ) ) {
                // Try to use all available intent parameters for fallback
                $intent = $this->detect_intent( $message );
                $intent = array_merge($intent, $query_params); // merge any extra params
                switch ( $intent['type'] ) {
                    case 'search_product':
                        $action_response = $this->search_products_advanced(
                            $intent['query'] ?? $message,
                            $vendor_id,
                            $intent['product_type'] ?? null,
                            $intent['products_category'] ?? null,
                            $intent['products_page'] ?? 1,
                            $intent['products_per_page'] ?? 10
                        );
                        break;
                    case 'check_order':
                        $action_response = $this->get_order_details( $intent['order_id'], $vendor_id );
                        break;
                    default:
                        $action_response = __( 'Sorry, I could not find more information.', 'dokan-chatbot' );
                }
                return rest_ensure_response( [
                    'response' => $action_response,
                    'context' => $response['context'] ?? [],
                    'timestamp' => current_time( 'mysql' ),
                    'message_id' => $response['message_id'] ?? null,
                    'query_params' => $response['query_params'] ?? [],
                ] );
            }

            return rest_ensure_response( $response );
        } catch ( Exception $e ) {
            return new WP_Error(
                'chatbot_error',
                $e->getMessage(),
                [ 'status' => 500 ]
            );
        }
    }

    /**
     * Detect if AI response is insufficient and needs action
     */
    private function ai_needs_action( $ai_response ) {
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

    /**
     * Detect intent from message
     * Returns array: [ 'type' => 'search_product'|'check_order'|..., 'query' => ..., 'order_id' => ... ]
     */
    private function detect_intent( $message ) {
        $msg = strtolower( $message );
        // Product search
        if ( preg_match( '/search (?:for )?(?:product|products) (.+)/i', $message, $matches ) ) {
            return [ 'type' => 'search_product', 'query' => trim( $matches[1] ) ];
        }
        // Check order
        if ( preg_match( '/order\s+#?(\d+)/i', $message, $matches ) ) {
            return [ 'type' => 'check_order', 'order_id' => intval( $matches[1] ) ];
        }
        return [ 'type' => 'unknown' ];
    }

    /**
     * Search products by query (WooCommerce)
     */
    private function search_products( $query, $vendor_id = null ) {
        if ( ! class_exists( 'WC_Product_Query' ) ) {
            return __( 'Product search is not available.', 'dokan-chatbot' );
        }
        $args = [
            'limit' => 5,
            'status' => 'publish',
            's' => $query,
        ];
        if ( $vendor_id ) {
            $args['author'] = $vendor_id;
        }
        $product_query = new \WC_Product_Query( $args );
        $products = $product_query->get_products();
        if ( empty( $products ) ) {
            return __( 'No products found matching your query.', 'dokan-chatbot' );
        }
        $result = __( 'Here are some products I found:', 'dokan-chatbot' ) . "\n";
        foreach ( $products as $product ) {
            $result .= sprintf( "%s (ID: %d) - %s\n", $product->get_name(), $product->get_id(), $product->get_price_html() );
        }
        return $result;
    }

    /**
     * Get order details by order ID (WooCommerce)
     */
    private function get_order_details( $order_id, $vendor_id = null ) {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return __( 'Order lookup is not available.', 'dokan-chatbot' );
        }
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return __( 'Order not found.', 'dokan-chatbot' );
        }
        // If vendor_id is set, check if this order belongs to the vendor
        if ( $vendor_id ) {
            $found = false;
            foreach ( $order->get_items() as $item ) {
                $product = $item->get_product();
                if ( $product && method_exists( $product, 'get_author_id' ) && $product->get_author_id() == $vendor_id ) {
                    $found = true;
                    break;
                }
            }
            if ( ! $found ) {
                return __( 'Order does not belong to this vendor.', 'dokan-chatbot' );
            }
        }
        $result = sprintf( __( 'Order #%d details:', 'dokan-chatbot' ), $order_id ) . "\n";
        $result .= sprintf( __( 'Status: %s', 'dokan-chatbot' ), $order->get_status() ) . "\n";
        $result .= __( 'Items:', 'dokan-chatbot' ) . "\n";
        foreach ( $order->get_items() as $item ) {
            $result .= sprintf( "- %s x%d\n", $item->get_name(), $item->get_quantity() );
        }
        $result .= sprintf( __( 'Total: %s', 'dokan-chatbot' ), $order->get_formatted_order_total() );
        return $result;
    }

    /**
     * Extract query parameters for context building
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    private function extract_query_params( $request ): array {
        $query_params = [];

        // Boolean parameters
        $boolean_params = [
            'include_analytics',
            'include_sales',
            'include_products',
            'include_orders',
            'include_reviews',
            'include_geo_analytics',
        ];

        foreach ( $boolean_params as $param ) {
            $value = $request->get_param( $param );
            if ( $value !== null ) {
                $query_params[ $param ] = (bool) $value;
            }
        }

        // String parameters
        $string_params = [
            'analytics_start_date',
            'analytics_end_date',
            'sales_from',
            'sales_to',
            'sales_group_by',
            'orders_status',
            'products_category',
            'products_search',
            'reviews_rating',
        ];

        foreach ( $string_params as $param ) {
            $value = $request->get_param( $param );
            if ( $value !== null && $value !== '' ) {
                $query_params[ $param ] = sanitize_text_field( $value );
            }
        }

        // Integer parameters
        $integer_params = [
            'orders_page',
            'orders_per_page',
            'products_page',
            'products_per_page',
            'reviews_page',
            'reviews_per_page',
        ];

        foreach ( $integer_params as $param ) {
            $value = $request->get_param( $param );
            if ( $value !== null && is_numeric( $value ) ) {
                $query_params[ $param ] = (int) $value;
            }
        }

        return $query_params;
    }

    /**
     * Get chat history
     */
    public function get_history( $request ) {
        $user_id = get_current_user_id();
        $limit = $request->get_param( 'limit' );
        $offset = $request->get_param( 'offset' );

        $chat_history = new ChatHistory();
        $messages = $chat_history->get_messages( $user_id, $limit, $offset );

        return rest_ensure_response( [
            'messages' => $messages,
            'total' => $chat_history->get_total_messages( $user_id ),
        ] );
    }

    /**
     * Get suggestions
     */
    public function get_suggestions( $request ) {
        $user_id = get_current_user_id();
        $role = $request->get_param( 'role' );
        $vendor_id = $request->get_param( 'vendor_id' );

        // Build context
        $context = [
            'user_id' => $user_id,
            'role' => $role,
            'vendor_id' => $vendor_id,
        ];

        // Extract query parameters for context building
        $query_params = $this->extract_query_params( $request );

        $chatbot_service = new ChatbotService();
        $suggestions = $chatbot_service->get_suggestions( $role, $context, $query_params );

        return rest_ensure_response( [
            'suggestions' => $suggestions,
            'role' => $role,
            'context' => $context,
            'query_params' => $query_params,
        ] );
    }

    /**
     * Switch user role
     */
    public function switch_role( $request ) {
        $user_id = get_current_user_id();
        $new_role = $request->get_param( 'role' );

        // Check role permissions
        $role_manager = new RoleManager();
        if ( ! $role_manager->can_use_role( $user_id, $new_role ) ) {
            return new WP_Error(
                'invalid_role',
                __( 'You do not have permission to use this role.', 'dokan-chatbot' ),
                [ 'status' => 403 ]
            );
        }

        // Update user preference
        $preferences = get_user_meta( $user_id, 'dokan_chatbot_preferences', true );
        if ( ! is_array( $preferences ) ) {
            $preferences = [];
        }

        $preferences['preferred_role'] = $new_role;
        update_user_meta( $user_id, 'dokan_chatbot_preferences', $preferences );

        return rest_ensure_response( [
            'success' => true,
            'role' => $new_role,
            'message' => sprintf( __( 'Switched to %s mode', 'dokan-chatbot' ), $new_role ),
        ] );
    }

    /**
     * Clear chat history
     */
    public function clear_history( $request ) {
        $user_id = get_current_user_id();

        try {
            $chatbot_service = new ChatbotService();
            $success = $chatbot_service->clear_conversation_history( $user_id );

            if ( $success ) {
                return rest_ensure_response( [
                    'success' => true,
                    'message' => __( 'Chat history cleared successfully.', 'dokan-chatbot' ),
                ] );
            } else {
                return new WP_Error(
                    'clear_failed',
                    __( 'Failed to clear chat history.', 'dokan-chatbot' ),
                    [ 'status' => 500 ]
                );
            }
        } catch ( Exception $e ) {
            return new WP_Error(
                'clear_error',
                $e->getMessage(),
                [ 'status' => 500 ]
            );
        }
    }

    /**
     * Check permission
     */
    public function check_permission(): bool {
        return is_user_logged_in();
    }

    /**
     * Advanced product search supporting extra parameters (type, category, pagination)
     */
    private function search_products_advanced( $query, $vendor_id = null, $product_type = null, $products_category = null, $products_page = 1, $products_per_page = 10 ) {
        if ( ! class_exists( 'WC_Product_Query' ) ) {
            return __( 'Product search is not available.', 'dokan-chatbot' );
        }
        $args = [
            'limit' => $products_per_page,
            'status' => 'publish',
            's' => $query,
            'paged' => $products_page,
        ];
        if ( $vendor_id ) {
            $args['author'] = $vendor_id;
        }
        if ( $product_type ) {
            $args['type'] = $product_type;
        }
        if ( $products_category ) {
            $args['category'] = $products_category;
        }
        $product_query = new \WC_Product_Query( $args );
        $products = $product_query->get_products();
        if ( empty( $products ) ) {
            return __( 'No products found matching your query.', 'dokan-chatbot' );
        }
        $result = __( 'Here are some products I found:', 'dokan-chatbot' ) . "\n";
        foreach ( $products as $product ) {
            $result .= sprintf( "%s (ID: %d) - %s\n", $product->get_name(), $product->get_id(), $product->get_price_html() );
        }
        return $result;
    }
}




