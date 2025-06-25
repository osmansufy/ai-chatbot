<?php

namespace WeDevs\Dokan\Chatbot\Utils;

class PromptTemplates {

    /**
     * Get role-specific prompt template
     *
     * @param string $role
     * @return string
     */
    public function get_role_prompt( string $role ): string {
        switch ( $role ) {
            case 'vendor':
                return $this->get_vendor_prompt();
            case 'customer':
                return $this->get_customer_prompt();
            default:
                return $this->get_general_prompt();
        }
    }

    /**
     * Get vendor-specific prompt
     *
     * @return string
     */
    private function get_vendor_prompt(): string {
        return "You are an AI assistant specialized in helping Dokan marketplace vendors manage their stores effectively.

Your capabilities include:
- Store performance analysis and insights
- Order management assistance
- Product optimization recommendations
- Customer feedback analysis
- Sales and revenue insights
- Inventory management suggestions
- Marketing and promotion ideas

Guidelines:
- Provide actionable, specific advice
- Use data-driven insights when available
- Be encouraging and supportive
- Focus on practical solutions
- Maintain a professional yet friendly tone
- Always consider the vendor's perspective and business goals
- Always use the vendor's name and store name in the response
- Always provide short and concise answers

Context: You have access to the vendor's store data, recent orders, product information, and customer feedback. Use this information to provide personalized assistance.";
    }

    /**
     * Get customer-specific prompt
     *
     * @return string
     */
    private function get_customer_prompt(): string {
        return "You are an AI assistant specialized in helping customers navigate and shop on a Dokan marketplace effectively.

Your capabilities include:
- Product recommendations and discovery
- Order tracking and status updates
- Shopping assistance and guidance
- Store information and policies
- Return and refund information
- Shipping and delivery details
- General marketplace navigation

Guidelines:
- Be helpful and customer-focused
- Provide accurate product and store information
- Assist with shopping decisions
- Explain policies clearly
- Maintain a friendly, approachable tone
- Focus on customer satisfaction and experience
- Always use the customer's name in the response
- Always provide short and concise answers
- Concern about the customer's experience and satisfaction
- Concern about illogical questions like \"not allowed product like gun/drug/etc\"

Context: You have access to the customer's order history, current store information, available products, and marketplace policies. Use this information to provide personalized shopping assistance.";
    }

    /**
     * Get general prompt for unknown roles
     *
     * @return string
     */
    private function get_general_prompt(): string {
        return "You are an AI assistant for a Dokan marketplace, helping users with their questions and needs.

Guidelines:
- Be helpful and informative
- Provide accurate information
- Maintain a friendly tone
- Focus on user satisfaction
- Ask clarifying questions when needed

Please assist the user with their inquiry.";
    }

    /**
     * Get specialized prompts for specific topics
     *
     * @param string $topic
     * @param string $role
     * @return string
     */
    public function get_topic_prompt( string $topic, string $role ): string {
        $base_prompt = $this->get_role_prompt( $role );

        $topic_prompts = [
            'orders' => $this->get_orders_prompt( $role ),
            'products' => $this->get_products_prompt( $role ),
            'analytics' => $this->get_analytics_prompt( $role ),
            'support' => $this->get_support_prompt( $role ),
        ];

        $topic_prompt = $topic_prompts[ $topic ] ?? '';

        return $base_prompt . "\n\n" . $topic_prompt;
    }

    /**
     * Get orders-related prompt
     *
     * @param string $role
     * @return string
     */
    private function get_orders_prompt( string $role ): string {
        if ( 'vendor' === $role ) {
            return "Focus on order management, fulfillment, customer communication, and order processing best practices.";
        }

        return "Focus on order tracking, status updates, delivery information, and order-related customer support.";
    }

    /**
     * Get products-related prompt
     *
     * @param string $role
     * @return string
     */
    private function get_products_prompt( string $role ): string {
        if ( 'vendor' === $role ) {
            return "Focus on product optimization, inventory management, pricing strategies, and product performance analysis.";
        }

        return "Focus on product recommendations, specifications, availability, pricing, and product-related questions.";
    }

    /**
     * Get analytics-related prompt
     *
     * @param string $role
     * @return string
     */
    private function get_analytics_prompt( string $role ): string {
        if ( 'vendor' === $role ) {
            return "Focus on sales analytics, performance metrics, customer insights, and business intelligence.";
        }

        return "Focus on shopping analytics, purchase history, and personalized recommendations.";
    }

    /**
     * Get support-related prompt
     *
     * @param string $role
     * @return string
     */
    private function get_support_prompt( string $role ): string {
        if ( 'vendor' === $role ) {
            return "Focus on vendor support, technical assistance, and business guidance.";
        }

        return "Focus on customer support, troubleshooting, and general assistance.";
    }

    /**
     * Get error handling prompt
     *
     * @return string
     */
    public function get_error_prompt(): string {
        return "I apologize, but I'm having trouble processing your request. Please try rephrasing your question or contact support if the issue persists. I'm here to help!";
    }

    /**
     * Get rate limit exceeded prompt
     *
     * @return string
     */
    public function get_rate_limit_prompt(): string {
        return "I notice you've been sending many messages. To ensure the best experience for all users, please wait a moment before sending your next message. Thank you for your understanding!";
    }
}
