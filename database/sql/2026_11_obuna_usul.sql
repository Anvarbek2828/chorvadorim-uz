-- =====================================================================
-- Obuna: farm_payments.usul ga yangi qiymat - tolov_tizimi (Tolov tizimlari orqali)
-- Migratsiya (batch 17):
--   2026_11_04_100200_add_tolov_tizimi_to_farm_payments_usul
-- MariaDB 10.6 / MySQL. phpMyAdmin orqali bajarishdan OLDIN bazaning zaxira nusxasini oling.
-- Bloklarni tartib bilan, bittadan bajaring.
--
-- Tartib:
--   1-blok (ALTER TABLE)   - bir marta (qayta ishlatilsa ham zarari yoq, natija bir xil).
--   2-blok (migrations)    - FAQAT BIR MARTA.
--   3-blok (tekshiruv)     - faqat oqiladi, istalgancha.
--
-- Yangi qiymat ENUM oxiriga qoshiladi: mavjud qiymatlar (bank, naqd, boshqa) va yozuvlar ozgarmaydi.
-- =====================================================================


-- =====================================================================
-- 1-BLOK. ALTER TABLE
-- =====================================================================

ALTER TABLE `farm_payments`
    MODIFY COLUMN `usul` ENUM('bank', 'naqd', 'boshqa', 'tolov_tizimi') NULL DEFAULT NULL;


-- =====================================================================
-- 2-BLOK. migrations JADVALIGA YOZUV (batch 17, bir marta)
-- php artisan migrate uni qayta ishlatmasligi uchun.
-- =====================================================================

INSERT INTO `migrations` (`migration`, `batch`) VALUES ('2026_11_04_100200_add_tolov_tizimi_to_farm_payments_usul', 17);


-- =====================================================================
-- 3-BLOK. TEKSHIRUV
-- Kutilgan: Type = enum('bank','naqd','boshqa','tolov_tizimi'), Null = YES, Default = NULL
-- =====================================================================

SHOW COLUMNS FROM `farm_payments` LIKE 'usul';
