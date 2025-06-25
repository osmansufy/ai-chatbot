# Dokan Chatbot - Chat History Feature

## Overview

The Dokan Chatbot now includes a comprehensive chat history system that allows users to view their previous conversations, load historical messages, and maintain context across sessions.

## Features

### 1. Automatic History Loading
- Chat history is automatically loaded when the chatbot is opened
- Messages are displayed in chronological order
- History includes both user messages and AI responses

### 2. Pagination Support
- Load more history with pagination (20 messages per page)
- Smooth loading states with spinners
- "Load More History" button when more messages are available

### 3. Role-Based History
- History is filtered by user role (customer/vendor)
- Role indicators displayed on messages
- Separate history for different roles

### 4. Message Metadata
- Timestamps for all messages
- Date formatting (Today, Yesterday, or specific date)
- Role badges on messages
- Vendor context for customer messages

### 5. History Management
- Clear all chat history with confirmation
- History persists across sessions
- Automatic cleanup of old messages (configurable)

## Frontend Components

### ChatbotWidget.js
The main widget component that manages chat history state.

### ChatbotHistory.js
New component for handling history loading and pagination.

### ChatbotMessage.js
Enhanced to show message metadata including timestamps and role badges.

## Backend API Endpoints

### GET /wp-json/dokan/v1/chatbot/history
Retrieve chat history with pagination.

### POST /wp-json/dokan/v1/chatbot/clear-history
Clear all chat history for the current user.

## Usage Examples

### Loading Chat History
```javascript
const loadChatHistory = async (page = 0, append = false) => {
    const response = await fetch(`/wp-json/dokan/v1/chatbot/history?limit=20&offset=${page * 20}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.dokanChatbot?.nonce || '',
        },
    });

    if (response.ok) {
        const data = await response.json();
        const formattedMessages = formatHistoryMessages(data.messages || []);
        
        if (append) {
            setMessages(prev => [...formattedMessages, ...prev]);
        } else {
            setMessages(formattedMessages);
        }
        
        setHasMoreHistory(data.messages && data.messages.length === 20);
    }
};
```

### Clearing Chat History
```javascript
const clearChat = async () => {
    const response = await fetch('/wp-json/dokan/v1/chatbot/clear-history', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-WP-Nonce': window.dokanChatbot?.nonce || '',
        },
    });

    if (response.ok) {
        setMessages([]);
        setHistoryLoaded(false);
        setHasMoreHistory(true);
        setHistoryPage(0);
    }
};
```

## Testing

Use the provided test file to verify functionality:

```php
// Include the test file in development
require_once plugin_dir_path(__FILE__) . 'tests/test-chat-history.php';
```

## Security Considerations

1. **Authentication**: All endpoints require user authentication
2. **Nonce Verification**: CSRF protection with WordPress nonces
3. **Data Sanitization**: All input is sanitized before database operations
4. **Rate Limiting**: Prevents abuse of history loading
5. **User Isolation**: Users can only access their own history

## Performance Optimizations

1. **Pagination**: Load history in chunks to prevent memory issues
2. **Indexing**: Database indexes on user_id and created_at
3. **Caching**: Consider implementing Redis caching for frequently accessed history
4. **Cleanup**: Automatic cleanup of old messages to maintain performance

## Troubleshooting

### Common Issues

1. **History not loading**: Check user authentication and nonce validity
2. **Empty history**: Verify database table exists and has data
3. **Performance issues**: Check database indexes and consider pagination
4. **Styling issues**: Ensure Tailwind CSS is properly loaded

### Debug Mode

Enable debug mode to see detailed error messages:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements

1. **Search functionality**: Search through chat history
2. **Export history**: Allow users to download their chat history
3. **History analytics**: Track usage patterns and insights
4. **Message reactions**: Allow users to react to messages
5. **History sharing**: Share specific conversations
6. **Advanced filtering**: Filter by date, role, or vendor

---

**Version**: 1.0.0  
**Last Updated**: January 2024  
**Compatibility**: WordPress 5.0+, Dokan 3.0+ 