import { __ } from '@wordpress/i18n';

const ChatbotHistory = ({ 
    isLoadingHistory, 
    hasMoreHistory, 
    onLoadMoreHistory, 
    strings 
}) => {
    if (isLoadingHistory) {
        return (
            <div className="p-4 flex flex-col items-center justify-center text-center">
                <div className="flex flex-col items-center gap-3 text-gray-600">
                    <div className="w-8 h-8 border-2 border-dokan-primary-500 border-t-transparent rounded-full animate-spin"></div>
                    <p className="text-sm m-0">
                        {strings.loadingHistory || __('Loading chat history...', 'dokan-chatbot')}
                    </p>
                </div>
            </div>
        );
    }

    if (hasMoreHistory) {
        return (
            <div className="p-4 flex flex-col items-center justify-center text-center border-b border-gray-200">
                <button
                    onClick={onLoadMoreHistory}
                    className="flex items-center gap-2 px-4 py-2 text-sm text-dokan-primary-600 hover:text-dokan-primary-700 hover:bg-dokan-primary-50 rounded-lg transition-colors"
                    disabled={isLoadingHistory}
                >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 10L12 15L17 10" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                    </svg>
                    {strings.loadMoreHistory || __('Load More History', 'dokan-chatbot')}
                </button>
            </div>
        );
    }

    return null;
};

export default ChatbotHistory; 