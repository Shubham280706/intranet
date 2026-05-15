-- WorkSpace Intranet — Database Setup
-- MySQL 8+ compatible

CREATE DATABASE IF NOT EXISTS intranet_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE intranet_db;

-- ─────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)  NOT NULL,
    email        VARCHAR(150)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role         ENUM('admin','employee') NOT NULL DEFAULT 'employee',
    department   VARCHAR(100)  DEFAULT NULL,
    avatar_color VARCHAR(7)    NOT NULL DEFAULT '#2563EB',
    is_online    TINYINT(1)    NOT NULL DEFAULT 0,
    created_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- TASKS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tasks (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(200)  NOT NULL,
    description TEXT          DEFAULT NULL,
    assigned_to INT UNSIGNED  NOT NULL,
    created_by  INT UNSIGNED  NOT NULL,
    priority    ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    status      ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
    due_date    DATE          DEFAULT NULL,
    created_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- MESSAGES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS messages (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message     TEXT         NOT NULL,
    sent_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    FOREIGN KEY (sender_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_receiver_unread (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- ANNOUNCEMENTS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS announcements (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    body       TEXT         NOT NULL,
    category   ENUM('general','hr','it','finance','operations') NOT NULL DEFAULT 'general',
    is_pinned  TINYINT(1)   NOT NULL DEFAULT 0,
    author_id  INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pinned_date (is_pinned DESC, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- NOTIFICATIONS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    message    VARCHAR(255) NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────
-- SEED DATA
-- ─────────────────────────────────────────
-- Default admin: admin@company.com / admin123
INSERT INTO users (name, email, password_hash, role, department, avatar_color)
VALUES (
    'Admin User',
    'admin@company.com',
    '$2y$12$dsLqIq0M7fiAjuy2OFecUO97SXe2oaqEPmE/5XHbNUpQ3si6sOFXm',
    'admin',
    'Management',
    '#2563EB'
) ON DUPLICATE KEY UPDATE id = id;

-- Sample employees (password = admin123 for all)
INSERT INTO users (name, email, password_hash, role, department, avatar_color) VALUES
('Alice Johnson',  'alice@company.com',  '$2y$12$dsLqIq0M7fiAjuy2OFecUO97SXe2oaqEPmE/5XHbNUpQ3si6sOFXm', 'employee', 'Engineering', '#7C3AED'),
('Bob Martinez',   'bob@company.com',    '$2y$12$dsLqIq0M7fiAjuy2OFecUO97SXe2oaqEPmE/5XHbNUpQ3si6sOFXm', 'employee', 'Design',      '#059669'),
('Carol Smith',    'carol@company.com',  '$2y$12$dsLqIq0M7fiAjuy2OFecUO97SXe2oaqEPmE/5XHbNUpQ3si6sOFXm', 'employee', 'HR',          '#DC2626'),
('David Lee',      'david@company.com',  '$2y$12$dsLqIq0M7fiAjuy2OFecUO97SXe2oaqEPmE/5XHbNUpQ3si6sOFXm', 'employee', 'Finance',     '#D97706')
ON DUPLICATE KEY UPDATE id = id;

-- Sample announcements
INSERT INTO announcements (title, body, category, is_pinned, author_id) VALUES
('Welcome to WorkSpace Intranet', 'We are excited to launch our new company intranet. Use it to collaborate, stay updated, and manage your tasks.', 'general', 1, 1),
('Q2 All-Hands Meeting', 'Join us this Friday at 3pm in the main conference room for our quarterly all-hands meeting.', 'general', 0, 1),
('IT Maintenance Window', 'Scheduled maintenance this Saturday 2am–4am. Expect brief service interruptions.', 'it', 0, 1)
ON DUPLICATE KEY UPDATE id = id;

-- Sample tasks
INSERT INTO tasks (title, description, assigned_to, created_by, priority, status, due_date) VALUES
('Set up development environment', 'Install all required tools and configure local dev environment.', 2, 1, 'high',   'completed',   CURDATE()),
('Design new dashboard mockup',   'Create wireframes for the updated dashboard UI.',               3, 1, 'medium', 'in_progress', DATE_ADD(CURDATE(), INTERVAL 3 DAY)),
('Review Q2 expense reports',     'Audit and approve all Q2 department expense submissions.',      5, 1, 'high',   'pending',     DATE_ADD(CURDATE(), INTERVAL 7 DAY)),
('Onboard new team members',      'Prepare onboarding docs and schedule orientation sessions.',    4, 1, 'medium', 'pending',     DATE_ADD(CURDATE(), INTERVAL 5 DAY))
ON DUPLICATE KEY UPDATE id = id;
