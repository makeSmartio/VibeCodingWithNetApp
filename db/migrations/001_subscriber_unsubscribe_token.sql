-- One-time upgrade for databases created before unsubscribe support.
-- (New databases get this column from schema.sql.)
ALTER TABLE subscribers ADD COLUMN unsubscribe_token CHAR(32) NULL AFTER company;
UPDATE subscribers SET unsubscribe_token = LEFT(SHA2(CONCAT(UUID(), RAND(), email), 256), 32) WHERE unsubscribe_token IS NULL;
ALTER TABLE subscribers MODIFY unsubscribe_token CHAR(32) NOT NULL, ADD UNIQUE KEY uq_unsubscribe_token (unsubscribe_token);
