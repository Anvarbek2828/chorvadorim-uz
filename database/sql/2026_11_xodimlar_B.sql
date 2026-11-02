-- =====================================================================
-- Xodimlar B: xavfsiz ochirish (FK), ferma audit jurnali
-- Migratsiyalar (batch 15):
--   2026_11_02_100000_add_farm_foreign_keys_and_nullable_animal_user
--   2026_11_02_100100_create_farm_audit_logs_table
-- MariaDB 10.6 / MySQL. phpMyAdmin orqali bajarishdan OLDIN bazaning zaxira nusxasini oling.
--
-- Tartib:
--   1-blok (sxema)      - FAQAT BIR MARTA bajariladi (qayta ishlatilsa DROP FOREIGN KEY xato beradi).
--   2-blok (malumot)    - idempotent: qayta ishlatish xavfsiz.
--   3-blok (migrations) - idempotent.
--   4-blok (tekshiruv)  - bitta SELECT, bitta natija qatori; istalgancha ishlatish mumkin.
-- =====================================================================


-- =====================================================================
-- 1-BLOK. SXEMA (bir marta)
-- =====================================================================

-- 1.1. FK qoshishdan oldin: mavjud bolmagan fermaga ishora qiluvchi farm_id lar NULL qilinadi.
--      Masalan, Faza A dan keyin egasi akkauntini ochirgan fermaning AI yozuvlari. Yozuvlar ochirilmaydi.
--      Bu qadam ozi idempotent; FK qoshilishi uchun shart.
UPDATE `animals` a
LEFT JOIN `farms` f ON f.`id` = a.`farm_id`
SET a.`farm_id` = NULL
WHERE a.`farm_id` IS NOT NULL AND f.`id` IS NULL;

UPDATE `ai_usage_logs` l
LEFT JOIN `farms` f ON f.`id` = l.`farm_id`
SET l.`farm_id` = NULL
WHERE l.`farm_id` IS NOT NULL AND f.`id` IS NULL;

-- 1.2. animals.user_id (kim yaratdi) NULL bola oladi: xodim akkaunti ochsa, hayvon fermada qoladi.
--      Eski FK (ON DELETE CASCADE) olib tashlanadi; indeks animals_user_id_foreign qoladi.
ALTER TABLE `animals` DROP FOREIGN KEY `animals_user_id_foreign`;

ALTER TABLE `animals`
    MODIFY `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    ADD CONSTRAINT `animals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- 1.3. Ferma ochsa, uning hayvonlari ham ochadi (hozirgi xatti-harakat bilan bir xil).
--      AI yozuvlari esa qoladi, farm_id NULL boladi: platformaning AI xarajat tarixi saqlanadi.
--      Faza A da yaratilgan animals_farm_id_index va ai_usage_logs_farm_id_index ishlatiladi.
ALTER TABLE `animals`
    ADD CONSTRAINT `animals_farm_id_foreign` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE;

ALTER TABLE `ai_usage_logs`
    ADD CONSTRAINT `ai_usage_logs_farm_id_foreign` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE SET NULL;

-- 1.4. Ferma amallari jurnali. Ferma ochsa yozuvlar ham ochadi; amalni bajargan foydalanuvchi ochsa user_id NULL.
CREATE TABLE `farm_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `farm_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `amal` VARCHAR(50) NOT NULL,
    `obyekt_turi` VARCHAR(50) NOT NULL,
    `obyekt_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `eski_qiymat` JSON NULL DEFAULT NULL,
    `yangi_qiymat` JSON NULL DEFAULT NULL,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `farm_audit_logs_farm_id_created_at_index` (`farm_id`, `created_at`),
    KEY `farm_audit_logs_user_id_foreign` (`user_id`),
    CONSTRAINT `farm_audit_logs_farm_id_foreign` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `farm_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
-- 2-BLOK. MALUMOT (idempotent)
-- =====================================================================

START TRANSACTION;

-- 2.1. Ehtiyot uchun: tarif limitidan ortiq faol xodimlar toxtatiladi (eng oxirgi qoshilganlar).
--      Ilovadagi Farm::suspendExcessStaff bilan bir xil qoida: egasi hisobga kirmaydi, max_staff NULL = cheksiz,
--      tarif belgilanmagan yoki muddati otgan bolsa bepul (free) tarif limiti olinadi.
--      Faza A dan keyin xodimlar hali qoshilmagan bolsa, hech narsa ozgarmaydi.
UPDATE `farm_user` fu
JOIN (
    SELECT r.`id`
    FROM (
        SELECT m.`id`,
               ROW_NUMBER() OVER (PARTITION BY m.`farm_id` ORDER BY m.`created_at` ASC, m.`id` ASC) AS rn,
               p.`max_staff`
        FROM `farm_user` m
        JOIN `farms` f ON f.`id` = m.`farm_id`
        JOIN `plans` p ON p.`id` = CASE
            WHEN f.`plan_id` IS NOT NULL AND (f.`plan_tugash_sanasi` IS NULL OR f.`plan_tugash_sanasi` >= CURDATE()) THEN f.`plan_id`
            ELSE (SELECT pf.`id` FROM `plans` pf WHERE pf.`kod` = 'free' LIMIT 1)
        END
        WHERE m.`role` <> 'owner' AND m.`status` = 'active'
    ) r
    WHERE r.`max_staff` IS NOT NULL AND r.rn > r.`max_staff`
) ortiqcha ON ortiqcha.`id` = fu.`id`
SET fu.`status` = 'suspended', fu.`updated_at` = NOW();

