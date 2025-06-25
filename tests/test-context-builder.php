<?php
/**
 * Test file for enhanced ContextBuilder
 * 
 * This file demonstrates how to use the enhanced ContextBuilder with
 * REST API integration and analytics support.
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include required files
require_once plugin_dir_path( __FILE__ ) . '../includes/Chatbot/Services/ContextBuilder.php';

use WeDevs\Dokan\Chatbot\Services\ContextBuilder;

/**
 * Test ContextBuilder functionality
 */
function test_context_builder() {
    echo "<h2>Dokan Chatbot ContextBuilder Test</h2>\n";
    
    $context_builder = new ContextBuilder();
    
    // Test 1: Basic context building
    echo "<h3>Test 1: Basic Context Building</h3>\n";
    $basic_context = $context_builder->build_context( 1, 'vendor' );
    echo "<pre>Basic Context:\n" . print_r( $basic_context, true ) . "</pre>\n";
    
    // Test 2: Context with analytics
    echo "<h3>Test 2: Context with Analytics</h3>\n";
    $analytics_params = [
        'include_analytics' => true,
        'include_geo_analytics' => true,
        'analytics_start_date' => '7daysAgo',
        'analytics_end_date' => 'today'
    ];
    $analytics_context = $context_builder->build_context( 1, 'vendor', null, $analytics_params );
    echo "<pre>Analytics Context:\n" . print_r( $analytics_context, true ) . "</pre>\n";
    
    // Test 3: Context with sales reports
    echo "<h3>Test 3: Context with Sales Reports</h3>\n";
    $sales_params = [
        'include_sales' => true,
        'sales_from' => '2024-01-01T00:00:00Z',
        'sales_to' => '2024-01-31T23:59:59Z',
        'sales_group_by' => 'week'
    ];
    $sales_context = $context_builder->build_context( 1, 'vendor', null, $sales_params );
    echo "<pre>Sales Context:\n" . print_r( $sales_context, true ) . "</pre>\n";
    
    // Test 4: Context with products and orders
    echo "<h3>Test 4: Context with Products and Orders</h3>\n";
    $products_orders_params = [
        'include_products' => true,
        'include_orders' => true,
        'products_per_page' => 5,
        'orders_per_page' => 5,
        'orders_status' => 'completed'
    ];
    $products_orders_context = $context_builder->build_context( 1, 'vendor', null, $products_orders_params );
    echo "<pre>Products & Orders Context:\n" . print_r( $products_orders_context, true ) . "</pre>\n";
    
    // Test 5: Customer context with store data
    echo "<h3>Test 5: Customer Context with Store Data</h3>\n";
    $customer_params = [
        'include_products' => true,
        'include_reviews' => true,
        'products_category' => 'electronics',
        'reviews_rating' => 4
    ];
    $customer_context = $context_builder->build_context( 2, 'customer', 1, $customer_params );
    echo "<pre>Customer Context:\n" . print_r( $customer_context, true ) . "</pre>\n";
    
    // Test 6: Parameter validation
    echo "<h3>Test 6: Parameter Validation</h3>\n";
    $invalid_params = [
        'include_analytics' => 'invalid',
        'orders_per_page' => 999,
        'reviews_rating' => 10,
        'sales_group_by' => 'invalid'
    ];
    $validated_params = $context_builder->validate_query_params( $invalid_params );
    echo "<pre>Invalid Params:\n" . print_r( $invalid_params, true ) . "</pre>\n";
    echo "<pre>Validated Params:\n" . print_r( $validated_params, true ) . "</pre>\n";
}

/**
 * Test REST API integration
 */
