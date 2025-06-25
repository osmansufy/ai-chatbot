import { render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ChatbotWidget from './components/ChatbotWidget';
import './styles/tailwind.css';

// Initialize chatbot when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const chatbotContainer = document.getElementById('dokan-chatbot-widget');
    
    if (chatbotContainer) {
        render(
            <ChatbotWidget />,
            chatbotContainer
        );
    }
});

// Export for potential external use
window.DokanChatbot = {
    render: (container) => {
        if (container) {
            render(<ChatbotWidget />, container);
        }
    }
}; 