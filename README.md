# Dokan AI Chatbot

An AI-powered chatbot plugin for Dokan marketplace that provides intelligent assistance to vendors and customers using Dokan's existing AI infrastructure.

## Features

### 🤖 AI-Powered Conversations
- Leverages Dokan's AI Intelligence module (OpenAI/Gemini)
- Context-aware responses based on user role and data
- Conversation memory and history tracking
- Real-time message processing with error handling

### 👥 Role-Based Assistance
- **Vendor Mode**: Store management, analytics, order processing, product optimization
- **Customer Mode**: Shopping assistance, order tracking, product recommendations, support
- Dynamic role switching with permission validation

### 🎯 Smart Context Building
- Dynamic context based on user's store data, orders, and preferences
- Real-time information integration
- Personalized responses with conversation history

### 🔧 Easy Configuration
- Seamless integration with Dokan settings
- Role-based access control
- Customizable prompts and messages
- Rate limiting and abuse prevention
- Configurable widget position and welcome messages

### 🛡️ Enhanced Security & Performance
- Input validation and sanitization
- Rate limiting with remaining message counter
- Error handling and user feedback
- Database optimization with proper indexing
- Conversation history management

## Requirements

- WordPress 5.0+
- Dokan Lite/Pro (with AI Intelligence module)
- Dokan AI properly configured with API keys
- PHP 7.4+

## Installation

1. Upload the `dokan-chatbot` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure Dokan AI settings in Dokan > Settings > AI Assist
4. Configure chatbot settings in Dokan > Settings > AI Chatbot

## Configuration

### Basic Settings

1. **Enable AI Chatbot**: Toggle the chatbot on/off
2. **Vendor Access**: Allow vendors to use chatbot assistance
3. **Customer Access**: Allow customers to use chatbot assistance
4. **Conversation Retention**: Set how long to keep chat history
5. **Rate Limiting**: Prevent abuse with message limits
6. **Widget Position**: Choose where the chatbot appears
7. **Welcome Message**: Customize the initial greeting

### Advanced Settings

- **Custom Prompts**: Override default AI prompts for each role
- **Message Limits**: Configure maximum messages per session
- **Error Handling**: Customize error messages and behavior

## Usage

### For Vendors

Vendors can ask the chatbot about:
- Store performance and analytics
- Order management and fulfillment
- Product optimization suggestions
- Customer feedback analysis
- Sales insights and reports
- Inventory management
- Store visibility improvements

### For Customers

Customers can ask the chatbot about:
- Product recommendations
- Order tracking and status
- Store policies and information
- Shopping assistance
- Return and refund information
- Shipping details
- Payment methods
- Customer support contact

## API Endpoints

The plugin provides REST API endpoints for integration:

- `POST /dokan/v1/chatbot/chat` - Send a message
- `GET /dokan/v1/chatbot/history` - Get conversation history
- `GET /dokan/v1/chatbot/suggestions` - Get role-based suggestions
- `POST /dokan/v1/chatbot/role-switch` - Switch user role
- `POST /dokan/v1/chatbot/clear-history` - Clear chat history

## Database Tables

The plugin creates two database tables:

### `wp_dokan_chatbot_conversations`
Stores all chatbot conversations with:
- User and vendor IDs
- Message content and responses
- Role information
- Context data
- Timestamps with proper indexing

### `wp_dokan_chatbot_preferences`
Stores user preferences:
- Preferred role
- Chat settings
- Notification preferences
- Creation and update timestamps

## Hooks and Filters

### Actions
- `dokan_chatbot_loaded` - Fired when plugin is fully loaded
- `dokan_chatbot_activated` - Fired when plugin is activated
- `dokan_chatbot_deactivated` - Fired when plugin is deactivated
- `dokan_chatbot_uninstalled` - Fired when plugin is uninstalled
- `dokan_chatbot_message_processed` - Fired when a message is processed

### Filters
- `dokan_chatbot_prompt` - Modify AI prompts
- `dokan_chatbot_context` - Modify context data
- `dokan_chatbot_suggestions` - Modify suggestions
- `dokan_chatbot_should_load` - Control when chatbot loads

## Customization

### Styling
The chatbot uses CSS classes that can be customized:
- `.dokan-chatbot-widget` - Main widget container
- `.dokan-chatbot-interface` - Chat interface
- `.dokan-chatbot-message` - Individual messages
- `.dokan-chatbot-message-error` - Error messages
- `.dokan-chatbot-button` - Toggle button
- `.dokan-chatbot-remaining` - Message counter

### JavaScript
The chatbot JavaScript can be extended:
```javascript
// Listen for chatbot events
$(document).on('dokan_chatbot_message_sent', function(e, data) {
    console.log('Message sent:', data);
});

// Customize chatbot behavior
window.dokanChatbotCustom = {
    onMessageSend: function(message) {
        // Custom logic before sending
    },
    onResponseReceive: function(response) {
        // Custom logic after receiving response
    }
};
```

## Security

- User authentication required
- Role-based access control
- Rate limiting to prevent abuse
- Input sanitization and validation
- Nonce verification for API requests
- SQL injection prevention
- XSS protection

## Performance

- Efficient database queries with proper indexing
- Conversation caching for faster responses
- Optimized asset loading
- Minimal impact on page load times
- Lazy loading of suggestions
- Responsive design for mobile devices

## Troubleshooting

### Common Issues

1. **Chatbot not appearing**
   - Check if Dokan AI is configured
   - Verify user is logged in
   - Check role-based access settings
   - Ensure WooCommerce is active

2. **AI responses not working**
   - Verify API keys in Dokan AI settings
   - Check API quota and limits
   - Review error logs
   - Test AI service connectivity

3. **Database errors**
   - Ensure proper database permissions
   - Check table creation during activation
   - Verify WordPress database prefix
   - Run database repair if needed

4. **Rate limiting issues**
   - Check message limits in settings
   - Verify user permissions
   - Clear browser cache
   - Check for conflicting plugins

### Debug Mode

Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Error Logging

The plugin logs errors to WordPress error log:
- Check `/wp-content/debug.log` for detailed error messages
- Monitor server error logs for additional information

## Support

For support and documentation:
- [Dokan Documentation](https://dokan.co/docs/)
- [WordPress.org Support Forums](https://wordpress.org/support/)
- [GitHub Issues](https://github.com/weDevs/dokan-chatbot/issues)

## Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.1
- **Enhanced Error Handling**: Improved error handling throughout the plugin
- **Better Input Validation**: Added comprehensive input validation and sanitization
- **Rate Limiting Improvements**: Added remaining message counter and better rate limiting
- **New Features**: Added clear chat history functionality
- **UI Improvements**: Enhanced chatbot interface with error states and better feedback
- **Code Refactoring**: Improved code structure and removed duplicate code
- **Database Optimization**: Added proper indexing and improved table structure
- **Security Enhancements**: Better security measures and validation
- **Performance Improvements**: Optimized asset loading and database queries
- **Mobile Responsiveness**: Improved mobile experience
- **Dark Mode Support**: Added dark mode CSS support
- **Better Documentation**: Enhanced code documentation and comments

### Version 1.0.0
- Initial release
- Role-based AI chatbot
- Integration with Dokan AI
- REST API endpoints
- Admin settings interface
- Frontend widget
- Conversation history
- Rate limiting

## Credits

Developed by [WeDevs](https://wedevs.com/) for the Dokan marketplace ecosystem. 
