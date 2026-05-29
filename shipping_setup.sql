-- Shipping and Parcels Management System

-- Table for shipping countries and their fees
CREATE TABLE IF NOT EXISTS `shipping_countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `country_name` varchar(100) NOT NULL UNIQUE,
  `country_code` varchar(2) NOT NULL,
  `currency_code` varchar(3) NOT NULL DEFAULT 'XAF',
  `currency_symbol` varchar(5) NOT NULL DEFAULT 'XAF',
  `shipping_fee` int(11) NOT NULL DEFAULT 0,
  `estimated_days` int(11) DEFAULT 7,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_code` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Shipment/Parcel tracking table
CREATE TABLE IF NOT EXISTS `shipments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `tracking_number` varchar(50) NOT NULL UNIQUE,
  `country_id` int(11) NOT NULL,
  `country_name` varchar(100) DEFAULT NULL,
  `shipping_fee` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'pending',
  `shipped_date` datetime DEFAULT NULL,
  `delivered_date` datetime DEFAULT NULL,
  `estimated_delivery` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`country_id`) REFERENCES `shipping_countries`(`id`),
  KEY `tracking_number` (`tracking_number`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Update orders table to include shipping and country info
ALTER TABLE `orders` 
ADD COLUMN IF NOT EXISTS `shipping_country_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `shipping_fee` int(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `grand_total` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `status` varchar(50) DEFAULT 'pending',
ADD FOREIGN KEY (`shipping_country_id`) REFERENCES `shipping_countries`(`id`);

-- Insert default shipping countries with XAF pricing
INSERT INTO `shipping_countries` (`country_name`, `country_code`, `currency_code`, `currency_symbol`, `shipping_fee`, `estimated_days`, `is_active`) VALUES
('Cameroon', 'CM', 'XAF', 'XAF', 0, 3, 1),
('Nigeria', 'NG', 'NGN', '₦', 8000, 5, 1),
('Ghana', 'GH', 'GHS', 'GHS', 35, 5, 1),
('Kenya', 'KE', 'KES', 'KES', 1500, 7, 1),
('South Africa', 'ZA', 'ZAR', 'R', 250, 7, 1),
('France', 'FR', 'EUR', '€', 45, 10, 1),
('United Kingdom', 'GB', 'GBP', '£', 35, 10, 1),
('United States', 'US', 'USD', '$', 50, 10, 1),
('Canada', 'CA', 'CAD', 'C$', 45, 10, 1),
('Australia', 'AU', 'AUD', 'A$', 60, 14, 1),
('India', 'IN', 'INR', '₹', 800, 14, 1),
('China', 'CN', 'CNY', '¥', 600, 14, 1),
('Japan', 'JP', 'JPY', '¥', 3000, 12, 1),
('Brazil', 'BR', 'BRL', 'R$', 120, 14, 1),
('Mexico', 'MX', 'MXN', '$', 800, 10, 1),
('Singapore', 'SG', 'SGD', 'S$', 35, 7, 1),
('Malaysia', 'MY', 'MYR', 'RM', 40, 7, 1),
('UAE', 'AE', 'AED', 'د.إ', 80, 7, 1),
('Saudi Arabia', 'SA', 'SAR', 'ر.س', 180, 10, 1),
('Egypt', 'EG', 'EGP', 'E£', 400, 7, 1)
ON DUPLICATE KEY UPDATE is_active=1;