COMMIT;


-- =====================================================================
-- 3-BLOK. migrations JADVALIGA YOZUV (batch 15) - php artisan migrate ularni qayta ishlatmasligi uchun
-- =====================================================================

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_100000_add_farm_foreign_keys_and_nullable_animal_user', 15
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_02_100000_add_farm_foreign_keys_and_nullable_animal_user'
);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_02_100100_create_farm_audit_logs_table', 15
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_02_100100_create_farm_audit_logs_table'
);


-- =====================================================================
-- 4-BLOK. TEKSHIRUV (bitta qator). Kutilgan qiymatlar:
--   animals_user_id_nullable = YES
--   animals_user_fk          = SET NULL
--   animals_farm_fk          = CASCADE
--   ai_logs_farm_fk          = SET NULL
--   audit_jadval             = 1
--   audit_farm_fk            = CASCADE
--   audit_user_fk            = SET NULL
--   migrations_yozuvi        = 2
--   animals_yetim            = 0   (mavjud bolmagan fermaga ishora)
--   ai_logs_yetim            = 0
--   limitdan_ortiq_xodim     = 0
--   animals_fermasiz         = malumot uchun (farm_id NULL hayvonlar; 0 bolishi kerak, bolmasa korib chiqing)
-- =====================================================================

SELECT
    (SELECT `IS_NULLABLE` FROM information_schema.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'animals' AND `COLUMN_NAME` = 'user_id') AS animals_user_id_nullable,
    (SELECT `DELETE_RULE` FROM information_schema.`REFERENTIAL_CONSTRAINTS`
        WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'animals' AND `CONSTRAINT_NAME` = 'animals_user_id_foreign') AS animals_user_fk,
    (SELECT `DELETE_RULE` FROM information_schema.`REFERENTIAL_CONSTRAINTS`
        WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'animals' AND `CONSTRAINT_NAME` = 'animals_farm_id_foreign') AS animals_farm_fk,
    (SELECT `DELETE_RULE` FROM information_schema.`REFERENTIAL_CONSTRAINTS`
        WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'ai_usage_logs' AND `CONSTRAINT_NAME` = 'ai_usage_logs_farm_id_foreign') AS ai_logs_farm_fk,
    (SELECT COUNT(*) FROM information_schema.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'farm_audit_logs') AS audit_jadval,
    (SELECT `DELETE_RULE` FROM information_schema.`REFERENTIAL_CONSTRAINTS`
        WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'farm_audit_logs' AND `CONSTRAINT_NAME` = 'farm_audit_logs_farm_id_foreign') AS audit_farm_fk,
    (SELECT `DELETE_RULE` FROM information_schema.`REFERENTIAL_CONSTRAINTS`
        WHERE `CONSTRAINT_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'farm_audit_logs' AND `CONSTRAINT_NAME` = 'farm_audit_logs_user_id_foreign') AS audit_user_fk,
    (SELECT COUNT(*) FROM `migrations`
        WHERE `batch` = 15 AND `migration` IN (
            '2026_11_02_100000_add_farm_foreign_keys_and_nullable_animal_user',
            '2026_11_02_100100_create_farm_audit_logs_table'
        )) AS migrations_yozuvi,
    (SELECT COUNT(*) FROM `animals` a
        WHERE a.`farm_id` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `farms` f WHERE f.`id` = a.`farm_id`)) AS animals_yetim,
    (SELECT COUNT(*) FROM `ai_usage_logs` l
        WHERE l.`farm_id` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `farms` f WHERE f.`id` = l.`farm_id`)) AS ai_logs_yetim,
    (SELECT COUNT(*) FROM (
        SELECT ROW_NUMBER() OVER (PARTITION BY m.`farm_id` ORDER BY m.`created_at` ASC, m.`id` ASC) AS rn, p.`max_staff`
        FROM `farm_user` m
        JOIN `farms` f ON f.`id` = m.`farm_id`
        JOIN `plans` p ON p.`id` = CASE
            WHEN f.`plan_id` IS NOT NULL AND (f.`plan_tugash_sanasi` IS NULL OR f.`plan_tugash_sanasi` >= CURDATE()) THEN f.`plan_id`
            ELSE (SELECT pf.`id` FROM `plans` pf WHERE pf.`kod` = 'free' LIMIT 1)
        END
        WHERE m.`role` <> 'owner' AND m.`status` = 'active'
    ) x WHERE x.`max_staff` IS NOT NULL AND x.rn > x.`max_staff`) AS limitdan_ortiq_xodim,
    (SELECT COUNT(*) FROM `animals` WHERE `farm_id` IS NULL) AS animals_fermasiz;
