<?php
/**
 * Chatbot Widget Template
 *
 * @package DokanChatbot
 */

defined( 'ABSPATH' ) || exit;
?>

<div id="dokan-chatbot-widget" class="dokan-chatbot-widget">
    <!-- Chat Button -->
    <div class="dokan-chatbot-button" id="dokan-chatbot-toggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H6L4 18V4H20V16Z" fill="currentColor"/>
            <path d="M7 9H17V11H7V9ZM7 12H14V14H7V12Z" fill="currentColor"/>
        </svg>
        <span class="dokan-chatbot-button-text"><?php esc_html_e( 'Chat with AI', 'dokan-chatbot' ); ?></span>
    </div>

    <!-- Chat Interface -->
    <div class="dokan-chatbot-interface" id="dokan-chatbot-interface">
        <!-- Header -->
        <div class="dokan-chatbot-header">
            <div class="dokan-chatbot-header-info">
                <h3><?php esc_html_e( 'AI Assistant', 'dokan-chatbot' ); ?></h3>
                <p class="dokan-chatbot-subtitle"><?php esc_html_e( 'How can I help you today?', 'dokan-chatbot' ); ?></p>
            </div>
            <div class="dokan-chatbot-header-actions">
                <!-- Role Selector -->
                <div class="dokan-chatbot-role-selector">
                    <select id="dokan-chatbot-role" class="dokan-chatbot-role-select">
                        <option value="customer"><?php esc_html_e( 'Customer', 'dokan-chatbot' ); ?></option>
                        <option value="vendor"><?php esc_html_e( 'Vendor', 'dokan-chatbot' ); ?></option>
                    </select>
                </div>
                <!-- Clear Chat Button -->
                <button class="dokan-chatbot-clear" id="dokan-chatbot-clear" title="<?php esc_attr_e( 'Clear Chat History', 'dokan-chatbot' ); ?>">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 13H5V11H19V13Z" fill="currentColor"/>
                    </svg>
                </button>
                <!-- Close Button -->
                <button class="dokan-chatbot-close" id="dokan-chatbot-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="dokan-chatbot-messages" id="dokan-chatbot-messages">
            <!-- Welcome Message -->
            <div class="dokan-chatbot-message dokan-chatbot-message-ai">
                <div class="dokan-chatbot-message-content">
                    <p><?php echo esc_html( get_option( 'dokan_chatbot_welcome_message', __( 'Hello! I\'m your AI assistant. I can help you with questions about products, orders, store information, and more. What would you like to know?', 'dokan-chatbot' ) ) ); ?></p>
                </div>
                <div class="dokan-chatbot-message-time">
                    <?php echo esc_html( current_time( 'H:i' ) ); ?>
                </div>
            </div>
        </div>

        <!-- Suggestions -->
        <div class="dokan-chatbot-suggestions" id="dokan-chatbot-suggestions">
            <!-- Suggestions will be loaded dynamically -->
        </div>

        <!-- Input Area -->
        <div class="dokan-chatbot-input-area">
            <div class="dokan-chatbot-input-wrapper">
                <textarea
                    id="dokan-chatbot-input"
                    class="dokan-chatbot-input"
                    placeholder="<?php esc_attr_e( 'Type your message...', 'dokan-chatbot' ); ?>"
                    rows="1"
                ></textarea>
                <button class="dokan-chatbot-send" id="dokan-chatbot-send">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
            <div class="dokan-chatbot-input-footer">
                <small id="dokan-chatbot-remaining" class="dokan-chatbot-remaining">
                    <?php 
                    $max_messages = get_option( 'dokan_chatbot_max_messages_per_session', 50 );
                    printf( esc_html__( '%d messages remaining', 'dokan-chatbot' ), $max_messages ); 
                    ?>
                </small>
                <small><?php esc_html_e( 'AI responses are generated based on your role and available data.', 'dokan-chatbot' ); ?></small>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div class="dokan-chatbot-loading" id="dokan-chatbot-loading" style="display: none;">
            <div class="dokan-chatbot-loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <p><?php esc_html_e( 'AI is thinking...', 'dokan-chatbot' ); ?></p>
        </div>
    </div>
</div>

<!-- Chatbot Styles -->
<style>
.dokan-chatbot-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.dokan-chatbot-button {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #007cba;
    color: white;
    padding: 12px 16px;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 124, 186, 0.3);
    transition: all 0.3s ease;
    font-weight: 500;
}

.dokan-chatbot-button:hover {
    background: #005a87;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 124, 186, 0.4);
}

