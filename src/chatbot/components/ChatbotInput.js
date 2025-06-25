import { __ } from '@wordpress/i18n';

const ChatbotInput = ({
    value,
    onChange,
    onSend,
    onKeyPress,
    isLoading,
    remainingMessages,
    strings
}) => {
    const isDisabled = isLoading || remainingMessages <= 0;

    return (
        <div className="p-4 border-t border-gray-200 bg-white">
            <div className="flex items-end gap-2">
                <textarea
                    className="flex-1 resize-none border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-dokan-primary-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    onKeyPress={onKeyPress}
                    placeholder={strings.inputPlaceholder || __('Type your message...', 'dokan-chatbot')}
                    disabled={isDisabled}
                    rows={1}
                    style={{
                        minHeight: '40px',
                        maxHeight: '120px'
                    }}
                />
                
                <button
                    type="button"
                    className={`w-10 h-10 rounded-lg flex items-center justify-center transition-all duration-200 ${
                        isDisabled 
                            ? 'bg-gray-300 text-gray-500 cursor-not-allowed' 
                            : 'bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 text-white hover:shadow-md hover:-translate-y-0.5 active:translate-y-0'
                    }`}
                    onClick={onSend}
                    disabled={isDisabled}
                    title={__('Send message', 'dokan-chatbot')}
                >
                    {isLoading ? (
                        <div className="animate-spin">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2V6M12 18V22M4.93 4.93L7.76 7.76M16.24 16.24L19.07 19.07M2 12H6M18 12H22M4.93 19.07L7.76 16.24M16.24 7.76L19.07 4.93" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </div>
                    ) : (
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="currentColor"/>
                        </svg>
                    )}
                </button>
            </div>
            
            {remainingMessages <= 0 && (
                <div className="text-xs text-red-600 mt-2 text-center">
                    {strings.rateLimitExceeded || __('Rate limit exceeded. Please try again later.', 'dokan-chatbot')}
                </div>
            )}
        </div>
    );
};

export default ChatbotInput; 