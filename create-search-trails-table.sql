-- Create search trails table for logging search queries
-- This table stores search trail data for analytics and dashboard

CREATE TABLE IF NOT EXISTS oc_openregister_search_trails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    register_id INT NULL,
    schema_id INT NULL,
    query_params TEXT NULL COMMENT 'JSON encoded query parameters',
    search_term VARCHAR(255) NULL,
    result_count INT NOT NULL DEFAULT 0,
    total_results INT NOT NULL DEFAULT 0,
    response_time DECIMAL(10, 2) NOT NULL COMMENT 'Response time in milliseconds',
    type VARCHAR(50) NOT NULL DEFAULT 'sync' COMMENT 'Type of search: sync, async, etc.',
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_register_id (register_id),
    INDEX idx_schema_id (schema_id),
    INDEX idx_search_term (search_term),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