.dokan-chatbot-interface {
    position: absolute;
    bottom: 70px;
    right: 0;
    width: 380px;
    height: 500px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    display: none;
    flex-direction: column;
    overflow: hidden;
}

.dokan-chatbot-interface.active {
    display: flex;
}

.dokan-chatbot-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    background: #f9fafb;
}

.dokan-chatbot-header-info h3 {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
    color: #111827;
}

.dokan-chatbot-subtitle {
    margin: 0;
    font-size: 12px;
    color: #6b7280;
}

.dokan-chatbot-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.dokan-chatbot-role-select {
    padding: 4px 8px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 12px;
    background: white;
}

.dokan-chatbot-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    color: #6b7280;
    transition: all 0.2s ease;
}

.dokan-chatbot-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.dokan-chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dokan-chatbot-message {
    display: flex;
    flex-direction: column;
    max-width: 80%;
}

.dokan-chatbot-message-user {
    align-self: flex-end;
}

.dokan-chatbot-message-ai {
    align-self: flex-start;
}

.dokan-chatbot-message-content {
    padding: 12px 16px;
    border-radius: 18px;
    font-size: 14px;
    line-height: 1.4;
}

.dokan-chatbot-message-user .dokan-chatbot-message-content {
    background: #007cba;
    color: white;
    border-bottom-right-radius: 4px;
}

.dokan-chatbot-message-ai .dokan-chatbot-message-content {
    background: #f3f4f6;
    color: #374151;
    border-bottom-left-radius: 4px;
}

.dokan-chatbot-message-time {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 4px;
    align-self: flex-end;
}

.dokan-chatbot-suggestions {
    padding: 0 16px 16px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.dokan-chatbot-suggestion {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    padding: 8px 12px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #374151;
}

.dokan-chatbot-suggestion:hover {
    background: #e5e7eb;
    border-color: #d1d5db;
}

.dokan-chatbot-input-area {
    padding: 16px;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.dokan-chatbot-input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 24px;
    padding: 8px 12px;
}

.dokan-chatbot-input {
    flex: 1;
    border: none;
    outline: none;
    resize: none;
    font-size: 14px;
    line-height: 1.4;
    max-height: 100px;
    font-family: inherit;
}

.dokan-chatbot-send {
    background: #007cba;
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dokan-chatbot-send:hover {
    background: #005a87;
}

.dokan-chatbot-send:disabled {
    background: #9ca3af;
    cursor: not-allowed;
}

.dokan-chatbot-input-footer {
    margin-top: 8px;
    text-align: center;
}

.dokan-chatbot-input-footer small {
    color: #6b7280;
    font-size: 11px;
}

.dokan-chatbot-loading {
    position: absolute;
    bottom: 80px;
    left: 16px;
    right: 16px;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.dokan-chatbot-loading-dots {
    display: flex;
    justify-content: center;
    gap: 4px;
    margin-bottom: 8px;
}

.dokan-chatbot-loading-dots span {
    width: 8px;
    height: 8px;
    background: #007cba;
    border-radius: 50%;
    animation: dokan-chatbot-bounce 1.4s infinite ease-in-out;
}

.dokan-chatbot-loading-dots span:nth-child(1) { animation-delay: -0.32s; }
.dokan-chatbot-loading-dots span:nth-child(2) { animation-delay: -0.16s; }

@keyframes dokan-chatbot-bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Responsive Design */
@media (max-width: 480px) {
    .dokan-chatbot-interface {
        width: calc(100vw - 40px);
        height: calc(100vh - 120px);
        bottom: 70px;
        right: 20px;
    }

    .dokan-chatbot-button-text {
        display: none;
    }

    .dokan-chatbot-button {
        padding: 12px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .dokan-chatbot-interface {
        background: #1f2937;
        color: #f9fafb;
    }

    .dokan-chatbot-header {
        background: #374151;
        border-bottom-color: #4b5563;
    }

    .dokan-chatbot-message-ai .dokan-chatbot-message-content {
        background: #374151;
        color: #f9fafb;
    }

    .dokan-chatbot-input-area {
        background: #374151;
        border-top-color: #4b5563;
    }

    .dokan-chatbot-input-wrapper {
        background: #1f2937;
        border-color: #4b5563;
    }

    .dokan-chatbot-input {
        background: #1f2937;
        color: #f9fafb;
    }

    .dokan-chatbot-suggestion {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }

    .dokan-chatbot-suggestion:hover {
        background: #4b5563;
    }
}
</style>
