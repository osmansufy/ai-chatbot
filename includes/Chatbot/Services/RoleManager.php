<?php

namespace WeDevs\Dokan\Chatbot\Services;

class RoleManager {

    /**
     * Check if user can use a specific role
     *
     * @param int $user_id
     * @param string $role
     * @return bool
     */
    public function can_use_role( int $user_id, string $role ): bool {
        // Check if user is logged in
        if ( ! $user_id ) {
            return false;
        }

        // Check if chatbot is enabled for this role
        if ( ! $this->is_role_enabled( $role ) ) {
            return false;
        }

        // Check user-specific permissions
        switch ( $role ) {
            case 'vendor':
                return $this->can_use_vendor_role( $user_id );
            case 'customer':
                return $this->can_use_customer_role( $user_id );
            default:
                return false;
        }
    }

    /**
     * Check if vendor role is enabled
     *
     * @param string $role
     * @return bool
     */
    private function is_role_enabled( string $role ): bool {
        switch ( $role ) {
            case 'vendor':
                return 'yes' === get_option( 'dokan_chatbot_vendor_access', 'yes' );
            case 'customer':
                return 'yes' === get_option( 'dokan_chatbot_customer_access', 'yes' );
            default:
                return false;
        }
    }

    /**
     * Check if user can use vendor role
     *
     * @param int $user_id
     * @return bool
     */
    private function can_use_vendor_role( int $user_id ): bool {
        // Check if user is a vendor
        if ( ! dokan_is_user_seller( $user_id ) ) {
            return false;
        }

        // Check if vendor store is active
        $store_info = dokan_get_store_info( $user_id );
        if ( ! $store_info || empty( $store_info['store_name'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use customer role
     *
     * @param int $user_id
     * @return bool
     */
    private function can_use_customer_role( int $user_id ): bool {
        // Any logged-in user can use customer role
        return true;
    }

    /**
     * Get user's default role
     *
     * @param int $user_id
     * @return string
     */
    public function get_default_role( int $user_id ): string {
        // Check user preference
        $preferences = get_user_meta( $user_id, 'dokan_chatbot_preferences', true );
        if ( is_array( $preferences ) && isset( $preferences['preferred_role'] ) ) {
            $preferred_role = $preferences['preferred_role'];
            if ( $this->can_use_role( $user_id, $preferred_role ) ) {
                return $preferred_role;
            }
        }

        // Fallback to user type
        if ( dokan_is_user_seller( $user_id ) ) {
            return 'vendor';
        }

        return 'customer';
    }

    /**
     * Get available roles for user
     *
     * @param int $user_id
     * @return array
     */
    public function get_available_roles( int $user_id ): array {
        $available_roles = [];

        if ( $this->can_use_role( $user_id, 'customer' ) ) {
            $available_roles['customer'] = __( 'Customer', 'dokan-chatbot' );
        }

        if ( $this->can_use_role( $user_id, 'vendor' ) ) {
            $available_roles['vendor'] = __( 'Vendor', 'dokan-chatbot' );
        }

        return $available_roles;
    }

    /**
     * Get role capabilities
     *
     * @param string $role
     * @return array
     */
    public function get_role_capabilities( string $role ): array {
        switch ( $role ) {
            case 'vendor':
                return [
                    'store_analytics' => true,
                    'order_management' => true,
                    'product_optimization' => true,
                    'customer_insights' => true,
                    'sales_reports' => true,
                    'inventory_management' => true,
                    'marketing_suggestions' => true,
                ];
            case 'customer':
                return [
                    'product_recommendations' => true,
                    'order_tracking' => true,
                    'shopping_assistance' => true,
                    'store_information' => true,
                    'return_policies' => true,
                    'shipping_info' => true,
                    'general_support' => true,
                ];
            default:
                return [];
        }
    }

    /**
     * Check if user has specific capability
     *
     * @param int $user_id
     * @param string $role
     * @param string $capability
     * @return bool
     */
    public function has_capability( int $user_id, string $role, string $capability ): bool {
        if ( ! $this->can_use_role( $user_id, $role ) ) {
            return false;
        }

        $capabilities = $this->get_role_capabilities( $role );
        return isset( $capabilities[ $capability ] ) && $capabilities[ $capability ];
    }

    /**
     * Get role display name
     *
     * @param string $role
     * @return string
     */
    public function get_role_display_name( string $role ): string {
        switch ( $role ) {
            case 'vendor':
                return __( 'Vendor', 'dokan-chatbot' );
            case 'customer':
                return __( 'Customer', 'dokan-chatbot' );
            default:
                return __( 'User', 'dokan-chatbot' );
        }
    }

    /**
     * Get role description
     *
     * @param string $role
     * @return string
     */
    public function get_role_description( string $role ): string {
        switch ( $role ) {
            case 'vendor':
                return __( 'Get help with store management, orders, analytics, and business insights.', 'dokan-chatbot' );
            case 'customer':
                return __( 'Get help with shopping, orders, product recommendations, and customer support.', 'dokan-chatbot' );
            default:
                return __( 'Get general assistance and support.', 'dokan-chatbot' );
        }
    }

    /**
     * Update user role preference
     *
     * @param int $user_id
     * @param string $role
     * @return bool
     */
    public function update_role_preference( int $user_id, string $role ): bool {
        if ( ! $this->can_use_role( $user_id, $role ) ) {
            return false;
        }

        $preferences = get_user_meta( $user_id, 'dokan_chatbot_preferences', true );
        if ( ! is_array( $preferences ) ) {
            $preferences = [];
        }

        $preferences['preferred_role'] = $role;
        $preferences['last_role_switch'] = current_time( 'mysql' );

        return update_user_meta( $user_id, 'dokan_chatbot_preferences', $preferences );
    }

    /**
     * Get user role statistics
     *
     * @param int $user_id
     * @return array
     */
    public function get_role_statistics( int $user_id ): array {
        $stats = [
            'vendor' => [
                'usage_count' => 0,
                'last_used' => null,
                'preferred' => false,
            ],
            'customer' => [
                'usage_count' => 0,
                'last_used' => null,
                'preferred' => false,
            ],
        ];

        // Get user preferences
        $preferences = get_user_meta( $user_id, 'dokan_chatbot_preferences', true );
        if ( is_array( $preferences ) ) {
            if ( isset( $preferences['preferred_role'] ) ) {
                $stats[ $preferences['preferred_role'] ]['preferred'] = true;
            }
        }

        // Get usage statistics from chat history
        global $wpdb;
        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';

        $role_stats = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT role, COUNT(*) as usage_count, MAX(created_at) as last_used
                FROM {$table_name}
                WHERE user_id = %d
                GROUP BY role",
                $user_id
            ),
            ARRAY_A
        );

        foreach ( $role_stats as $stat ) {
            $role = $stat['role'];
            if ( isset( $stats[ $role ] ) ) {
                $stats[ $role ]['usage_count'] = (int) $stat['usage_count'];
                $stats[ $role ]['last_used'] = $stat['last_used'];
            }
        }

        return $stats;
    }
}
