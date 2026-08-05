-- Migration: typing indicator support for internal chat.
ALTER TABLE chat_members ADD COLUMN IF NOT EXISTS typing_at DATETIME DEFAULT NULL;
