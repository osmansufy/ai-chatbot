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
    strings
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
                
                <div ref={messagesEndRef} />
            </div>
            
            <ChatbotInput
                value={inputValue}
                onChange={setInputValue}
                onSend={handleSendMessage}
                onKeyPress={handleKeyPress}
                isLoading={isLoading}
                remainingMessages={remainingMessages}
                strings={strings}
            />
        </div>
    );
};

export default ChatbotInterface; 