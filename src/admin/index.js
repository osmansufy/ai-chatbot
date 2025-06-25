import { render } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import AdminSettings from './components/AdminSettings';
import '../chatbot/styles/tailwind.css';

// Initialize admin interface when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const adminContainer = document.getElementById('dokan-chatbot-admin');
    
    if (adminContainer) {
        render(
            <AdminSettings />,
            adminContainer
        );
    }
});

// Export for potential external use
window.DokanChatbotAdmin = {
    render: (container) => {
        if (container) {
            render(<AdminSettings />, container);
        }
    }
}; 