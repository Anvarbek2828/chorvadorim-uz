-- =====================================================================
-- Xodimlar A: farms, farm_user, egalik user_id dan farm_id ga o'tadi
-- Migratsiya: 2026_11_01_100000_create_farms_and_farm_user_tables (batch 14)
-- Faqat MySQL. phpMyAdmin'da bajarishdan OLDIN bazaning zaxira nusxasini oling.
--
-- Tartib:
--   1-blok (sxema)      — FAQAT BIR MARTA bajariladi (ALTER TABLE qayta ishlatilsa xato beradi).
--   2-blok (ko'chirish) — idempotent: qayta ishlatilsa dublikat qilmaydi.
--   3-blok (migrations) — idempotent.
--   4-blok (tekshiruv)  — istalgancha ishlatish mumkin.
-- =====================================================================


-- =====================================================================
-- 1-BLOK. SXEMA
-- =====================================================================

-- 1.1. Fermalar jadvali. Tarif ustunlari users dagi bilan bir xil nomda (plan_id, plan_tugash_sanasi).
CREATE TABLE `farms` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `owner_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `plan_tugash_sanasi` DATE NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `farms_owner_id_index` (`owner_id`),
    KEY `farms_plan_id_foreign` (`plan_id`),
    CONSTRAINT `farms_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `farms_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2. Ferma a'zoliklari (rol va holat). Bitta foydalanuvchi bitta fermada bir marta.
CREATE TABLE `farm_user` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `farm_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('owner', 'manager', 'worker', 'viewer') NOT NULL,
    `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    `invited_by` BIGINT UNSIGNED NULL DEFAULT NULL,
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `farm_user_farm_id_user_id_unique` (`farm_id`, `user_id`),
    KEY `farm_user_user_id_foreign` (`user_id`),
    KEY `farm_user_invited_by_foreign` (`invited_by`),
    CONSTRAINT `farm_user_farm_id_foreign` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `farm_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `farm_user_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3. Foydalanuvchining joriy fermasi (ferma o'chsa NULL bo'ladi).
ALTER TABLE `users`
    ADD COLUMN `current_farm_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `plan_tugash_sanasi`,
    ADD CONSTRAINT `users_current_farm_id_foreign` FOREIGN KEY (`current_farm_id`) REFERENCES `farms` (`id`) ON DELETE SET NULL;

-- 1.4. Ferma ma'lumoti jadvallariga farm_id. user_id qoladi va "kim yaratdi" ma'nosini oladi.
ALTER TABLE `animals`
    ADD COLUMN `farm_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`,
    ADD INDEX `animals_farm_id_index` (`farm_id`);

ALTER TABLE `ai_usage_logs`
    ADD COLUMN `farm_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`,
    ADD INDEX `ai_usage_logs_farm_id_index` (`farm_id`);

ALTER TABLE `admin_audit_logs`
    ADD COLUMN `farm_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `id`,
    ADD INDEX `admin_audit_logs_farm_id_index` (`farm_id`);

-- 1.5. Tarifdagi xodimlar chegarasi. NULL = cheksiz.
ALTER TABLE `plans`
    ADD COLUMN `max_staff` INT UNSIGNED NULL DEFAULT NULL AFTER `max_hayvon`;


-- =====================================================================
-- 2-BLOK. MA'LUMOTLARNI KO'CHIRISH (idempotent)
-- =====================================================================

START TRANSACTION;

-- 2.1. Hech qaysi fermaga aloqasi yo'q (egasi ham, a'zosi ham emas) har bir foydalanuvchiga ferma:
--      nomi — foydalanuvchi ismi, tarif users dan nusxa. Faza B dan keyin qayta ishlatilsa ham xodimlarga ferma ochmaydi.
INSERT INTO `farms` (`name`, `owner_id`, `plan_id`, `plan_tugash_sanasi`, `created_at`, `updated_at`)
SELECT u.`name`, u.`id`, u.`plan_id`, u.`plan_tugash_sanasi`, NOW(), NOW()
FROM `users` u
WHERE NOT EXISTS (SELECT 1 FROM `farms` f WHERE f.`owner_id` = u.`id`)
  AND NOT EXISTS (SELECT 1 FROM `farm_user` fu WHERE fu.`user_id` = u.`id`)
ORDER BY u.`id`;

-- 2.2. Ferma egasini farm_user ga role='owner' qilib yozish (bor bo'lsa qo'shilmaydi).
INSERT INTO `farm_user` (`farm_id`, `user_id`, `role`, `status`, `must_change_password`, `created_at`, `updated_at`)
SELECT f.`id`, f.`owner_id`, 'owner', 'active', 0, NOW(), NOW()
FROM `farms` f
WHERE NOT EXISTS (
    SELECT 1 FROM `farm_user` fu WHERE fu.`farm_id` = f.`id` AND fu.`user_id` = f.`owner_id`
);

-- 2.3. users.current_farm_id — foydalanuvchi egasi bo'lgan (eng birinchi) ferma. Faqat bo'sh bo'lsa.
UPDATE `users` u
JOIN (SELECT `owner_id`, MIN(`id`) AS `farm_id` FROM `farms` GROUP BY `owner_id`) f ON f.`owner_id` = u.`id`
SET u.`current_farm_id` = f.`farm_id`
WHERE u.`current_farm_id` IS NULL;

-- 2.4. animals.farm_id = yaratgan foydalanuvchining fermasi (arxivdagi hayvonlar ham).
UPDATE `animals` a
JOIN (SELECT `owner_id`, MIN(`id`) AS `farm_id` FROM `farms` GROUP BY `owner_id`) f ON f.`owner_id` = a.`user_id`
SET a.`farm_id` = f.`farm_id`
WHERE a.`farm_id` IS NULL;

-- 2.5. ai_usage_logs.farm_id = foydalanuvchining fermasi.
UPDATE `ai_usage_logs` l
JOIN (SELECT `owner_id`, MIN(`id`) AS `farm_id` FROM `farms` GROUP BY `owner_id`) f ON f.`owner_id` = l.`user_id`
SET l.`farm_id` = f.`farm_id`
WHERE l.`farm_id` IS NULL;

-- 2.6. Foydalanuvchisi o'chirilgan (user_id NULL) AI yozuvlari — hayvon orqali.
UPDATE `ai_usage_logs` l
JOIN `animals` a ON a.`id` = l.`animal_id`
SET l.`farm_id` = a.`farm_id`
WHERE l.`farm_id` IS NULL AND a.`farm_id` IS NOT NULL;

-- 2.7. Admin audit jurnali: foydalanuvchiga oid yozuvlar (tarif, blok, import) — o'sha foydalanuvchining fermasi.
--      Tarif sozlamalari (obyekt_turi='plan') platforma darajasida, farm_id NULL qoladi.
UPDATE `admin_audit_logs` l
JOIN (SELECT `owner_id`, MIN(`id`) AS `farm_id` FROM `farms` GROUP BY `owner_id`) f ON f.`owner_id` = l.`obyekt_id`
SET l.`farm_id` = f.`farm_id`
WHERE l.`obyekt_turi` = 'user' AND l.`farm_id` IS NULL;

-- 2.8. Tariflar bo'yicha xodimlar chegarasi: Free 0, Basic 2, Pro 5, Max cheksiz (NULL).
UPDATE `plans` SET `max_staff` = 0    WHERE `kod` = 'free';
UPDATE `plans` SET `max_staff` = 2    WHERE `kod` = 'basic';
UPDATE `plans` SET `max_staff` = 5    WHERE `kod` = 'pro';
UPDATE `plans` SET `max_staff` = NULL WHERE `kod` = 'max';

COMMIT;


-- =====================================================================
-- 3-BLOK. migrations JADVALIGA YOZUV (batch 14) — php artisan migrate uni qayta ishlatmasligi uchun
-- =====================================================================

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_01_100000_create_farms_and_farm_user_tables', 14
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_01_100000_create_farms_and_farm_user_tables'
);


-- =====================================================================
-- 4-BLOK. TEKSHIRUV (har bir qatorda 0 bo'lishi kerak, alohida ko'rsatilganlardan tashqari)
-- =====================================================================

-- 4.1. Fermasiz foydalanuvchilar (0 bo'lishi kerak).
SELECT COUNT(*) AS fermasiz_foydalanuvchilar
FROM `users` u
WHERE NOT EXISTS (SELECT 1 FROM `farm_user` fu WHERE fu.`user_id` = u.`id` AND fu.`status` = 'active');

-- 4.2. current_farm_id bo'sh foydalanuvchilar (0 bo'lishi kerak).
SELECT COUNT(*) AS current_farm_id_bosh FROM `users` WHERE `current_farm_id` IS NULL;

-- 4.3. Har bir ferma jadvalida farm_id IS NULL soni (0 bo'lishi kerak).
SELECT 'animals' AS jadval, COUNT(*) AS farm_id_bosh FROM `animals` WHERE `farm_id` IS NULL
UNION ALL
SELECT 'ai_usage_logs', COUNT(*) FROM `ai_usage_logs` WHERE `farm_id` IS NULL AND (`user_id` IS NOT NULL OR `animal_id` IS NOT NULL)
UNION ALL
SELECT 'admin_audit_logs (obyekt_turi=user)', COUNT(*) FROM `admin_audit_logs` l
    WHERE l.`farm_id` IS NULL AND l.`obyekt_turi` = 'user' AND EXISTS (SELECT 1 FROM `users` u WHERE u.`id` = l.`obyekt_id`);

-- 4.4. Ma'lumot uchun (0 bo'lishi shart emas): farm_id ni aniqlab bo'lmaydigan yozuvlar —
--      foydalanuvchisi ham, hayvoni ham o'chirilgan AI yozuvlari va o'chirilgan foydalanuvchiga oid audit yozuvlari.
SELECT 'ai_usage_logs (user va animal o''chirilgan)' AS jadval, COUNT(*) AS soni FROM `ai_usage_logs`
    WHERE `farm_id` IS NULL AND `user_id` IS NULL AND `animal_id` IS NULL
UNION ALL
SELECT 'admin_audit_logs (platforma / o''chirilgan user)', COUNT(*) FROM `admin_audit_logs` WHERE `farm_id` IS NULL;

-- 4.5. Dublikat tekshiruvi: bir foydalanuvchi egasi bo'lgan fermalar soni (bo'sh natija bo'lishi kerak).
SELECT `owner_id`, COUNT(*) AS fermalar FROM `farms` GROUP BY `owner_id` HAVING COUNT(*) > 1;

-- 4.6. Tarif nusxasi to'g'riligi: ferma tarifi egasining tarifidan farq qiladigan yozuvlar (0 bo'lishi kerak).
SELECT COUNT(*) AS tarif_farqi
FROM `farms` f
JOIN `users` u ON u.`id` = f.`owner_id`
WHERE NOT (f.`plan_id` <=> u.`plan_id`) OR NOT (f.`plan_tugash_sanasi` <=> u.`plan_tugash_sanasi`);
