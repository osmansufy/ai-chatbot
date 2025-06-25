# Dokan Chatbot ContextBuilder Documentation

## Overview

The enhanced `ContextBuilder` class provides comprehensive context building for the Dokan Chatbot using Dokan REST API endpoints and analytics data. It supports query-based context building with proper validation and analytics integration.

## Features

- **REST API Integration**: Uses Dokan REST API endpoints for data retrieval
- **Analytics Support**: Integrates with Dokan Pro Vendor Analytics module
- **Query-Based Context**: Build context based on specific query parameters
- **Proper Validation**: Comprehensive parameter validation and sanitization
- **Fallback Support**: Graceful fallback to direct function calls if REST API fails
- **Error Handling**: Robust error handling with logging

## API Endpoints Used

### Vendor Dashboard Endpoints
- `GET /dokan/v1/vendor-dashboard` - Dashboard statistics
- `GET /dokan/v1/vendor-dashboard/profile` - Vendor profile information
- `GET /dokan/v1/vendor-dashboard/sales` - Sales reports
- `GET /dokan/v1/vendor-dashboard/products` - Products summary
- `GET /dokan/v1/vendor-dashboard/orders` - Orders summary

### Store Endpoints
- `GET /dokan/v1/stores/{id}/products` - Store products
- `GET /dokan/v1/stores/{id}/reviews` - Store reviews

### Analytics Integration
- Google Analytics integration via Dokan Pro Vendor Analytics module
- General analytics (users, sessions, page views, bounce rate)
- Geographic analytics (city, country data)

## Usage

### Basic Usage

```php
use WeDevs\Dokan\Chatbot\Services\ContextBuilder;

$context_builder = new ContextBuilder();

// Basic context building
$context = $context_builder->build_context(
    $user_id = 123,
    $role = 'vendor',
    $vendor_id = null
);
```

### Advanced Usage with Query Parameters

```php
// Define query parameters for context building
$query_params = [
    'include_analytics' => true,
    'include_sales' => true,
    'include_products' => true,
    'include_orders' => true,
    'include_reviews' => true,
    'include_geo_analytics' => true,
    'analytics_start_date' => '30daysAgo',
    'analytics_end_date' => 'today',
    'sales_from' => '2024-01-01T00:00:00Z',
    'sales_to' => '2024-01-31T23:59:59Z',
    'sales_group_by' => 'day',
    'orders_page' => 1,
    'orders_per_page' => 10,
    'orders_status' => 'completed',
    'products_page' => 1,
    'products_per_page' => 10,
    'products_category' => 'electronics',
    'products_search' => 'laptop',
    'reviews_page' => 1,
    'reviews_per_page' => 10,
    'reviews_rating' => 5,
];

// Build context with query parameters
$context = $context_builder->build_context(
    $user_id = 123,
    $role = 'vendor',
    $vendor_id = null,
    $query_params
);
```

### REST API Usage

```javascript
// Example API call with query parameters
const response = await fetch('/wp-json/dokan/v1/chatbot/chat', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': wpApiSettings.nonce
    },
    body: JSON.stringify({
        message: 'Show me my store analytics',
        role: 'vendor',
        include_analytics: true,
        include_sales: true,
        analytics_start_date: '30daysAgo',
        analytics_end_date: 'today',
        sales_group_by: 'day'
    })
});

const data = await response.json();
```

## Query Parameters Reference

### Boolean Flags

| Parameter | Description | Default |
|-----------|-------------|---------|
| `include_analytics` | Include analytics data in context | `false` |
| `include_sales` | Include sales reports in context | `false` |
| `include_products` | Include products summary in context | `false` |
| `include_orders` | Include orders summary in context | `false` |
| `include_reviews` | Include store reviews in context | `false` |
| `include_geo_analytics` | Include geographic analytics in context | `false` |

### Date Parameters

| Parameter | Description | Default | Format |
|-----------|-------------|---------|--------|
| `analytics_start_date` | Analytics start date | `30daysAgo` | `30daysAgo`, `7daysAgo`, etc. |
| `analytics_end_date` | Analytics end date | `today` | `today`, `yesterday`, etc. |
| `sales_from` | Sales report start date | - | ISO 8601 format |
| `sales_to` | Sales report end date | - | ISO 8601 format |

### Pagination Parameters

| Parameter | Description | Default | Range |
|-----------|-------------|---------|-------|
| `orders_page` | Orders page number | `1` | 1+ |
| `orders_per_page` | Orders per page | `10` | 1-100 |
| `products_page` | Products page number | `1` | 1+ |
| `products_per_page` | Products per page | `10` | 1-100 |
| `reviews_page` | Reviews page number | `1` | 1+ |
| `reviews_per_page` | Reviews per page | `10` | 1-100 |

### Filter Parameters

| Parameter | Description | Values |
|-----------|-------------|--------|
| `sales_group_by` | Sales report grouping | `day`, `week`, `month`, `year` |
| `orders_status` | Filter orders by status | `completed`, `processing`, `pending`, etc. |
| `products_category` | Filter products by category | Category slug |
| `products_search` | Search products by name | Search term |
| `reviews_rating` | Filter reviews by rating | 1-5 |

