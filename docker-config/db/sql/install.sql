-- 初期データ投入用
-- USE laravel;
-- INSERT INTO users (name, email) VALUES ('Taro','taro@example.com');
-- 1. ベーシックプラン（小規模向け：1,000通まで定額、超過は高め）
-- INSERT INTO `plans` (`name`, `code`, `pricing_type`, `base_price`, `included_mails`, `overage_unit_price`, `tax_rate`, `created_at`, `updated_at`)
-- VALUES ('Basic 1000', 'basic_1000', 'bundle', 5000.00, 1000, 10, 10.00, NOW(), NOW());

-- -- 2. スタンダードプラン（中規模向け：5,000通まで定額、超過単価を抑える）
-- INSERT INTO `plans` (`name`, `code`, `pricing_type`, `base_price`, `included_mails`, `overage_unit_price`, `tax_rate`, `created_at`, `updated_at`)
-- VALUES ('Standard 5000', 'std_5000', 'bundle', 15000.00, 5000, 5, 10.00, NOW(), NOW());

-- -- 3. プレミアムプラン（大規模向け：基本料金は高いが内包数も多く、単価が最安）
-- INSERT INTO `plans` (`name`, `code`, `pricing_type`, `base_price`, `included_mails`, `overage_unit_price`, `tax_rate`, `created_at`, `updated_at`)
-- VALUES ('Premium 20000', 'pre_20000', 'bundle', 45000.00, 20000, 2, 10.00, NOW(), NOW());