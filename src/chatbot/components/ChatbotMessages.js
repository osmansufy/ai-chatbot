import { __ } from '@wordpress/i18n';
import ChatbotMessage from './ChatbotMessage';

const ChatbotMessages = ({ messages, isLoading, isLoadingHistory, error, strings }) => {
    if (messages.length === 0 && !isLoading && !isLoadingHistory) {
        return (
            <div className="p-5 flex flex-col gap-4 justify-center items-center text-center py-10">
                <div className="flex flex-col items-center gap-4 text-gray-600">
                    <div className="w-16 h-16 bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 rounded-full flex items-center justify-center text-white">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H6L4 18V4H20V16Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h4 className="text-lg font-semibold text-gray-800 m-0">
                        {strings.welcomeTitle || __('Welcome to AI Assistant', 'dokan-chatbot')}
                    </h4>
                    <p className="text-sm opacity-80 m-0">
                        {strings.welcomeMessage || __('How can I help you today?', 'dokan-chatbot')}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="p-5 flex flex-col gap-4">
            {messages.map((message) => (
                <ChatbotMessage
                    key={message.id}
                    message={message}
                    strings={strings}
                />
            ))}
            
            {isLoading && (
                <div className="flex items-start gap-3">
                    <div className="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        AI
                    </div>
                    <div className="flex-1 min-w-0">
                        <div className="bg-white rounded-2xl shadow-sm border border-gray-200 p-3 w-fit">
                            <div className="flex gap-1">
                                <span className="w-2 h-2 bg-gray-400 rounded-full animate-typing"></span>
                                <span className="w-2 h-2 bg-gray-400 rounded-full animate-typing" style={{animationDelay: '-0.16s'}}></span>
                                <span className="w-2 h-2 bg-gray-400 rounded-full animate-typing" style={{animationDelay: '-0.32s'}}></span>
                            </div>
                        </div>
                    </div>
                </div>
            )}
            
            {error && (
                <div className="flex items-start gap-3">
                    <div className="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        AI
                    </div>
                    <div className="flex-1 min-w-0">
                        <div className="bg-red-50 border border-red-200 rounded-2xl p-3 w-fit">
                            <p className="text-red-700 text-sm m-0">{error}</p>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default ChatbotMessages; 