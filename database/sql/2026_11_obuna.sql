-- =====================================================================
-- Obuna va tolov moduli: farm_payments, subscription_notifications, system_runs,
-- reminders.bajarilgan_at, users.locale
-- Migratsiyalar (batch 16):
--   2026_11_04_100000_create_subscription_tables
--   2026_11_04_100100_add_bajarilgan_at_and_locale_columns
-- MariaDB 10.6 / MySQL. phpMyAdmin orqali bajarishdan OLDIN bazaning zaxira nusxasini oling.
-- Bloklarni tartib bilan, bittadan bajaring. Har blokdan keyin uning tekshiruv SELECT natijasini koring.
--
-- Tartib:
--   1-blok (sxema)        - FAQAT BIR MARTA (qayta ishlatilsa CREATE TABLE va ADD COLUMN xato beradi).
--   2-blok (system_runs)  - FAQAT BIR MARTA (PRIMARY KEY takrorlanmaydi).
--   3-blok (migrations)   - FAQAT BIR MARTA.
--   4-blok (FK)           - SHOW CREATE TABLE, istalgancha.
--   5-blok (xavfsizlik)   - faqat oqiladi, istalgancha. Deploydan OLDIN va KEYIN koring.
--
-- Bugungi sana Toshkent vaqti bilan: DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR) (serverning vaqt zonasiga bogliq emas).
-- =====================================================================


-- =====================================================================
-- 1-BLOK. SXEMA (bir marta)
-- =====================================================================

-- 1.1. Tolovlar va bonuslar. Yozuv ochirilmaydi: bekor qilinganda bekor_qilingan_at belgilanadi.
--      Ferma ochsa farm_id NULL boladi, farm_nomi qoladi.
CREATE TABLE `farm_payments` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `farm_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `farm_nomi` VARCHAR(255) NOT NULL,
    `tur` ENUM('tolov', 'bonus') NOT NULL,
    `plan_id` BIGINT UNSIGNED NOT NULL,
    `avvalgi_plan_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `oylar` TINYINT UNSIGNED NOT NULL,
    `summa` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `usul` ENUM('bank', 'naqd', 'boshqa') NULL DEFAULT NULL,
    `hujjat_raqami` VARCHAR(100) NULL DEFAULT NULL,
    `izoh` TEXT NULL DEFAULT NULL,
    `avvalgi_tugash` DATE NULL DEFAULT NULL,
    `yangi_tugash` DATE NOT NULL,
    `admin_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `bekor_qilingan_at` DATETIME NULL DEFAULT NULL,
    `bekor_sababi` TEXT NULL DEFAULT NULL,
    `bekor_admin_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `farm_payments_farm_id_created_at_index` (`farm_id`, `created_at`),
    KEY `farm_payments_plan_id_foreign` (`plan_id`),
    KEY `farm_payments_admin_id_foreign` (`admin_id`),
    KEY `farm_payments_bekor_admin_id_foreign` (`bekor_admin_id`),
    CONSTRAINT `farm_payments_farm_id_foreign` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE SET NULL,
    CONSTRAINT `farm_payments_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `farm_payments_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `farm_payments_bekor_admin_id_foreign` FOREIGN KEY (`bekor_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2. Obuna haqidagi bot xabarlari: (ferma, foydalanuvchi, tur, tugash_sanasi) boyicha bir marta.
CREATE TABLE `subscription_notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `farm_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `tur` ENUM('oldin_3kun', 'tugadi', 'faqat_oqish') NOT NULL,
    `tugash_sanasi` DATE NOT NULL,
    `holat` ENUM('yuborildi', 'telegram_yoq', 'xato') NOT NULL,
    `urinishlar` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `xato` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `subscription_notifications_unique` (`farm_id`, `user_id`, `tur`, `tugash_sanasi`),
    KEY `subscription_notifications_user_id_foreign` (`user_id`),
    CONSTRAINT `subscription_notifications_farm_id_foreign` FOREIGN KEY (`farm_id`) REFERENCES `farms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `subscription_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3. Kunlik ishlar holati (cronsiz): har ish oz qatorida kuniga bir marta.
CREATE TABLE `system_runs` (
    `nom` VARCHAR(50) NOT NULL,
    `last_run_date` DATE NULL DEFAULT NULL,
    `last_started_at` DATETIME NULL DEFAULT NULL,
    `last_finished_at` DATETIME NULL DEFAULT NULL,
    `last_result` TEXT NULL DEFAULT NULL,
    PRIMARY KEY (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4. Eslatmani bajarildi deb belgilash.
ALTER TABLE `reminders`
    ADD COLUMN `bajarilgan_at` DATETIME NULL DEFAULT NULL AFTER `sent_at`;

-- 1.5. Foydalanuvchi tili (uz, uz_cyrl, ru). NULL - bot uch tilda yozadi.
ALTER TABLE `users`
    ADD COLUMN `locale` VARCHAR(10) NULL DEFAULT NULL AFTER `current_farm_id`;

-- 1-BLOK TEKSHIRUV (bitta qator). Kutilgan: jadvallar = 3, bajarilgan_at = 1, locale = 1
SELECT
    (SELECT COUNT(*) FROM information_schema.`TABLES`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` IN ('farm_payments', 'subscription_notifications', 'system_runs')) AS jadvallar,
    (SELECT COUNT(*) FROM information_schema.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'reminders' AND `COLUMN_NAME` = 'bajarilgan_at') AS bajarilgan_at,
    (SELECT COUNT(*) FROM information_schema.`COLUMNS`
        WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'users' AND `COLUMN_NAME` = 'locale') AS locale;


