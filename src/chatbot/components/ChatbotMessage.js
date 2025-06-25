import { __ } from '@wordpress/i18n';
import parse from 'html-react-parser';

const ChatbotMessage = ({ message, strings }) => {
    const { content, type, timestamp, isError, role, vendor_id } = message;
    
    const formatTime = (date) => {
        return new Date(date).toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatDate = (date) => {
        const messageDate = new Date(date);
        const today = new Date();
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        if (messageDate.toDateString() === today.toDateString()) {
            return __('Today', 'dokan-chatbot');
        } else if (messageDate.toDateString() === yesterday.toDateString()) {
            return __('Yesterday', 'dokan-chatbot');
        } else {
            return messageDate.toLocaleDateString();
        }
    };

    const getRoleLabel = (role) => {
        return role === 'vendor' ? __('Vendor', 'dokan-chatbot') : __('Customer', 'dokan-chatbot');
    };

    if (type === 'user') {
        return (
            <div className="flex items-start gap-3 justify-end">
                <div className="flex-1 min-w-0">
                    <div className="bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 text-white rounded-2xl p-3 ml-auto w-fit max-w-xs">
                        <p className="text-sm m-0 break-words">{content}</p>
                    </div>
                    <div className="text-xs text-gray-500 mt-1 text-right">
                        <div className="flex items-center gap-2 justify-end">
                            {role && (
                                <span className="bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs">
                                    {getRoleLabel(role)}
                                </span>
                            )}
                            <span>{formatTime(timestamp)}</span>
                        </div>
                        {timestamp && (
                            <div className="text-xs text-gray-400 mt-0.5">
                                {formatDate(timestamp)}
                            </div>
                        )}
                    </div>
                </div>
                <div className="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-gray-500 to-gray-600 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                    U
                </div>
            </div>
        );
    }

    return (
        <div className="flex items-start gap-3">
            <div className="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-dokan-primary-500 to-dokan-primary-700 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                AI
            </div>
            <div className="flex-1 min-w-0">
                <div className={`rounded-2xl p-3 w-fit max-w-xs ${
                    isError 
                        ? 'bg-red-50 border border-red-200' 
                        : 'bg-white shadow-sm border border-gray-200'
                }`}>
                    <p className={`text-sm m-0 break-words ${
                        isError ? 'text-red-700' : 'text-gray-800'
                    }`} 
                    >
                        {parse(content)}
                    </p>
                </div>
                <div className="text-xs text-gray-500 mt-1">
                    <div className="flex items-center gap-2">
                        {role && (
                            <span className="bg-gray-200 text-gray-700 px-2 py-0.5 rounded-full text-xs">
                                {getRoleLabel(role)}
                            </span>
                        )}
                        <span>{formatTime(timestamp)}</span>
                    </div>
                    {timestamp && (
                        <div className="text-xs text-gray-400 mt-0.5">
                            {formatDate(timestamp)}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default ChatbotMessage; 