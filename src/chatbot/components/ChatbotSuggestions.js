import { __ } from '@wordpress/i18n';

const ChatbotSuggestions = ({ suggestions, onSuggestionClick, strings }) => {
    if (!suggestions || suggestions.length === 0) {
        return null;
    }

    return (
        <div className="p-5">
            <div className="text-sm font-medium text-gray-700 mb-3">
                {strings.suggestionsTitle || __('Quick suggestions:', 'dokan-chatbot')}
            </div>
            <div className="flex flex-wrap gap-2">
                {suggestions.map((suggestion, index) => (
                    <button
                        key={index}
                        type="button"
                        className="bg-white border border-gray-300 rounded-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:border-dokan-primary-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-dokan-primary-500 focus:border-transparent"
                        onClick={() => onSuggestionClick(suggestion)}
                    >
                        {suggestion}
                    </button>
                ))}
            </div>
        </div>
    );
};

export default ChatbotSuggestions; 