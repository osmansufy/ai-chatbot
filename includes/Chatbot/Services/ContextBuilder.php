<?php

namespace WeDevs\Dokan\Chatbot\Services;

use Exception;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class ContextBuilder {

    /**
     * Build context for AI response
     *
     * @param int $user_id
     * @param string $role
     * @param int|null $vendor_id
     * @param array $query_params Optional query parameters for context building
     * @return array
     */
    public function build_context( int $user_id, string $role, ?int $vendor_id = null, array $query_params = [] ): array {
        try {
            $context = [
                'user_info' => $this->get_user_info( $user_id ),
                'timestamp' => current_time( 'mysql' ),
                'query_params' => $query_params,
            ];

            if ( 'vendor' === $role ) {
                $context = array_merge( $context, $this->get_vendor_context( $user_id, $query_params ) );
            } else {
                $context = array_merge( $context, $this->get_customer_context( $user_id, $vendor_id, $query_params ) );
            }

            return apply_filters( 'dokan_chatbot_context', $context, $user_id, $role, $vendor_id, $query_params );
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot ContextBuilder Error: " . $e->getMessage() );
            return [
                'error' => $e->getMessage(),
                'user_info' => $this->get_user_info( $user_id ),
                'timestamp' => current_time( 'mysql' ),
            ];
        }
    }

    /**
     * Get user information
     *
     * @param int $user_id
     * @return string
     */
    private function get_user_info( int $user_id ): string {
        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return '';
        }

        $user_info = [
            'Name: ' . $user->display_name,
            'Email: ' . $user->user_email,
            'Registered: ' . date( 'Y-m-d', strtotime( $user->user_registered ) ),
        ];

        return implode( "\n", $user_info );
    }

    /**
     * Get vendor-specific context using REST API
     *
     * @param int $user_id
     * @param array $query_params
     * @return array
     */
    private function get_vendor_context( int $user_id, array $query_params = [] ): array {
        $context = [];

        // Store information from REST API
        $store_info = $this->get_vendor_store_info( $user_id );
        if ( ! empty( $store_info ) ) {
            $context['store_info'] = $this->format_store_info( $store_info );
        }

        // Dashboard statistics from REST API
        $dashboard_stats = $this->get_vendor_dashboard_stats( $user_id );
        if ( ! empty( $dashboard_stats ) ) {
            $context['dashboard_stats'] = $this->format_dashboard_stats( $dashboard_stats );
        }

        // Analytics data if requested
        if ( ! empty( $query_params['include_analytics'] ) ) {
            $analytics = $this->get_vendor_analytics( $user_id, $query_params );
            if ( ! empty( $analytics ) ) {
                $context['analytics'] = $analytics;
            }
        }

        // Sales reports if requested
        if ( ! empty( $query_params['include_sales'] ) ) {
            $sales_data = $this->get_vendor_sales_reports( $user_id, $query_params );
            if ( ! empty( $sales_data ) ) {
                $context['sales_reports'] = $sales_data;
            }
        }

        // Products summary if requested
        if ( ! empty( $query_params['include_products'] ) ) {
            $products_data = $this->get_vendor_products_summary( $user_id );
            if ( ! empty( $products_data ) ) {
                $context['products_summary'] = $products_data;
            }
        }

        // Orders summary if requested
        if ( ! empty( $query_params['include_orders'] ) ) {
            $orders_data = $this->get_vendor_orders_summary( $user_id, $query_params );
            if ( ! empty( $orders_data ) ) {
                $context['orders_summary'] = $orders_data;
            }
        }

        // Recent orders (default)
        $recent_orders = $this->get_vendor_recent_orders( $user_id );
        if ( ! empty( $recent_orders ) ) {
            $context['recent_orders'] = $recent_orders;
        }

        return $context;
    }

    /**
     * Get customer-specific context using REST API
     *
     * @param int $user_id
     * @param int|null $vendor_id
     * @param array $query_params
     * @return array
     */
    private function get_customer_context( int $user_id, ?int $vendor_id = null, array $query_params = [] ): array {
        $context = [];

        // Recent orders from REST API
        $recent_orders = $this->get_customer_recent_orders( $user_id );
        if ( ! empty( $recent_orders ) ) {
            $context['recent_orders'] = $recent_orders;
        }

        // Store information (if vendor_id provided)
        if ( $vendor_id ) {
            $store_info = $this->get_vendor_store_info( $vendor_id );
            if ( ! empty( $store_info ) ) {
                $context['store_info'] = $this->format_store_info( $store_info );
            }

            // Store products if requested
            if ( ! empty( $query_params['include_products'] ) ) {
                $products_data = $this->get_store_products( $vendor_id, $query_params );
                if ( ! empty( $products_data ) ) {
                    $context['store_products'] = $products_data;
                }
            }

            // Store reviews if requested
            if ( ! empty( $query_params['include_reviews'] ) ) {
                $reviews_data = $this->get_store_reviews( $vendor_id, $query_params );
                if ( ! empty( $reviews_data ) ) {
                    $context['store_reviews'] = $reviews_data;
                }
            }
        }

        return $context;
    }

    /**
     * Get vendor store information via REST API
     *
     * @param int $user_id
     * @return array
     */
    private function get_vendor_store_info( int $user_id ): array {
        try {
            $request = new WP_REST_Request( 'GET', '/dokan/v1/vendor-dashboard/profile' );
            $request->set_param( 'user_id', $user_id );
            
            $response = rest_do_request( $request );
            
            if ( $response instanceof WP_REST_Response && $response->get_status() === 200 ) {
                return $response->get_data();
            }
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching vendor store info: " . $e->getMessage() );
        }

        // Fallback to direct function call
        return dokan_get_store_info( $user_id ) ?: [];
    }

    /**
     * Get vendor dashboard statistics via REST API
     *
     * @param int $user_id
     * @return array
     */
    private function get_vendor_dashboard_stats( int $user_id ): array {
        try {
            $request = new WP_REST_Request( 'GET', '/dokan/v1/vendor-dashboard' );
            $request->set_param( 'user_id', $user_id );
            
            $response = rest_do_request( $request );
            
            if ( $response instanceof WP_REST_Response && $response->get_status() === 200 ) {
                return $response->get_data();
            }
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching vendor dashboard stats: " . $e->getMessage() );
        }

        // Fallback to direct function calls
        return [
            'balance'  => dokan_get_seller_balance( $user_id ),
            'orders'   => dokan_count_orders( $user_id ),
            'products' => dokan_count_posts( 'product', $user_id ),
            'sales'    => wc_price( dokan_author_total_sales( $user_id ) ),
            'earnings' => dokan_get_seller_earnings( $user_id ),
            'views'    => dokan_author_pageviews( $user_id ),
        ];
    }

    /**
     * Get vendor analytics data
     *
     * @param int $user_id
     * @param array $query_params
     * @return array
     */
    private function get_vendor_analytics( int $user_id, array $query_params = [] ): array {
        $analytics = [];

        // Check if vendor analytics module is available
        if ( ! class_exists( '\WeDevs\DokanPro\Modules\VendorAnalytics\Reports' ) ) {
            return $analytics;
        }

        try {
            $reports = new \WeDevs\DokanPro\Modules\VendorAnalytics\Reports();
            
            // Get date range from query params or default to last 30 days
            $start_date = $query_params['analytics_start_date'] ?? '30daysAgo';
            $end_date = $query_params['analytics_end_date'] ?? 'today';
            
            // General analytics
            $general_analytics = $reports->dokan_get_vendor_analytics(
                $start_date,
                $end_date,
                'activeUsers,sessions,screenPageViews,bounceRate,newUsers,averageSessionDuration',
                'date',
                'sessions'
            );

            if ( ! is_wp_error( $general_analytics ) && $general_analytics ) {
                $analytics['general'] = $this->format_analytics_data( $general_analytics );
            }

            // Geographic analytics if requested
            if ( ! empty( $query_params['include_geo_analytics'] ) ) {
                $geo_analytics = $reports->dokan_get_vendor_analytics(
                    $start_date,
                    $end_date,
                    'activeUsers,screenPageViews',
                    'city,country',
                    'activeUsers',
                    [],
                    10
                );

                if ( ! is_wp_error( $geo_analytics ) && $geo_analytics ) {
                    $analytics['geographic'] = $this->format_analytics_data( $geo_analytics );
                }
            }

        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching vendor analytics: " . $e->getMessage() );
        }

        return $analytics;
    }

    /**
     * Get vendor sales reports via REST API
     *
     * @param int $user_id
     * @param array $query_params
     * @return array
     */
    private function get_vendor_sales_reports( int $user_id, array $query_params = [] ): array {
        try {
            $request = new WP_REST_Request( 'GET', '/dokan/v1/vendor-dashboard/sales' );
            $request->set_param( 'user_id', $user_id );
            
            // Add query parameters
            if ( ! empty( $query_params['sales_from'] ) ) {
                $request->set_param( 'from', $query_params['sales_from'] );
            }
            if ( ! empty( $query_params['sales_to'] ) ) {
                $request->set_param( 'to', $query_params['sales_to'] );
            }
            if ( ! empty( $query_params['sales_group_by'] ) ) {
                $request->set_param( 'group_by', $query_params['sales_group_by'] );
            }
            
            $response = rest_do_request( $request );
            
            if ( $response instanceof WP_REST_Response && $response->get_status() === 200 ) {
                return $response->get_data();
            }
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching vendor sales reports: " . $e->getMessage() );
        }

        return [];
    }

    /**
     * Get vendor products summary via REST API
     *
     * @param int $user_id
     * @return array
     */
    private function get_vendor_products_summary( int $user_id ): array {
        try {
            $request = new WP_REST_Request( 'GET', '/dokan/v1/vendor-dashboard/products' );
            $request->set_param( 'user_id', $user_id );
            
            $response = rest_do_request( $request );
            
            if ( $response instanceof WP_REST_Response && $response->get_status() === 200 ) {
                return $response->get_data();
            }
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching vendor products summary: " . $e->getMessage() );
        }

        return [];
    }

    /**
     * Get vendor orders summary via REST API
     *
     * @param int $user_id
     * @param array $query_params
     * @return array
     */
    private function get_vendor_orders_summary( int $user_id, array $query_params = [] ): array {
        try {
            $request = new WP_REST_Request( 'GET', '/dokan/v1/vendor-dashboard/orders' );
            $request->set_param( 'user_id', $user_id );
            
            // Add pagination and filtering parameters
            if ( ! empty( $query_params['orders_page'] ) ) {
                $request->set_param( 'page', $query_params['orders_page'] );
            }
            if ( ! empty( $query_params['orders_per_page'] ) ) {
                $request->set_param( 'per_page', $query_params['orders_per_page'] );
            }
            if ( ! empty( $query_params['orders_status'] ) ) {
                $request->set_param( 'status', $query_params['orders_status'] );
            }
            
            $response = rest_do_request( $request );
            
            if ( $response instanceof WP_REST_Response && $response->get_status() === 200 ) {
                return $response->get_data();
            }
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching vendor orders summary: " . $e->getMessage() );
        }

        return [];
    }

    /**
     * Get store products via REST API
     *
     * @param int $vendor_id
     * @param array $query_params
     * @return array
     */
    private function get_store_products( int $vendor_id, array $query_params = [] ): array {
        try {
            $request = new WP_REST_Request( 'GET', "/dokan/v1/stores/{$vendor_id}/products" );
            
            // Add query parameters
            if ( ! empty( $query_params['products_page'] ) ) {
                $request->set_param( 'page', $query_params['products_page'] );
            }
            if ( ! empty( $query_params['products_per_page'] ) ) {
                $request->set_param( 'per_page', $query_params['products_per_page'] );
            }
            if ( ! empty( $query_params['products_category'] ) ) {
                $request->set_param( 'category', $query_params['products_category'] );
            }
            if ( ! empty( $query_params['products_search'] ) ) {
                $request->set_param( 'search', $query_params['products_search'] );
            }
            
            $response = rest_do_request( $request );
            
            if ( $response instanceof WP_REST_Response && $response->get_status() === 200 ) {
                return $response->get_data();
            }
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching store products: " . $e->getMessage() );
        }

        return [];
    }

    /**
     * Get store reviews via REST API
     *
     * @param int $vendor_id
     * @param array $query_params
     * @return array
     */
    private function get_store_reviews( int $vendor_id, array $query_params = [] ): array {
        try {
            $request = new WP_REST_Request( 'GET', "/dokan/v1/stores/{$vendor_id}/reviews" );
            
            // Add query parameters
            if ( ! empty( $query_params['reviews_page'] ) ) {
                $request->set_param( 'page', $query_params['reviews_page'] );
            }
            if ( ! empty( $query_params['reviews_per_page'] ) ) {
                $request->set_param( 'per_page', $query_params['reviews_per_page'] );
            }
            if ( ! empty( $query_params['reviews_rating'] ) ) {
                $request->set_param( 'rating', $query_params['reviews_rating'] );
            }
            
            $response = rest_do_request( $request );
            
            if ( $response instanceof WP_REST_Response && $response->get_status() === 200 ) {
                return $response->get_data();
            }
        } catch ( Exception $e ) {
            error_log( "Dokan Chatbot: Error fetching store reviews: " . $e->getMessage() );
        }

        return [];
    }

    /**
     * Safely convert a value to string
     *
     * @param mixed $value
     * @return string
     */
    private function safe_to_string( $value ): string {
        if ( is_null( $value ) ) {
            return '';
        }
        
        if ( is_string( $value ) ) {
            return $value;
        }
        
        if ( is_numeric( $value ) ) {
            return (string) $value;
        }
        
        if ( is_bool( $value ) ) {
            return $value ? 'true' : 'false';
        }
        
        if ( is_object( $value ) ) {
            // Try to convert object to string
            try {
                if ( method_exists( $value, '__toString' ) ) {
                    return (string) $value;
                }
                
                if ( method_exists( $value, 'getValue' ) ) {
                    return (string) $value->getValue();
                }
                
                // For unknown objects, try to get a meaningful representation
                return 'Object(' . get_class( $value ) . ')';
            } catch ( Exception $e ) {
                return 'Object(' . get_class( $value ) . ')';
            }
        }
        
        if ( is_array( $value ) ) {
            return 'Array(' . count( $value ) . ' items)';
        }
        
        return '';
    }

    /**
     * Format store information
     *
     * @param array $store_info
     * @return string
     */
    private function format_store_info( array $store_info ): string {
        $info_parts = [];

        if ( ! empty( $store_info['store_name'] ) ) {
            $store_name = $this->safe_to_string( $store_info['store_name'] );
            if ( ! empty( $store_name ) ) {
                $info_parts[] = 'Store Name: ' . $store_name;
            }
        }

        if ( ! empty( $store_info['address'] ) ) {
            if ( is_array( $store_info['address'] ) && ! empty( $store_info['address']['street_1'] ) ) {
                $street_1 = $this->safe_to_string( $store_info['address']['street_1'] );
                if ( ! empty( $street_1 ) ) {
                    $info_parts[] = 'Address: ' . $street_1;
                }
            } elseif ( is_string( $store_info['address'] ) ) {
                $address = $this->safe_to_string( $store_info['address'] );
                if ( ! empty( $address ) ) {
                    $info_parts[] = 'Address: ' . $address;
                }
            }
        }

        if ( ! empty( $store_info['phone'] ) ) {
            $phone = $this->safe_to_string( $store_info['phone'] );
            if ( ! empty( $phone ) ) {
                $info_parts[] = 'Phone: ' . $phone;
            }
        }

        if ( ! empty( $store_info['email'] ) ) {
            $email = $this->safe_to_string( $store_info['email'] );
            if ( ! empty( $email ) ) {
                $info_parts[] = 'Email: ' . $email;
            }
        }

        if ( ! empty( $store_info['store_url'] ) ) {
            $store_url = $this->safe_to_string( $store_info['store_url'] );
            if ( ! empty( $store_url ) ) {
                $info_parts[] = 'Store URL: ' . $store_url;
            }
        }

        return implode( "\n", $info_parts );
    }

    /**
     * Format dashboard statistics
     *
     * @param array $stats
     * @return string
     */
    private function format_dashboard_stats( array $stats ): string {
        $stats_parts = ['Dashboard Statistics:'];

        if ( isset( $stats['balance'] ) ) {
            $balance = $this->safe_to_string( $stats['balance'] );
            if ( ! empty( $balance ) ) {
                $stats_parts[] = 'Balance: ' . $balance;
            }
        }

        if ( isset( $stats['orders'] ) ) {
            $orders = $this->safe_to_string( $stats['orders'] );
            if ( ! empty( $orders ) ) {
                $stats_parts[] = 'Total Orders: ' . $orders;
            }
        }

        if ( isset( $stats['products'] ) ) {
            $products = $this->safe_to_string( $stats['products'] );
            if ( ! empty( $products ) ) {
                $stats_parts[] = 'Total Products: ' . $products;
            }
        }

        if ( isset( $stats['sales'] ) ) {
            $sales = $this->safe_to_string( $stats['sales'] );
            if ( ! empty( $sales ) ) {
                $stats_parts[] = 'Total Sales: ' . $sales;
            }
        }

        if ( isset( $stats['earnings'] ) ) {
            $earnings = $this->safe_to_string( $stats['earnings'] );
            if ( ! empty( $earnings ) ) {
                $stats_parts[] = 'Total Earnings: ' . $earnings;
            }
        }

        if ( isset( $stats['views'] ) ) {
            $views = $this->safe_to_string( $stats['views'] );
            if ( ! empty( $views ) ) {
                $stats_parts[] = 'Store Views: ' . $views;
            }
        }

        return implode( "\n", $stats_parts );
    }

    /**
     * Format analytics data
     *
     * @param mixed $analytics_data
     * @return array
     */
    private function format_analytics_data( $analytics_data ): array {
        if ( ! $analytics_data || ! method_exists( $analytics_data, 'getRows' ) ) {
            return [];
        }

        $formatted = [];
        $rows = $analytics_data->getRows();

        if ( ! empty( $rows ) ) {
            foreach ( $rows as $row ) {
                $row_data = [];
                
                // Get dimension values
                $dimensions = $row->getDimensionValues();
                if ( ! empty( $dimensions ) ) {
                    foreach ( $dimensions as $dimension ) {
                        $row_data['dimensions'][] = $dimension->getValue();
                    }
                }
                
                // Get metric values
                $metrics = $row->getMetricValues();
                if ( ! empty( $metrics ) ) {
                    foreach ( $metrics as $metric ) {
                        $row_data['metrics'][] = $metric->getValue();
                    }
                }
                
                $formatted[] = $row_data;
            }
        }

        return $formatted;
    }

    /**
     * Get vendor recent orders
     *
     * @param int $user_id
     * @return string
     */
    private function get_vendor_recent_orders( int $user_id ): string {
        $orders = wc_get_orders( [
            'seller_id' => $user_id,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ] );

        if ( empty( $orders ) ) {
            return 'No recent orders.';
        }

        $order_info = [];
        foreach ( $orders as $order ) {
            $order_info[] = sprintf(
                'Order #%s - %s - %s - %s',
                $order->get_order_number(),
                $order->get_status(),
                $order->get_total(),
                $order->get_date_created()->format( 'Y-m-d' )
            );
        }

        return 'Recent Orders:' . "\n" . implode( "\n", $order_info );
    }

    /**
     * Get customer recent orders
     *
     * @param int $user_id
     * @return string
     */
    private function get_customer_recent_orders( int $user_id ): string {
        $orders = wc_get_orders( [
            'customer_id' => $user_id,
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ] );

        if ( empty( $orders ) ) {
            return 'No recent orders.';
        }

        $order_info = [];
        foreach ( $orders as $order ) {
            $order_info[] = sprintf(
                'Order #%s - %s - %s - %s',
                $order->get_order_number(),
                $order->get_status(),
                $order->get_total(),
                $order->get_date_created()->format( 'Y-m-d' )
            );
        }

        return 'Recent Orders:' . "\n" . implode( "\n", $order_info );
    }

    /**
     * Validate query parameters
     *
     * @param array $query_params
     * @return array
     */
    public function validate_query_params( array $query_params ): array {
        $validated = [];

        // Validate date formats
        if ( ! empty( $query_params['analytics_start_date'] ) ) {
            $validated['analytics_start_date'] = sanitize_text_field( $query_params['analytics_start_date'] );
        }

        if ( ! empty( $query_params['analytics_end_date'] ) ) {
            $validated['analytics_end_date'] = sanitize_text_field( $query_params['analytics_end_date'] );
        }

        if ( ! empty( $query_params['sales_from'] ) ) {
            $validated['sales_from'] = sanitize_text_field( $query_params['sales_from'] );
        }

        if ( ! empty( $query_params['sales_to'] ) ) {
            $validated['sales_to'] = sanitize_text_field( $query_params['sales_to'] );
        }

        // Validate boolean flags
        $boolean_flags = [
            'include_analytics',
            'include_sales',
            'include_products',
            'include_orders',
            'include_reviews',
            'include_geo_analytics',
        ];

        foreach ( $boolean_flags as $flag ) {
            if ( isset( $query_params[ $flag ] ) ) {
                $validated[ $flag ] = (bool) $query_params[ $flag ];
            }
        }

        // Validate numeric parameters
        $numeric_params = [
            'orders_page',
            'orders_per_page',
            'products_page',
            'products_per_page',
            'reviews_page',
            'reviews_per_page',
        ];

        foreach ( $numeric_params as $param ) {
            if ( ! empty( $query_params[ $param ] ) && is_numeric( $query_params[ $param ] ) ) {
                $validated[ $param ] = (int) $query_params[ $param ];
            }
        }

        // Validate string parameters
        $string_params = [
            'sales_group_by',
            'orders_status',
            'products_category',
            'products_search',
            'reviews_rating',
        ];

        foreach ( $string_params as $param ) {
            if ( ! empty( $query_params[ $param ] ) ) {
                $validated[ $param ] = sanitize_text_field( $query_params[ $param ] );
            }
        }

        return $validated;
    }
}
