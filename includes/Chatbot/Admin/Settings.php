<?php

namespace WeDevs\Dokan\Chatbot\Admin;

use WeDevs\Dokan\Contracts\Hookable;

class Settings implements Hookable
{

    public function register_hooks(): void
    {
        add_filter('dokan_settings_sections', [$this, 'add_chatbot_section']);
        add_filter('dokan_settings_fields', [$this, 'add_chatbot_settings']);
    }

    /**
     * Add chatbot section to Dokan settings
     *
     * @param array $sections
     * @return array
     */
    public function add_chatbot_section(array $sections): array
    {
        $sections[] = [
            'id'                   => 'dokan_chatbot',
            'title'                => __('AI Chatbot', 'dokan-chatbot'),
            'icon_url'             => DOKAN_CHATBOT_ASSETS . 'images/chatbot-icon.svg',
            'description'          => __('Configure AI-powered chatbot for your marketplace', 'dokan-chatbot'),
            'document_link'        => 'https://dokan.co/docs/wordpress/settings/ai-chatbot/',
            'settings_title'       => __('AI Chatbot Settings', 'dokan-chatbot'),
            'settings_description' => __('Set up AI chatbot to provide intelligent assistance to vendors and customers.', 'dokan-chatbot'),
        ];

        return $sections;
    }

    /**
     * Add chatbot settings fields
     *
     * @param array $settings_fields
     * @return array
     */
    public function add_chatbot_settings(array $settings_fields): array
    {
        $settings_fields['dokan_chatbot'] = [
            'dokan_chatbot_enabled' => [
                'name'    => 'dokan_chatbot_enabled',
                'label'   => __('Enable AI Chatbot', 'dokan-chatbot'),
                'type'    => 'checkbox',
                'desc'    => __('Enable AI-powered chatbot for your marketplace', 'dokan-chatbot'),
                'default' => 'yes',
            ],
            'dokan_chatbot_vendor_access' => [
                'name'    => 'dokan_chatbot_vendor_access',
                'label'   => __('Vendor Access', 'dokan-chatbot'),
                'type'    => 'checkbox',
                'desc'    => __('Allow vendors to use the chatbot for store management assistance', 'dokan-chatbot'),
                'default' => 'yes',
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
            'dokan_chatbot_customer_access' => [
                'name'    => 'dokan_chatbot_customer_access',
                'label'   => __('Customer Access', 'dokan-chatbot'),
                'type'    => 'checkbox',
                'desc'    => __('Allow customers to use the chatbot for shopping assistance', 'dokan-chatbot'),
                'default' => 'yes',
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
            'dokan_chatbot_conversation_retention_days' => [
                'name'    => 'dokan_chatbot_conversation_retention_days',
                'label'   => __('Conversation Retention (Days)', 'dokan-chatbot'),
                'type'    => 'number',
                'desc'    => __('Number of days to keep conversation history. Set to 0 to disable retention.', 'dokan-chatbot'),
                'default' => 30,
                'min'     => 0,
                'max'     => 365,
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
            'dokan_chatbot_max_messages_per_session' => [
                'name'    => 'dokan_chatbot_max_messages_per_session',
                'label'   => __('Max Messages per Session', 'dokan-chatbot'),
                'type'    => 'number',
                'desc'    => __('Maximum number of messages a user can send in one hour to prevent abuse', 'dokan-chatbot'),
                'default' => 50,
                'min'     => 10,
                'max'     => 200,
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
            'dokan_chatbot_widget_position' => [
                'name'    => 'dokan_chatbot_widget_position',
                'label'   => __('Widget Position', 'dokan-chatbot'),
                'type'    => 'select',
                'options' => [
                    'bottom-right' => __('Bottom Right', 'dokan-chatbot'),
                    'bottom-left'  => __('Bottom Left', 'dokan-chatbot'),
                ],
                'desc'    => __('Choose the position of the chatbot widget on the page', 'dokan-chatbot'),
                'default' => 'bottom-right',
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
            'dokan_chatbot_welcome_message' => [
                'name'    => 'dokan_chatbot_welcome_message',
                'label'   => __('Welcome Message', 'dokan-chatbot'),
                'type'    => 'textarea',
                'desc'    => __('Custom welcome message shown when users open the chatbot', 'dokan-chatbot'),
                'default' => __('Hello! I\'m your AI assistant. I can help you with questions about products, orders, store information, and more. What would you like to know?', 'dokan-chatbot'),
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
            'dokan_chatbot_vendor_prompt' => [
                'name'    => 'dokan_chatbot_vendor_prompt',
                'label'   => __('Vendor AI Prompt', 'dokan-chatbot'),
                'type'    => 'textarea',
                'desc'    => __('Custom AI prompt for vendor interactions. Leave empty to use default.', 'dokan-chatbot'),
                'default' => '',
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                    'dokan_chatbot_vendor_access' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
            'dokan_chatbot_customer_prompt' => [
                'name'    => 'dokan_chatbot_customer_prompt',
                'label'   => __('Customer AI Prompt', 'dokan-chatbot'),
                'type'    => 'textarea',
                'desc'    => __('Custom AI prompt for customer interactions. Leave empty to use default.', 'dokan-chatbot'),
                'default' => '',
                'show_if' => [
                    'dokan_chatbot_enabled' => [
                        'equal' => 'yes',
                    ],
                    'dokan_chatbot_customer_access' => [
                        'equal' => 'yes',
                    ],
                ],
            ],
        ];

        return $settings_fields;
    }
}