-- =====================================================================
-- 2-BLOK. system_runs BOSHLANGICH QATORLARI (bir marta)
-- Qator bolmasa kunlik ish hech qachon ishga tushmaydi.
-- =====================================================================

INSERT INTO `system_runs` (`nom`, `last_run_date`, `last_started_at`, `last_finished_at`, `last_result`) VALUES ('obuna', NULL, NULL, NULL, NULL);
INSERT INTO `system_runs` (`nom`, `last_run_date`, `last_started_at`, `last_finished_at`, `last_result`) VALUES ('eslatmalar', NULL, NULL, NULL, NULL);

-- 2-BLOK TEKSHIRUV (bitta qator). Kutilgan: qatorlar = 2, obuna = 1, eslatmalar = 1
SELECT
    (SELECT COUNT(*) FROM `system_runs`) AS qatorlar,
    (SELECT COUNT(*) FROM `system_runs` WHERE `nom` = 'obuna') AS obuna,
    (SELECT COUNT(*) FROM `system_runs` WHERE `nom` = 'eslatmalar') AS eslatmalar;


-- =====================================================================
-- 3-BLOK. migrations JADVALIGA YOZUV (batch 16, bir marta)
-- php artisan migrate ularni qayta ishlatmasligi uchun.
-- =====================================================================

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_04_100000_create_subscription_tables', 16);
INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_04_100100_add_bajarilgan_at_and_locale_columns', 16);

-- 3-BLOK TEKSHIRUV (bitta qator). Kutilgan: batch16 = 2, takroriy = 0
SELECT
    (SELECT COUNT(*) FROM `migrations` WHERE `batch` = 16 AND `migration` IN (
        '2026_11_04_100000_create_subscription_tables',
        '2026_11_04_100100_add_bajarilgan_at_and_locale_columns'
    )) AS batch16,
    (SELECT COUNT(*) - COUNT(DISTINCT `migration`) FROM `migrations`) AS takroriy;


-- =====================================================================
-- 4-BLOK. FK TEKSHIRUVI (SHOW CREATE TABLE). Natijadagi CONSTRAINT qatorlarini koring:
--   farm_payments:
--     farm_payments_farm_id_foreign         ON DELETE SET NULL
--     farm_payments_plan_id_foreign         ON DELETE RESTRICT (MariaDB RESTRICT ni yozmasligi mumkin, bu normal)
--     farm_payments_admin_id_foreign        ON DELETE SET NULL
--     farm_payments_bekor_admin_id_foreign  ON DELETE SET NULL
--   subscription_notifications:
--     subscription_notifications_farm_id_foreign  ON DELETE CASCADE
--     subscription_notifications_user_id_foreign  ON DELETE CASCADE
--     UNIQUE KEY subscription_notifications_unique (farm_id, user_id, tur, tugash_sanasi)
-- =====================================================================

SHOW CREATE TABLE `farm_payments`;
SHOW CREATE TABLE `subscription_notifications`;


-- =====================================================================
-- 5-BLOK. XAVFSIZLIK TEKSHIRUVLARI (faqat oqiladi, hech narsa ozgarmaydi)
-- =====================================================================

