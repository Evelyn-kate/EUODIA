-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 15, 2026 at 07:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `euodia_scents`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Signature Collection'),
(2, 'Travel Decants'),
(3, 'Retails'),
(4, 'Vintage Elegance'),
(5, 'Amber Essence'),
(6, 'Rose Gold Edition'),
(7, 'Frosted Luxury');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `created_at`) VALUES
(1, 3, 104000, '2026-04-08 11:15:26'),
(2, 3, 104000, '2026-04-08 11:15:34'),
(3, 3, 104000, '2026-04-08 11:15:36'),
(4, 3, 104000, '2026-04-08 11:15:36'),
(5, 3, 104000, '2026-04-08 11:15:36'),
(6, 3, 104000, '2026-04-08 11:16:38'),
(7, 3, 104000, '2026-04-08 11:16:50'),
(8, 3, 15000, '2026-04-08 11:23:16'),
(9, 3, 30000, '2026-04-08 11:24:06'),
(10, 3, 75000, '2026-04-08 11:25:00'),
(11, 3, 75000, '2026-04-08 11:54:44'),
(12, 3, 75000, '2026-04-14 13:56:26'),
(13, 3, 15000, '2026-04-15 16:04:03');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category_id`) VALUES
(1, 'Rose Oud 100ml', 'The essence of timeless sophistication — crafted for those who leave a lasting presence with every breath', 8000, './images/rose.png', 1),
(2, 'Lavender Rose 30ml', 'The essence of timeless sophistication — crafted for those who leave a lasting presence with every breath', 9000, './images/img 7.png', 1),
(3, 'Amber Oud pink essence 30ml', 'Bold and magnetic — a deep, warm fragrance housed in rich amber glass that whispers mystery and confidence', 15000, './images/img 6.png', 5),
(4, 'Dream Encounter 50ml ', 'Cool sophistication — frosted glass encloses gentle aromas that feel as calm as morning light', 15000, './images/im1.png', 7),
(5, 'Sugar Pink \r\nPeace essence 10ml', 'Elegance in motion. Your favorite Euodia scents, perfectly measured for life on the go', 2500, './images/im2.png', 2),
(7, 'Hayaati Rose', NULL, 15000, './images/WhatsApp Image 2025-06-29 at 1.47.01 PM.jpeg', 3),
(8, 'Amraf ', 'club de nuit', 18000, './images/WhatsApp Image 2025-06-29 at 1.47.02 PM.jpeg', 3),
(9, 'Saga Pink', 'Ember Eau de Parfum', 10000, './images/WhatsApp Image 2025-06-29 at 1.55.17 PM.jpeg', 3),
(10, 'Now Women', 'Pink Essence', 8000, './images/WhatsApp Image 2025-06-29 at 1.55.22 PM.jpeg', 3),
(11, 'Ramz Lattafa', NULL, 15000, './images/WhatsApp Image 2025-06-29 at 3.35.58 PM (1).jpeg', 3),
(12, 'Genuine Man Only', 'Eau Bois Pour Homme', 25000, './images/WhatsApp Image 2025-06-29 at 3.35.59 PM.jpeg', 3),
(13, 'Oud For Glory \r\nLattafa', NULL, 18000, './images/WhatsApp Image 2025-06-29 at 3.36.00 PM (1).jpeg', 3),
(14, 'RA\'ED LUXE', NULL, 12000, './images/WhatsApp Image 2025-06-29 at 3.36.00 PM.jpeg', 3);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key_name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key_name`, `value`) VALUES
(1, 'whitelist_enabled', '0');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `role` varchar(20) DEFAULT 'customer',
  `mfa_secret` varchar(32) DEFAULT NULL,
  `mfa_enabled` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `is_admin`, `role`, `mfa_secret`, `mfa_enabled`) VALUES
(1, 'Admin', 'admin@euodia.com', '0192023a7bbd73250516f069df18b500', 1, 'admin', NULL, 0),
(2, 'Eva Kate', 'evelynakate@gmail.com', 'a8215ff3148f54a4401b14812fedb36b', 0, 'customer', NULL, 0),
(3, 'Ndifon Durane', 'duranendifon@gmail.com', '5f4dcc3b5aa765d61d8327deb882cf99', 0, 'customer', NULL, 0),
(4, 'Kate Evelyne', 'evelynkate2@gmail.com', '9048387a5d1183a1afd81f0099754c5f', 0, 'customer', NULL, 0),
(5, 'Kang Modest', 'Destinho@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$OG9iaGVOSkJhT3lQYTdXeQ$6dZKbLAHDXo1EEryh5sB9LqVP7ufi28Hssp04Ko8PSw', 0, 'customer', NULL, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
