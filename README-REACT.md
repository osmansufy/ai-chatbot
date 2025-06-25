# Dokan AI Chatbot - React Frontend

This document describes the React-based frontend implementation for the Dokan AI Chatbot plugin using wp-scripts and Tailwind CSS.

## 🚀 Features

- **Modern React Components**: Built with React 18 and WordPress wp-elements
- **Tailwind CSS**: Utility-first CSS framework for rapid UI development
- **Responsive Design**: Mobile-first responsive chatbot interface
- **Role-based Chat**: Support for customer and vendor roles
- **Real-time Messaging**: Live chat with AI assistant
- **Suggestion System**: Quick suggestion buttons for common queries
- **Rate Limiting**: Built-in message limits and rate limiting
- **Admin Interface**: React-based admin settings panel

## 📁 Project Structure

```
src/
├── chatbot/
│   ├── components/
│   │   ├── ChatbotWidget.js          # Main widget container
│   │   ├── ChatbotToggle.js          # Floating chat button
│   │   ├── ChatbotInterface.js       # Chat window container
│   │   ├── ChatbotHeader.js          # Header with title and controls
│   │   ├── ChatbotMessages.js        # Messages container
│   │   ├── ChatbotMessage.js         # Individual message component
│   │   ├── ChatbotInput.js           # Message input area
│   │   └── ChatbotSuggestions.js     # Quick suggestion buttons
│   ├── styles/
│   │   └── tailwind.css              # Tailwind CSS imports
│   └── index.js                      # Main entry point
├── admin/
│   ├── components/
│   │   └── AdminSettings.js          # Admin settings interface
│   └── index.js                      # Admin entry point
```

## 🛠️ Development Setup

### Prerequisites

- Node.js 16+ and npm
- WordPress development environment
- Dokan plugin installed and activated

### Installation

1. **Install Dependencies**:
   ```bash
   npm install
   ```

2. **Development Mode**:
   ```bash
   npm start
   ```

3. **Build for Production**:
   ```bash
   npm run build
   ```

## 🎨 Tailwind CSS Configuration

The project uses a custom Tailwind configuration with Dokan-branded colors:

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        'dokan-primary': {
          500: '#667eea',
          600: '#5a67d8',
          700: '#4c51bf',
        },
        'dokan-secondary': {
          400: '#f472b6',
          500: '#ec4899',
          600: '#db2777',
        }
      }
    }
  }
}
```

## 🔧 Component Architecture

### ChatbotWidget
The main container component that manages:
- Chat state (open/closed)
- Message history
- Role switching
- API communication

### ChatbotToggle
Floating action button with:
- Animated state transitions
- Message count badge
- Hover effects

### ChatbotInterface
Main chat window with:
- Responsive design
- Header with controls
- Messages area
- Input section

### ChatbotMessages
Handles message display:
- User vs AI message styling
- Loading states
- Error handling
- Welcome screen

## 🎯 Key Features

### Responsive Design
- Mobile-first approach
- Adaptive chat window sizing
- Touch-friendly interface

### Accessibility
- Keyboard navigation support
- Screen reader friendly
- Focus management
- ARIA labels

### Performance
- Optimized re-renders
- Lazy loading
- Efficient state management

## 🔌 WordPress Integration

### PHP Integration
The React components are integrated into WordPress via:

```php
// In your PHP template
<div id="dokan-chatbot-widget"></div>

// Enqueue scripts
wp_enqueue_script('dokan-chatbot', plugin_dir_url(__FILE__) . 'assets/js/build/chatbot.js');
```

### WordPress Data
Components receive WordPress data via global variables:

```javascript
window.dokanChatbot = {
    settings: { /* settings object */ },
    strings: { /* localized strings */ },
    userRole: 'customer',
    vendorId: 123,
    nonce: 'wp_nonce'
};
```

## 🚀 Build Process

The build process uses wp-scripts which provides:

- **Webpack 5** bundling
- **Babel** transpilation
- **PostCSS** processing
- **Tailwind CSS** compilation
- **Development server** with hot reload
- **Production optimization**

### Build Output
```
assets/js/build/
├── chatbot.js          # Main chatbot bundle
├── chatbot.asset.php   # WordPress asset file
├── admin.js            # Admin interface bundle
└── admin.asset.php     # Admin asset file
```

## 🧪 Testing

```bash
# Run unit tests
npm run test:unit

# Run E2E tests
npm run test:e2e

# Lint code
npm run lint:js
npm run lint:css
```

## 📦 Deployment

1. **Build for Production**:
   ```bash
   npm run build
   ```

2. **Plugin Packaging**:
   ```bash
   npm run plugin-zip
   ```

## 🔄 Migration from Vanilla JS

The React frontend replaces the original vanilla JavaScript implementation:

### Benefits
- **Better State Management**: React hooks for complex state
- **Component Reusability**: Modular, reusable components
- **Developer Experience**: Hot reload, better debugging
- **Performance**: Optimized rendering and updates
- **Maintainability**: Cleaner, more organized code

### Backward Compatibility
The new React components maintain the same API endpoints and data structures as the original implementation.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details. 