-- 5.1. Pullik tarifdagi barcha fermalar va hisoblangan holati.
--      Deploydan keyin kutilmaganda faqat_oqish holatiga tushadigan fermalar shu yerda korinadi.
--      Holat: muddatsiz (tugash sanasi yoq), faol (bugun <= T), imtiyoz (T+1, T+2), faqat_oqish (T+3 dan).
SELECT
    f.`id` AS ferma_id,
    f.`name` AS ferma,
    u.`email` AS ega_email,
    p.`kod` AS tarif,
    f.`plan_tugash_sanasi`,
    CASE
        WHEN f.`plan_tugash_sanasi` IS NULL THEN 'muddatsiz'
        WHEN DATEDIFF(DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR), f.`plan_tugash_sanasi`) <= 0 THEN 'faol'
        WHEN DATEDIFF(DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR), f.`plan_tugash_sanasi`) <= 2 THEN 'imtiyoz'
        ELSE 'faqat_oqish'
    END AS holat,
    DATEDIFF(DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR), f.`plan_tugash_sanasi`) AS tugaganiga_kun
FROM `farms` f
JOIN `plans` p ON p.`id` = f.`plan_id`
LEFT JOIN `users` u ON u.`id` = f.`owner_id`
WHERE p.`kod` <> 'free'
ORDER BY f.`plan_tugash_sanasi` IS NULL, f.`plan_tugash_sanasi`, f.`id`;

-- 5.2. Pullik tarifdagi, muddati otgan (plan_tugash_sanasi < bugun) fermalardagi toxtatilgan xodimlar.
--      Avvalgi qoida boyicha muddati otgan ferma bepul tarif limitiga tushib, xodimlari toxtatilgan bolishi mumkin.
--      Avtomatik qayta faollashtirilmaydi: natijaga qarab qaror qiling.
--      suspended_vaqti - farm_user oxirgi ozgargan vaqt (UTC). jurnal_vaqti va jurnal_amali - ferma jurnalidagi oxirgi toxtatish yozuvi.
SELECT
    f.`id` AS ferma_id,
    f.`name` AS ferma,
    p.`kod` AS tarif,
    f.`plan_tugash_sanasi`,
    us.`id` AS foydalanuvchi_id,
    us.`name` AS foydalanuvchi,
    us.`email`,
    fu.`role` AS rol,
    fu.`updated_at` AS suspended_vaqti,
    (SELECT MAX(l.`created_at`) FROM `farm_audit_logs` l
        WHERE l.`farm_id` = f.`id` AND l.`obyekt_id` = us.`id`
          AND l.`amal` IN ('staff.auto_suspended', 'staff.status_changed')) AS jurnal_vaqti,
    (SELECT l2.`amal` FROM `farm_audit_logs` l2
        WHERE l2.`farm_id` = f.`id` AND l2.`obyekt_id` = us.`id`
          AND l2.`amal` IN ('staff.auto_suspended', 'staff.status_changed')
        ORDER BY l2.`created_at` DESC, l2.`id` DESC LIMIT 1) AS jurnal_amali
FROM `farm_user` fu
JOIN `farms` f ON f.`id` = fu.`farm_id`
JOIN `plans` p ON p.`id` = f.`plan_id`
JOIN `users` us ON us.`id` = fu.`user_id`
WHERE p.`kod` <> 'free'
  AND f.`plan_tugash_sanasi` IS NOT NULL
  AND f.`plan_tugash_sanasi` < DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR)
  AND fu.`role` <> 'owner'
  AND fu.`status` = 'suspended'
ORDER BY f.`id`, fu.`updated_at`;

-- 5.3. Yuborilmagan va bajarilmagan eslatmalar (arxivdagi hayvonlarniki hisobga olinmaydi), bitta qator.
--      oxirgi_3_kun - kunlik ish yuboradi (due_date bugun yoki oldingi 2 kun ichida).
--      eskilari     - YUBORILMAYDI va belgilanmaydi, shunchaki otkazib yuboriladi.
--      kelajakdagi  - malumot uchun, oz vaqti kelganda yuboriladi.
SELECT
    SUM(r.`due_date` BETWEEN DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR) - INTERVAL 2 DAY AND DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR)) AS oxirgi_3_kun,
    SUM(r.`due_date` < DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR) - INTERVAL 2 DAY) AS eskilari,
    SUM(r.`due_date` > DATE(UTC_TIMESTAMP() + INTERVAL 5 HOUR)) AS kelajakdagi
FROM `reminders` r
JOIN `animals` a ON a.`id` = r.`animal_id` AND a.`deleted_at` IS NULL
WHERE r.`sent_at` IS NULL
  AND r.`bajarilgan_at` IS NULL;
