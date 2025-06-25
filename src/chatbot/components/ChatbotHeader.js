import { __ } from '@wordpress/i18n';

const ChatbotHeader = ({
    currentRole,
    remainingMessages,
    onSwitchRole,
    onClearChat,
    onClose,
    settings,
    strings
}) => {
    // get user role from wp core data store
    const userRole = window.dokanChatbot?.userRole || 'customer';
    const roleOptions = [
        { value: 'customer', label: __('Customer', 'dokan-chatbot') },
       
    ];

    if (userRole === 'vendor') {
        roleOptions.push({ value: 'vendor', label: __('Vendor', 'dokan-chatbot') });
    }

    const handleClearChat = () => {
        if (window.confirm(strings.confirmClearChat || __('Are you sure you want to clear all chat history? This action cannot be undone.', 'dokan-chatbot'))) {
            onClearChat();
        }
    };

    return (
        <div className="bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 text-white px-5 py-4 flex items-center justify-between min-h-[60px]">
            <div className="flex items-center gap-3 flex-1">
                <div className="flex items-center gap-2">
                    <h3 className="text-base font-semibold text-white m-0">
                        {strings.title || __('AI Assistant', 'dokan-chatbot')}
                    </h3>
                    
                    {/* History indicator */}
                    <div className="flex items-center gap-1 text-xs opacity-80">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20ZM12.5 7H11V13L16.25 16.15L17 14.92L12.5 12.25V7Z" fill="currentColor"/>
                        </svg>
                        <span>{strings.history || __('History', 'dokan-chatbot')}</span>
                    </div>
                </div>
                
                <div>
                    <select
                        value={currentRole}
                        onChange={(e) => onSwitchRole(e.target.value)}
                        className="bg-white/20 border border-white/30 rounded-md text-white px-2 py-1 text-xs cursor-pointer transition-all duration-200 hover:bg-white/30 focus:outline-none focus:ring-2 focus:ring-white/30"
                    >
                        {roleOptions.map(option => (
                            <option key={option.value} value={option.value} className="bg-gray-800 text-white">
                                {option.label}
                            </option>
                        ))}
                    </select>
                </div>
            </div>
            
            <div className="flex items-center gap-3">
                <div className="text-xs opacity-90">
                    {remainingMessages > 0 ? (
                        <span className="text-green-300">
                            {__('Messages left:', 'dokan-chatbot')} {remainingMessages}
                        </span>
                    ) : (
                        <span className="text-red-300">
                            {strings.rateLimitExceeded || __('Rate limit exceeded', 'dokan-chatbot')}
                        </span>
                    )}
                </div>
                
                <div className="flex gap-2">
                    <button
                        type="button"
                        className="bg-white/20 border border-white/30 rounded-md text-white w-8 h-8 flex items-center justify-center cursor-pointer transition-all duration-200 hover:bg-white/30 hover:-translate-y-0.5 active:translate-y-0"
                        onClick={handleClearChat}
                        title={strings.clearChat || __('Clear chat history', 'dokan-chatbot')}
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 19C6 20.1 6.9 21 8 21H16C17.1 21 18 20.1 18 19V7H6V19ZM8 9H16V19H8V9ZM15.5 4L14.5 3H9.5L8.5 4H5V6H19V4H15.5Z" fill="currentColor"/>
                        </svg>
                    </button>
                    
                    <button
                        type="button"
                        className="bg-white/20 border border-white/30 rounded-md text-white w-8 h-8 flex items-center justify-center cursor-pointer transition-all duration-200 hover:bg-white/30 hover:-translate-y-0.5 active:translate-y-0"
                        onClick={onClose}
                        title={strings.closeChat || __('Close chat', 'dokan-chatbot')}
                    >
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ChatbotHeader; 