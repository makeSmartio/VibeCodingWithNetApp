-- Schema for the Vibe-Coding with NetApp registration app.
-- Loaded automatically the first time the MySQL container starts.

-- One row per webinar (e.g. each Friday 4 PM ET happy hour).
CREATE TABLE IF NOT EXISTS sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  starts_at DATETIME NOT NULL COMMENT 'UTC',   -- always stored in UTC; shown as Eastern by the app
  join_url VARCHAR(500) NULL,                  -- meeting link shown after registering
  capacity INT UNSIGNED NULL,                  -- NULL = unlimited seats
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_starts_at (starts_at)              -- speeds up "upcoming sessions" lookups
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per person per session.
CREATE TABLE IF NOT EXISTS registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(255) NOT NULL,
  company VARCHAR(150) NOT NULL,
  question TEXT NULL,                          -- optional topic or question
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  -- The same email can't register twice for the same session.
  UNIQUE KEY uq_session_email (session_id, email),
  -- Deleting a session also removes its registrations.
  CONSTRAINT fk_reg_session FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mailing list sign-ups, independent of any session (people can join any time).
CREATE TABLE IF NOT EXISTS subscribers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  name VARCHAR(150) NULL,                      -- optional
  company VARCHAR(150) NULL,                   -- optional
  unsubscribe_token CHAR(32) NOT NULL,         -- secret random value used in the unsubscribe link
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_subscriber_email (email),      -- one row per email address
  UNIQUE KEY uq_unsubscribe_token (unsubscribe_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
