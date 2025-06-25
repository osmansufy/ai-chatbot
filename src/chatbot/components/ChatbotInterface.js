import { useState, useRef, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ChatbotHeader from './ChatbotHeader';
import ChatbotMessages from './ChatbotMessages';
import ChatbotInput from './ChatbotInput';
import ChatbotSuggestions from './ChatbotSuggestions';
import ChatbotHistory from './ChatbotHistory';

const ChatbotInterface = ({
    messages,
    suggestions,
    isLoading,
    isLoadingHistory,
    currentRole,
    remainingMessages,
    error,
    hasMoreHistory,
    onSendMessage,
    onClearChat,
    onSwitchRole,
    onLoadMoreHistory,
    onClose,
    settings,
    strings,
    pendingIntent,
    onConfirmIntent,
    onCancelIntent,
}) => {
    const [inputValue, setInputValue] = useState('');
    const messagesEndRef = useRef(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSendMessage = () => {
        if (inputValue.trim() && !isLoading && remainingMessages > 0) {
            onSendMessage(inputValue.trim());
            setInputValue('');
        }
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    };

    const handleSuggestionClick = (suggestion) => {
        onSendMessage(suggestion);
    };

    return (
        <div className="absolute bottom-20 right-0 w-96 h-[500px] bg-white rounded-xl shadow-2xl flex flex-col overflow-hidden animate-slide-in md:w-96 md:h-[500px] sm:w-[calc(100vw-2.5rem)] sm:h-[calc(100vh-7.5rem)] sm:bottom-20 sm:right-5">
            <ChatbotHeader
                currentRole={currentRole}
                remainingMessages={remainingMessages}
                onSwitchRole={onSwitchRole}
                onClearChat={onClearChat}
                onClose={onClose}
                settings={settings}
                strings={strings}
            />
            
            <div className="flex-1 overflow-y-auto p-0 bg-gray-50 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent hover:scrollbar-thumb-gray-400">
                <ChatbotHistory
                    isLoadingHistory={isLoadingHistory}
                    hasMoreHistory={hasMoreHistory}
                    onLoadMoreHistory={onLoadMoreHistory}
                    strings={strings}
                />
                
                <ChatbotMessages
                    messages={messages}
                    isLoading={isLoading}
                    error={error}
                    strings={strings}
                />
                
                {suggestions.length > 0 && messages.length === 0 && !isLoadingHistory && (
                    <ChatbotSuggestions
                        suggestions={suggestions}
                        onSuggestionClick={handleSuggestionClick}
                        strings={strings}
                    />
                )}
                
                {pendingIntent && (
                    <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-xl my-2 flex flex-col items-center">
                        <div className="mb-2 text-yellow-800 font-semibold">
                            {__('Intent detected:', 'dokan-chatbot')} <span className="font-mono">{pendingIntent.intent.type}</span>
                        </div>
                        {pendingIntent.intent.query && (
                            <div className="mb-2 text-sm text-gray-700">{__('Query:', 'dokan-chatbot')} <span className="font-mono">{pendingIntent.intent.query}</span></div>
                        )}
                        {pendingIntent.intent.order_id && (
                            <div className="mb-2 text-sm text-gray-700">{__('Order ID:', 'dokan-chatbot')} <span className="font-mono">{pendingIntent.intent.order_id}</span></div>
                        )}
                        <div className="flex gap-2 mt-2">
                            <button className="px-4 py-1 rounded bg-dokan-primary-500 text-white font-semibold hover:bg-dokan-primary-700" onClick={onConfirmIntent} disabled={isLoading}>
                                {__('Confirm', 'dokan-chatbot')}
                            </button>
                            <button className="px-4 py-1 rounded bg-gray-200 text-gray-800 font-semibold hover:bg-gray-300" onClick={onCancelIntent} disabled={isLoading}>
                                {__('Cancel', 'dokan-chatbot')}
                            </button>
                        </div>
                    </div>
                )}
                
                <div ref={messagesEndRef} />
            </div>
            
            <ChatbotInput
                value={inputValue}
                onChange={setInputValue}
                onSend={handleSendMessage}
                onKeyPress={handleKeyPress}
                isLoading={isLoading || !!pendingIntent}
                remainingMessages={remainingMessages}
                strings={strings}
            />
        </div>
    );
};

export default ChatbotInterface; 