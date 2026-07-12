ALTER TABLE users
    ADD COLUMN birth_date DATE NULL AFTER bio,
    ADD COLUMN hire_date DATE NULL AFTER birth_date,
    ADD COLUMN celebrations_opt_out TINYINT(1) NOT NULL DEFAULT 0 AFTER hire_date;

INSERT INTO modules (slug, enabled) VALUES ('celebrations', 1)
    ON DUPLICATE KEY UPDATE slug = slug;
