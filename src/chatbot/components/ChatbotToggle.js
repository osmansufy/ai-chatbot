import { __ } from '@wordpress/i18n';

const ChatbotToggle = ({ isOpen, onToggle, remainingMessages }) => {
    return (
        <div 
            className={`w-15 h-15 rounded-full bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 text-white border-none cursor-pointer flex items-center justify-center shadow-lg transition-all duration-300 ease-in-out relative hover:-translate-y-0.5 hover:shadow-xl ${
                isOpen ? 'bg-gradient-to-br from-dokan-secondary-400 to-dokan-secondary-600' : ''
            }`}
            onClick={onToggle}
            title={__('Chat with AI Assistant', 'dokan-chatbot')}
        >
            <div className={`flex items-center justify-center transition-transform duration-300 ease-in-out ${
                isOpen ? 'opacity-0' : 'opacity-100'
            }`}>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H6L4 18V4H20V16Z" fill="currentColor"/>
                    <path d="M7 9H17V11H7V9ZM7 12H14V14H7V12Z" fill="currentColor"/>
                </svg>
            </div>
            
            {remainingMessages > 0 && (
                <div className="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs font-bold border-2 border-white">
                    {remainingMessages}
                </div>
            )}
            
            {isOpen && (
                <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 opacity-100 transition-opacity duration-300 ease-in-out">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                    </svg>
                </div>
            )}
        </div>
    );
};

export default ChatbotToggle; 