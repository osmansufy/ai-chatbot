import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ChatbotToggle from './ChatbotToggle';
import ChatbotInterface from './ChatbotInterface';

const ChatbotWidget = () => {
    const [isOpen, setIsOpen] = useState(false);
    const [currentRole, setCurrentRole] = useState('customer');
    const [messages, setMessages] = useState([]);
    const [suggestions, setSuggestions] = useState([]);
    const [isLoading, setIsLoading] = useState(false);
    const [isLoadingHistory, setIsLoadingHistory] = useState(false);
    const [remainingMessages, setRemainingMessages] = useState(50);
    const [error, setError] = useState(null);
    const [historyLoaded, setHistoryLoaded] = useState(false);
    const [hasMoreHistory, setHasMoreHistory] = useState(true);
    const [historyPage, setHistoryPage] = useState(0);
    const [pendingIntent, setPendingIntent] = useState(null);
    
    const widgetRef = useRef(null);

    // Get settings from WordPress
    const settings = window.dokanChatbot?.settings || {};
    const strings = window.dokanChatbot?.strings || {};
    const userRole = window.dokanChatbot?.userRole || 'customer';
    const vendorId = window.dokanChatbot?.vendorId || null;

    useEffect(() => {
        setCurrentRole(userRole);
        loadSuggestions();
    }, [userRole]);

    useEffect(() => {
        if (isOpen && !historyLoaded) {
            loadChatHistory();
        }
    }, [isOpen, historyLoaded]);

    const loadChatHistory = async (page = 0, append = false) => {
        if (isLoadingHistory) return;

        setIsLoadingHistory(true);
        setError(null);

        try {
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
                setHistoryPage(page);
                setHistoryLoaded(true);
            } else {
                throw new Error('Failed to load chat history');
            }
        } catch (error) {
            console.error('Failed to load chat history:', error);
            setError(error.message);
        } finally {
            setIsLoadingHistory(false);
        }
    };

    const formatHistoryMessages = (historyMessages) => {
        return historyMessages.map(msg => ({
            id: msg.id,
            content: msg.message,
            type: 'user',
            timestamp: new Date(msg.created_at),
            role: msg.role,
            vendor_id: msg.vendor_id,
        })).concat(
            historyMessages.map(msg => ({
                id: msg.id + '_response',
                content: msg.response,
                type: 'ai',
                timestamp: new Date(msg.created_at),
                role: msg.role,
                vendor_id: msg.vendor_id,
            }))
        ).sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
    };

    const loadMoreHistory = async () => {
        if (hasMoreHistory && !isLoadingHistory) {
            await loadChatHistory(historyPage + 1, true);
        }
    };

    const loadSuggestions = async () => {
        try {
            const response = await fetch('/wp-json/dokan/v1/chatbot/suggestions', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.dokanChatbot?.nonce || '',
                },
                body: JSON.stringify({ role: currentRole }),
            });

            if (response.ok) {
                const data = await response.json();
                setSuggestions(data.suggestions || []);
            }
        } catch (error) {
            console.error('Failed to load suggestions:', error);
        }
    };

    const sendMessage = async (message, extraParams = {}) => {
        if (!message.trim() || isLoading || remainingMessages <= 0) {
            return;
        }

        // If confirming an intent, don't add a new user message
        if (!extraParams.intent_confirmed) {
            const newMessage = {
                id: Date.now(),
                content: message,
                type: 'user',
                timestamp: new Date(),
            };
            setMessages(prev => [...prev, newMessage]);
        }
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch('/wp-json/dokan/v1/chatbot/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.dokanChatbot?.nonce || '',
                },
                body: JSON.stringify({
                    message: message,
                    role: currentRole,
                    vendor_id: vendorId,
                    ...extraParams,
                }),
            });

            const data = await response.json();

            if (response.ok && data.response) {
                const aiMessage = {
                    id: Date.now() + 1,
                    content: data.response,
                    type: 'ai',
                    timestamp: new Date(),
                };
                setMessages(prev => [...prev, aiMessage]);
                setRemainingMessages(prev => prev - 1);
                setPendingIntent(null);
            } else if (data.requires_followup && data.intent) {
                // Show intent confirmation UI
                setPendingIntent({
                    intent: data.intent,
                    message,
                    context: data.context,
                    query_params: data.query_params,
                });
            } else {
                throw new Error(data.message || strings.error || 'An error occurred');
            }
        } catch (error) {
            setError(error.message);
            const errorMessage = {
                id: Date.now() + 1,
                content: error.message,
                type: 'ai',
                timestamp: new Date(),
                isError: true,
            };
            setMessages(prev => [...prev, errorMessage]);
            setPendingIntent(null);
        } finally {
            setIsLoading(false);
        }
    };

    // Handler for confirming intent
    const handleConfirmIntent = () => {
        if (pendingIntent) {
            sendMessage(pendingIntent.message, {
                intent_confirmed: true,
                ...pendingIntent.intent,
                ...pendingIntent.query_params,
            });
        }
    };

    // Handler for cancelling intent
    const handleCancelIntent = () => {
        setPendingIntent(null);
    };

    const clearChat = async () => {
        try {
            const response = await fetch('/wp-json/dokan/v1/chatbot/clear-history', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.dokanChatbot?.nonce || '',
                },
            });

            if (response.ok) {
                setMessages([]);
                setError(null);
                setHistoryLoaded(false);
                setHasMoreHistory(true);
                setHistoryPage(0);
            } else {
                throw new Error('Failed to clear chat history');
            }
        } catch (error) {
            setError(error.message);
        }
    };

    const switchRole = async (newRole) => {
        setCurrentRole(newRole);
        setMessages([]);
        setError(null);
        setHistoryLoaded(false);
        setHasMoreHistory(true);
        setHistoryPage(0);
        await loadSuggestions();
    };

    const toggleChat = () => {
        setIsOpen(!isOpen);
    };

    return (
        <div className="dokan-chatbot-widget" ref={widgetRef}>
            <ChatbotToggle 
                isOpen={isOpen} 
                onToggle={toggleChat}
                remainingMessages={remainingMessages}
            />
            
            {isOpen && (
                <ChatbotInterface
                    messages={messages}
                    suggestions={suggestions}
                    isLoading={isLoading}
                    isLoadingHistory={isLoadingHistory}
                    currentRole={currentRole}
                    remainingMessages={remainingMessages}
                    error={error}
                    hasMoreHistory={hasMoreHistory}
                    onSendMessage={sendMessage}
                    onClearChat={clearChat}
                    onSwitchRole={switchRole}
                    onLoadMoreHistory={loadMoreHistory}
                    onClose={() => setIsOpen(false)}
                    settings={settings}
                    strings={strings}
                    pendingIntent={pendingIntent}
                    onConfirmIntent={handleConfirmIntent}
                    onCancelIntent={handleCancelIntent}
                />
            )}
        </div>
    );
};

export default ChatbotWidget; 