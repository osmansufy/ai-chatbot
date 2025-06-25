<?php

namespace WeDevs\Dokan\Chatbot\Utils;

class ChatHistory {

    /**
     * Save message to database
     *
     * @param int $user_id
     * @param int|null $vendor_id
     * @param string $role
     * @param string $message
     * @param string $response
     * @param array $context_data
     * @return bool
     */
    public function save_message( int $user_id, ?int $vendor_id, string $role, string $message, string $response, array $context_data = [] ): bool {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';

        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'vendor_id' => $vendor_id,
                'role' => $role,
                'message' => $message,
                'response' => $response,
                'context_data' => wp_json_encode( $context_data ),
                'created_at' => current_time( 'mysql' ),
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        return false !== $result;
    }

    /**
     * Get recent messages for a user
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public function get_recent_messages( int $user_id, int $limit = 5 ): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name}
                WHERE user_id = %d
                ORDER BY created_at DESC
                LIMIT %d",
                $user_id,
                $limit
            ),
            ARRAY_A
        );

        return array_reverse( $messages );
    }

    /**
     * Get messages with pagination
     *
     * @param int $user_id
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_messages( int $user_id, int $limit = 20, int $offset = 0 ): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table_name}
                WHERE user_id = %d
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d",
                $user_id,
                $limit,
                $offset
            ),
            ARRAY_A
        );

        return array_reverse( $messages );
    }

    /**
     * Get total message count for a user
     *
     * @param int $user_id
     * @return int
     */
    public function get_total_messages( int $user_id ): int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE user_id = %d",
                $user_id
            )
        );
    }

    /**
     * Get message count within time window
     *
     * @param int $user_id
     * @param int $time_window_seconds
     * @return int
     */
    public function get_message_count( int $user_id, int $time_window_seconds ): int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';
        $time_threshold = date( 'Y-m-d H:i:s', time() - $time_window_seconds );

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name}
                WHERE user_id = %d AND created_at > %s",
                $user_id,
                $time_threshold
            )
        );
    }

    /**
     * Clean old messages
     *
     * @param int $days_to_keep
     * @return int
     */
    public function clean_old_messages( int $days_to_keep = 30 ): int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';
        $cutoff_date = date( 'Y-m-d H:i:s', strtotime( "-{$days_to_keep} days" ) );

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $cutoff_date
            )
        );

        return (int) $deleted;
    }

    /**
     * Delete messages for a specific user
     *
     * @param int $user_id
     * @return int
     */
    public function delete_user_messages( int $user_id ): int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';

        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE user_id = %d",
                $user_id
            )
        );

        return (int) $deleted;
    }

    /**
     * Get conversation summary
     *
     * @param int $user_id
     * @param int $days
     * @return array
     */
    public function get_conversation_summary( int $user_id, int $days = 7 ): array {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';
        $start_date = date( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

        $summary = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) as total_messages,
                    COUNT(DISTINCT DATE(created_at)) as active_days,
                    MIN(created_at) as first_message,
                    MAX(created_at) as last_message
                FROM {$table_name}
                WHERE user_id = %d AND created_at > %s",
                $user_id,
                $start_date
            ),
            ARRAY_A
        );

        return $summary ?: [
            'total_messages' => 0,
            'active_days' => 0,
            'first_message' => null,
            'last_message' => null,
        ];
    }

    /**
     * Get the ID of the last message for a user
     *
     * @param int $user_id
     * @return int|null
     */
    public function get_last_message_id( int $user_id ): ?int {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dokan_chatbot_conversations';

        $message_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table_name}
                WHERE user_id = %d
                ORDER BY created_at DESC
                LIMIT 1",
                $user_id
            )
        );

        return $message_id ? (int) $message_id : null;
    }

    /**
     * Clear user's conversation history
     *
     * @param int $user_id
     * @return bool
     */
    public function clear_user_history( int $user_id ): bool {
        $deleted_count = $this->delete_user_messages( $user_id );
        return $deleted_count > 0;
    }
}