function test_rest_api_integration() {
    echo "<h2>REST API Integration Test</h2>\n";
    
    // Test vendor dashboard endpoint
    echo "<h3>Vendor Dashboard Endpoint</h3>\n";
    $request = new WP_REST_Request( 'GET', '/dokan/v1/vendor-dashboard' );
    $response = rest_do_request( $request );
    echo "<pre>Dashboard Response:\n" . print_r( $response->get_data(), true ) . "</pre>\n";
    
    // Test vendor profile endpoint
    echo "<h3>Vendor Profile Endpoint</h3>\n";
    $request = new WP_REST_Request( 'GET', '/dokan/v1/vendor-dashboard/profile' );
    $response = rest_do_request( $request );
    echo "<pre>Profile Response:\n" . print_r( $response->get_data(), true ) . "</pre>\n";
    
    // Test store products endpoint
    echo "<h3>Store Products Endpoint</h3>\n";
    $request = new WP_REST_Request( 'GET', '/dokan/v1/stores/1/products' );
    $request->set_param( 'per_page', 5 );
    $response = rest_do_request( $request );
    echo "<pre>Products Response:\n" . print_r( $response->get_data(), true ) . "</pre>\n";
}

/**
 * Test analytics integration
 */
function test_analytics_integration() {
    echo "<h2>Analytics Integration Test</h2>\n";
    
    // Check if analytics module is available
    if ( class_exists( '\WeDevs\DokanPro\Modules\VendorAnalytics\Reports' ) ) {
        echo "<p>✅ Vendor Analytics module is available</p>\n";
        
        try {
            $reports = new \WeDevs\DokanPro\Modules\VendorAnalytics\Reports();
            
            // Test general analytics
            echo "<h3>General Analytics</h3>\n";
            $general_analytics = $reports->dokan_get_vendor_analytics(
                '7daysAgo',
                'today',
                'activeUsers,sessions,screenPageViews',
                'date',
                'sessions'
            );
            
            if ( ! is_wp_error( $general_analytics ) && $general_analytics ) {
                echo "<p>✅ General analytics data retrieved successfully</p>\n";
                echo "<pre>Analytics Data:\n" . print_r( $general_analytics, true ) . "</pre>\n";
            } else {
                echo "<p>❌ Failed to retrieve general analytics data</p>\n";
            }
            
        } catch ( Exception $e ) {
            echo "<p>❌ Analytics error: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p>❌ Vendor Analytics module is not available</p>\n";
    }
}

/**
 * Test error handling
 */
function test_error_handling() {
    echo "<h2>Error Handling Test</h2>\n";
    
    $context_builder = new ContextBuilder();
    
    // Test with invalid user ID
    echo "<h3>Test with Invalid User ID</h3>\n";
    $invalid_context = $context_builder->build_context( 999999, 'vendor' );
    echo "<pre>Invalid User Context:\n" . print_r( $invalid_context, true ) . "</pre>\n";
    
    // Test with invalid role
    echo "<h3>Test with Invalid Role</h3>\n";
    $invalid_role_context = $context_builder->build_context( 1, 'invalid_role' );
    echo "<pre>Invalid Role Context:\n" . print_r( $invalid_role_context, true ) . "</pre>\n";
    
    // Test with invalid query parameters
    echo "<h3>Test with Invalid Query Parameters</h3>\n";
    $invalid_params = [
        'orders_per_page' => -1,
        'reviews_rating' => 10,
        'sales_group_by' => 'invalid'
    ];
    $validated = $context_builder->validate_query_params( $invalid_params );
    echo "<pre>Invalid Params:\n" . print_r( $invalid_params, true ) . "</pre>\n";
    echo "<pre>Validated:\n" . print_r( $validated, true ) . "</pre>\n";
}

/**
 * Run all tests
 */
function run_all_tests() {
    echo "<div style='font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto; padding: 20px;'>\n";
    echo "<h1>Dokan Chatbot ContextBuilder - Comprehensive Test Suite</h1>\n";
    
    // Run tests
    test_context_builder();
    test_rest_api_integration();
    test_analytics_integration();
    test_error_handling();
    
    echo "<h2>Test Summary</h2>\n";
    echo "<p>✅ All tests completed. Check the output above for detailed results.</p>\n";
    echo "<p><strong>Note:</strong> Some tests may fail if Dokan Pro or specific modules are not installed/configured.</p>\n";
    echo "</div>\n";
}

// Run tests if accessed directly
if ( isset( $_GET['run_tests'] ) && current_user_can( 'manage_options' ) ) {
    run_all_tests();
} 