## Context Structure

The context array contains the following sections:

### Basic Information
- `user_info`: User information (name, email, registration date)
- `timestamp`: Current timestamp
- `query_params`: Validated query parameters

### Vendor Context (when role = 'vendor')
- `store_info`: Store information from REST API
- `dashboard_stats`: Dashboard statistics (balance, orders, products, sales, earnings, views)
- `analytics`: Analytics data (if requested)
  - `general`: General analytics (users, sessions, page views, bounce rate)
  - `geographic`: Geographic analytics (city, country data)
- `sales_reports`: Sales reports data (if requested)
- `products_summary`: Products summary (if requested)
- `orders_summary`: Orders summary (if requested)
- `recent_orders`: Recent orders (default)

### Customer Context (when role = 'customer')
- `recent_orders`: Recent customer orders
- `store_info`: Store information (if vendor_id provided)
- `store_products`: Store products (if requested and vendor_id provided)
- `store_reviews`: Store reviews (if requested and vendor_id provided)

## Error Handling

The ContextBuilder includes comprehensive error handling:

```php
try {
    $context = $context_builder->build_context($user_id, $role, $vendor_id, $query_params);
} catch (Exception $e) {
    error_log("Dokan Chatbot ContextBuilder Error: " . $e->getMessage());
    // Handle error gracefully
}
```

### Error Response Structure

```php
[
    'error' => 'Error message',
    'user_info' => 'Basic user information',
    'timestamp' => 'Current timestamp'
]
```

## Validation

### Query Parameter Validation

```php
$validated_params = $context_builder->validate_query_params($query_params);
```

The validation method:
- Sanitizes string parameters
- Validates numeric parameters
- Converts boolean flags
- Validates date formats
- Ensures parameter ranges

### Input Validation

- User ID validation
- Role validation (vendor/customer)
- Message length validation
- Rate limiting
- Spam detection

## Analytics Integration

### Prerequisites

- Dokan Pro plugin installed
- Vendor Analytics module enabled
- Google Analytics connected

### Analytics Data Structure

```php
[
    'analytics' => [
        'general' => [
            [
                'dimensions' => ['2024-01-01'],
                'metrics' => ['100', '150', '300', '45.5', '50', '120']
            ]
        ],
        'geographic' => [
            [
                'dimensions' => ['New York', 'United States'],
                'metrics' => ['25', '45']
            ]
        ]
    ]
]
```

### Analytics Metrics

- `activeUsers`: Number of active users
- `sessions`: Number of sessions
- `screenPageViews`: Number of page views
- `bounceRate`: Bounce rate percentage
- `newUsers`: Number of new users
- `averageSessionDuration`: Average session duration

## Performance Considerations

### Caching

Consider implementing caching for:
- Dashboard statistics
- Analytics data
- Store information
- Products summary

### Rate Limiting

- REST API calls are rate-limited
- Analytics API calls have quotas
- Implement proper error handling for rate limits

### Optimization

- Use specific query parameters to limit data retrieval
- Implement pagination for large datasets
- Cache frequently accessed data

## Security

### Data Sanitization

- All input parameters are sanitized
- SQL injection prevention
- XSS protection
- CSRF protection via nonces

### Permission Checks

- User authentication required
- Role-based access control
- Vendor-specific data isolation

## Examples

### Vendor Analytics Query

```php
$query_params = [
    'include_analytics' => true,
    'include_geo_analytics' => true,
    'analytics_start_date' => '7daysAgo',
    'analytics_end_date' => 'today'
];

$context = $context_builder->build_context($user_id, 'vendor', null, $query_params);
```

### Sales Report Query

```php
$query_params = [
    'include_sales' => true,
    'sales_from' => '2024-01-01T00:00:00Z',
    'sales_to' => '2024-01-31T23:59:59Z',
    'sales_group_by' => 'week'
];

$context = $context_builder->build_context($user_id, 'vendor', null, $query_params);
```

### Store Products Query

```php
$query_params = [
    'include_products' => true,
    'products_category' => 'electronics',
    'products_search' => 'laptop',
    'products_per_page' => 20
];

$context = $context_builder->build_context($user_id, 'customer', $vendor_id, $query_params);
```

## Troubleshooting

### Common Issues

1. **REST API Errors**: Check if Dokan REST API is properly configured
2. **Analytics Not Available**: Ensure Vendor Analytics module is enabled
3. **Permission Errors**: Verify user has proper permissions
4. **Rate Limit Errors**: Implement proper error handling and retry logic

### Debug Mode

Enable debug logging to troubleshoot issues:

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Error Logs

Check error logs for detailed error messages:
- WordPress debug log
- Server error logs
- Dokan specific logs

## Future Enhancements

- Real-time analytics integration
- Advanced filtering options
- Custom analytics metrics
- Performance optimization
- Enhanced caching mechanisms
- Mobile app integration
- Webhook support for real-time updates 