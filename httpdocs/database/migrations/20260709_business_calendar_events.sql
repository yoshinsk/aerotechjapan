-- httpdocs/database/migrations/20260709_business_calendar_events.sql
-- 営業日カレンダーへ日別イベント名とURLを登録する列を追加します。

ALTER TABLE business_day_exceptions
    ADD COLUMN IF NOT EXISTS event_name_ja VARCHAR(255) NOT NULL DEFAULT '' AFTER note_en,
    ADD COLUMN IF NOT EXISTS event_name_en VARCHAR(255) NOT NULL DEFAULT '' AFTER event_name_ja,
    ADD COLUMN IF NOT EXISTS event_url VARCHAR(500) NOT NULL DEFAULT '' AFTER event_name_en;
