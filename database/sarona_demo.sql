-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2026 at 01:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.5.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sarona`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounting_category`
--

CREATE TABLE `accounting_category` (
  `id` bigint(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `id_type` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounting_category`
--

INSERT INTO `accounting_category` (`id`, `name`, `id_type`) VALUES
(1, 'Other income', 1),
(2, 'Operating supplies', 2),
(3, 'Equipment maintenance', 2);

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_transactions`
--

CREATE TABLE `cash_transactions` (
  `id` bigint(20) NOT NULL,
  `category_id` bigint(20) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `purchase_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `note` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_transactions`
--

INSERT INTO `cash_transactions` (`id`, `category_id`, `amount`, `transaction_date`, `order_id`, `purchase_id`, `user_id`, `note`) VALUES
(1, 1, 35.00, '2026-10-29 10:00:00', NULL, NULL, 10, 'Small catering order'),
(2, 2, 3.60, '2026-10-29 13:15:00', NULL, 14, 10, 'Latte ingredients replenishment'),
(3, 2, 12.00, '2026-10-30 16:40:00', NULL, NULL, 10, 'Cups and takeaway packaging'),
(4, 3, 15.00, '2026-10-31 11:20:00', NULL, NULL, 10, 'Coffee machine maintenance'),
(5, 2, 11.00, '2026-10-29 21:52:35', NULL, 17, 10, 'Purchase #17');

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `category_id` bigint(20) NOT NULL,
  `category_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`category_id`, `category_name`, `description`) VALUES
(4, 'Coffee & Espresso', 'Freshly prepared espresso-based drinks.'),
(5, 'Tea & Matcha', 'Tea, matcha, and other warm refreshments.'),
(6, 'Cold Drinks', 'Chilled drinks, refreshers, and smoothies.'),
(7, 'Kitchen & Snacks', 'Fresh meals and light bites.');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customer_id` bigint(20) NOT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customer_id`, `user_id`, `name`, `phone`) VALUES
(2, NULL, 'Sokha Dara', '010200301'),
(3, NULL, 'Malis Chan', '012345678'),
(4, NULL, 'Vicheka Sok', '0961234567');

-- --------------------------------------------------------

--
-- Table structure for table `delivery`
--

CREATE TABLE `delivery` (
  `delivery_id` bigint(20) NOT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo records

INSERT INTO `delivery` (`delivery_id`, `order_id`, `address`, `cost`, `status`) VALUES
(1, 4, 'Boeung Keng Kang 1, Phnom Penh', 1.50, 'delivered');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `in_and_exp_types`
--

CREATE TABLE `in_and_exp_types` (
  `id_type` bigint(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `in_and_exp_types`
--

INSERT INTO `in_and_exp_types` (`id_type`, `name`) VALUES
(1, 'Income'),
(2, 'Expense');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

CREATE TABLE `offers` (
  `offer_id` bigint(20) NOT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `new_price` decimal(10,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo records

INSERT INTO `offers` (`offer_id`, `product_id`, `discount`, `new_price`, `start_date`, `end_date`) VALUES
(1, 7, 13.33, 3.25, '2026-10-25 00:00:00', '2026-11-15 23:59:59');

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `option_id` bigint(20) NOT NULL,
  `category_id` bigint(20) DEFAULT NULL,
  `option_name` varchar(255) DEFAULT NULL,
  `value_name` varchar(255) DEFAULT NULL,
  `extra_price` decimal(10,2) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` bigint(20) NOT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
  `cashier_id` bigint(20) DEFAULT NULL,
  `order_type_id` bigint(20) DEFAULT NULL,
  `waiting_num` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `discount` decimal(10,2) DEFAULT NULL,
  `final_price` decimal(10,2) DEFAULT NULL,
  `exchange_rate` decimal(10,4) DEFAULT NULL,
  `payment_method_id` bigint(20) DEFAULT NULL,
  `is_paid` tinyint(1) DEFAULT 0,
  `payment_status` varchar(50) DEFAULT NULL,
  `order_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `customer_id`, `cashier_id`, `order_type_id`, `waiting_num`, `total_amount`, `discount`, `final_price`, `exchange_rate`, `payment_method_id`, `is_paid`, `payment_status`, `order_date`) VALUES
(2, 2, 10, 4, 1, 8.00, 0.00, 8.00, 4100.0000, 1, 1, 'Paid', '2026-10-29 09:12:00'),
(3, 3, 10, 5, 1, 11.00, 5.00, 10.45, 4100.0000, 2, 1, 'Paid', '2026-10-30 10:35:00'),
(4, 4, 10, 6, 1, 7.50, 0.00, 7.50, 4100.0000, 2, 1, 'Paid', '2026-10-31 14:05:00'),
(6, 2, 10, 4, 1, 3.00, 0.00, 3.00, 4100.0000, 1, 1, 'Paid', '2026-11-01 08:46:00');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `before_order_insert` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    -- គណនាតម្លៃចុងក្រោយដោយស្វ័យប្រវត្តិ មុនពេលរក្សាទុកទិន្នន័យ
    SET NEW.final_price = ROUND(COALESCE(NEW.total_amount, 0) * (1 - (COALESCE(NEW.discount, 0) / 100)), 2);
    IF NEW.is_paid = TRUE THEN
        SET NEW.payment_status = 'Paid';
    ELSE
        SET NEW.payment_status = 'Unpaid';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_order_update` BEFORE UPDATE ON `orders` FOR EACH ROW BEGIN
    -- ពិនិត្យមើលស្ថានភាពនៃការបង់ប្រាក់
    -- ប្រសិនបើ is_paid ជា True នោះ payment_status នឹងទៅជា 'Paid'
    IF NEW.is_paid = TRUE THEN
        SET NEW.payment_status = 'Paid';
    ELSE
        SET NEW.payment_status = 'Unpaid';
    END IF;

    -- Discount is stored as a percentage, matching the application form.
    SET NEW.final_price = ROUND(COALESCE(NEW.total_amount, 0) * (1 - (COALESCE(NEW.discount, 0) / 100)), 2);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_BeforeInsertOrderDaily` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    DECLARE max_num INT;
    
    -- ស្វែងរកលេខ waiting_num ធំបំផុតនៅក្នុងថ្ងៃនេះ
    SELECT COALESCE(MAX(waiting_num), 0) INTO max_num 
    FROM orders 
    WHERE DATE(order_date) = CURDATE();
    
    -- កំណត់លេខថ្មី (+1) ឱ្យទៅវិក្កយបត្រដែលហៀបនឹងចូល
    SET NEW.waiting_num = max_num + 1;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` bigint(20) NOT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(9, 2, 5, 2, 3.25),
(28, 2, 6, 1, 1.50),
(29, 3, 7, 2, 3.75),
(31, 3, 8, 1, 3.50),
(32, 4, 9, 1, 3.00),
(33, 4, 10, 1, 4.50),
(34, 6, 6, 2, 1.50);

--
-- Triggers `order_items`
--
DELIMITER $$
CREATE TRIGGER `after_order_item_insert_final` AFTER INSERT ON `order_items` FOR EACH ROW BEGIN
    DECLARE current_qty INT;
    DECLARE current_cashier_id BIGINT;
    DECLARE qty_diff INT;

    -- ១. ទាញយកចំនួនស្តុកបច្ចុប្បន្នរបស់មុខទំនិញ
    SELECT qty INTO current_qty FROM product WHERE product_id = NEW.product_id;

    -- ២. ស្វែងរកមើល Cashier ID ពីតុ orders មកប្រើប្រាស់
    SELECT cashier_id INTO current_cashier_id FROM orders WHERE order_id = NEW.order_id;

    -- ៣. ឆែកលក្ខខណ្ឌបើដាច់ស្តុក មិនឱ្យលក់
    IF NEW.quantity > current_qty THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: ចំនួនទំនិញក្នុងស្តុកមិនគ្រប់គ្រាន់សម្រាប់ការលក់ទេ!';
    ELSE
        -- ៤. បើស្តុកគ្រប់គ្រាន់ ធ្វើការដកស្តុកនៅក្នុងតុ product
        UPDATE product
        SET qty = qty - NEW.quantity
        WHERE product_id = NEW.product_id;

        -- ៥. កត់ត្រាចូល stock_logs (ជាមួយលក្ខខណ្ឌការពារ FK: បើរកមិនឃើញ Cashier ក្នុងតុ users ឱ្យប្រើតម្លៃ NULL)
        IF current_cashier_id IS NOT NULL AND EXISTS (SELECT 1 FROM users WHERE user_id = current_cashier_id) THEN
            INSERT INTO stock_logs (product_id, user_id, type, quantity, reason, note)
            VALUES (NEW.product_id, current_cashier_id, 'OUT', NEW.quantity, 'Sale', CONCAT('លក់ចេញតាមវិក្កយបត្រលេខ #', NEW.order_id));
        ELSE
            INSERT INTO stock_logs (product_id, user_id, type, quantity, reason, note)
            VALUES (NEW.product_id, NULL, 'OUT', NEW.quantity, 'Sale', CONCAT('លក់ចេញតាមវិក្កយបត្រលេខ #', NEW.order_id));
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_item_options`
--

CREATE TABLE `order_item_options` (
  `id` bigint(20) NOT NULL,
  `order_item_id` bigint(20) DEFAULT NULL,
  `option_id` bigint(20) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_type`
--

CREATE TABLE `order_type` (
  `order_type_id` bigint(20) NOT NULL,
  `order_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_type`
--

INSERT INTO `order_type` (`order_type_id`, `order_name`) VALUES
(4, 'Dine-in'),
(5, 'Takeaway'),
(6, 'Delivery');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_method`
--

CREATE TABLE `payment_method` (
  `payment_method_id` bigint(20) NOT NULL,
  `payment_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo records

INSERT INTO `payment_method` (`payment_method_id`, `payment_name`) VALUES
(1, 'Cash'),
(2, 'KHQR'),
(3, 'Card');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `product_id` bigint(20) NOT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `category_id` bigint(20) DEFAULT NULL,
  `supplier_id` bigint(20) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `qty` int(11) DEFAULT NULL,
  `product_type` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`product_id`, `product_name`, `category_id`, `supplier_id`, `price`, `qty`, `product_type`, `image`, `description`, `status`, `cost`) VALUES
(5, 'Classic Latte', 4, 3, 3.25, 26, 'Drink', '1785034661.jpg', 'Espresso with smooth steamed milk.', 'Active', 1.20),
(6, 'Coca-Cola', 6, 3, 1.50, 37, 'Drink', '1785160636.jpg', 'Chilled classic cola.', 'Active', 0.65),
(7, 'Matcha Latte', 5, 3, 3.75, 20, 'Drink', '1785034842.jpg', 'Creamy matcha with a delicate green tea finish.', 'Active', 1.10),
(8, 'Pink Coconut Mojito', 6, 3, 3.50, 18, 'Drink', '1781966137.jpg', 'Refreshing coconut and mint cooler.', 'Active', 1),
(9, 'Banana Smoothie', 6, 3, 3.00, 24, 'Drink', '1787373241.png', 'A chilled banana smoothie.', 'Active', 1),
(10, 'Chicken Fried Rice', 7, 3, 4.50, 12, 'Food', '1786852810.jpg', 'Wok-fried rice with vegetables and chicken.', 'Active', 2.00);

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `purchase_id` bigint(20) NOT NULL,
  `supplier_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`purchase_id`, `supplier_id`, `user_id`, `product_id`, `quantity`, `cost_price`, `purchase_date`) VALUES
(14, 3, 10, 5, 3, 1.20, '2026-10-29'),
(17, 3, 10, 7, 10, 1.10, '2026-10-29');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` bigint(20) NOT NULL,
  `order_id` bigint(20) DEFAULT NULL,
  `customer_id` bigint(20) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demo records

INSERT INTO `reviews` (`review_id`, `order_id`, `customer_id`, `rating`, `comment`) VALUES
(1, 2, 2, 5, 'Great latte and friendly service.'),
(2, 3, 3, 4, 'The matcha was smooth and refreshing.'),
(3, 4, 4, 5, 'Quick delivery and delicious food.');

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `role_id` bigint(20) NOT NULL,
  `role_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`role_id`, `role_name`) VALUES
(4, 'cashier'),
(5, 'admin'),
(6, 'customer');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--


-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint(20) NOT NULL,
  `shop_name` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `exchange_rate` decimal(10,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `shop_name`, `logo`, `address`, `tel`, `exchange_rate`) VALUES
(1, 'Coffee Corner', NULL, 'Street 271, Phnom Penh, Cambodia', '010123456', 4100.0000);

-- --------------------------------------------------------

--
-- Table structure for table `stock_logs`
--

CREATE TABLE `stock_logs` (
  `log_id` bigint(20) NOT NULL,
  `product_id` bigint(20) DEFAULT NULL,
  `user_id` bigint(20) DEFAULT NULL,
  `log_date` datetime NOT NULL DEFAULT current_timestamp(),
  `type` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_logs`
--

INSERT INTO `stock_logs` (`log_id`, `product_id`, `user_id`, `log_date`, `type`, `quantity`, `reason`, `note`, `updated_at`, `created_at`) VALUES
(1, 5, 10, '2026-10-20 08:00:00', 'IN', 25, 'Opening stock', 'Initial inventory count', NULL, NULL),
(2, 5, 10, '2026-10-29 09:00:00', 'IN', 3, 'Purchase', 'Restock from purchase #14', NULL, NULL),
(3, 5, 10, '2026-10-29 09:12:00', 'OUT', 2, 'Sale', 'Order #2', NULL, NULL),
(4, 6, 10, '2026-10-20 08:00:00', 'IN', 40, 'Opening stock', 'Initial inventory count', NULL, NULL),
(5, 6, 10, '2026-10-29 09:12:00', 'OUT', 1, 'Sale', 'Order #2', NULL, NULL),
(6, 6, 10, '2026-11-01 08:46:00', 'OUT', 2, 'Sale', 'Order #6', NULL, NULL),
(7, 7, 10, '2026-10-20 08:00:00', 'IN', 12, 'Opening stock', 'Initial inventory count', NULL, NULL),
(8, 7, 10, '2026-10-29 10:00:00', 'IN', 10, 'Purchase', 'Restock from purchase #17', NULL, NULL),
(9, 7, 10, '2026-10-30 10:35:00', 'OUT', 2, 'Sale', 'Order #3', NULL, NULL),
(10, 8, 10, '2026-10-20 08:00:00', 'IN', 19, 'Opening stock', 'Initial inventory count', NULL, NULL),
(11, 8, 10, '2026-10-30 10:35:00', 'OUT', 1, 'Sale', 'Order #3', NULL, NULL),
(12, 9, 10, '2026-10-20 08:00:00', 'IN', 25, 'Opening stock', 'Initial inventory count', NULL, NULL),
(13, 9, 10, '2026-10-31 14:05:00', 'OUT', 1, 'Sale', 'Order #4', NULL, NULL),
(14, 10, 10, '2026-10-20 08:00:00', 'IN', 13, 'Opening stock', 'Initial inventory count', NULL, NULL),
(15, 10, 10, '2026-10-31 14:05:00', 'OUT', 1, 'Sale', 'Order #4', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` bigint(20) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`supplier_id`, `name`, `phone`, `email`, `address`) VALUES
(3, 'Lotus Beverage Supply', '010555123', 'sales@lotus-supply.example.test', 'Phnom Penh, Cambodia');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint(20) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `username` varchar(200) NOT NULL,
  `role_id` bigint(20) NOT NULL,
  `status` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`, `phone`, `username`, `role_id`, `status`) VALUES
(10, 'Demo Cashier', 'cashier@example.test', NULL, '$2y$12$NWQ8gFSnMu7ZqlO9KQVaHu17uP9v7pJqoxLY5jqqupVyaIEhf0VA.', NULL, NULL, NULL, '0100000000', 'demo.cashier', 4, 'active'),
(11, 'Demo Administrator', 'demo.admin@example.test', NULL, '$2y$12$DIodSHRnhrpxBShhMldsJ.M6cCtsLT9hG4XXnFRV3E3KvaM6ruse2', NULL, NULL, NULL, '0120000000', 'demo.admin', 5, 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounting_category`
--
ALTER TABLE `accounting_category`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_accounting_type` (`id_type`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cash_category` (`category_id`),
  ADD KEY `fk_cash_order` (`order_id`),
  ADD KEY `fk_cash_purchase` (`purchase_id`),
  ADD KEY `fk_cash_user` (`user_id`);

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customer_id`),
  ADD KEY `fk_customer_user` (`user_id`);

--
-- Indexes for table `delivery`
--
ALTER TABLE `delivery`
  ADD PRIMARY KEY (`delivery_id`),
  ADD UNIQUE KEY `order_id` (`order_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indexes for table `in_and_exp_types`
--
ALTER TABLE `in_and_exp_types`
  ADD PRIMARY KEY (`id_type`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `fk_offers_product` (`product_id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`option_id`),
  ADD KEY `fk_options_category` (`category_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_orders_customer` (`customer_id`),
  ADD KEY `fk_orders_cashier` (`cashier_id`),
  ADD KEY `fk_orders_type` (`order_type_id`),
  ADD KEY `fk_orders_payment` (`payment_method_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `fk_order_items_order` (`order_id`),
  ADD KEY `fk_order_items_product` (`product_id`);

--
-- Indexes for table `order_item_options`
--
ALTER TABLE `order_item_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_oio_orderitem` (`order_item_id`),
  ADD KEY `fk_oio_option` (`option_id`);

--
-- Indexes for table `order_type`
--
ALTER TABLE `order_type`
  ADD PRIMARY KEY (`order_type_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payment_method`
--
ALTER TABLE `payment_method`
  ADD PRIMARY KEY (`payment_method_id`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `fk_product_category` (`category_id`),
  ADD KEY `fk_product_supplier` (`supplier_id`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`purchase_id`),
  ADD KEY `fk_purchase_supplier` (`supplier_id`),
  ADD KEY `fk_purchase_user` (`user_id`),
  ADD KEY `fk_purchase_product` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `fk_reviews_order` (`order_id`),
  ADD KEY `fk_reviews_customer` (`customer_id`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_stock_product` (`product_id`),
  ADD KEY `fk_stock_user` (`user_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD KEY `idx_users_role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounting_category`
--
ALTER TABLE `accounting_category`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `category_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customer_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `delivery`
--
ALTER TABLE `delivery`
  MODIFY `delivery_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `in_and_exp_types`
--
ALTER TABLE `in_and_exp_types`
  MODIFY `id_type` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `offer_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `option_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `order_item_options`
--
ALTER TABLE `order_item_options`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_type`
--
ALTER TABLE `order_type`
  MODIFY `order_type_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payment_method`
--
ALTER TABLE `payment_method`
  MODIFY `payment_method_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `product_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `purchase_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `role_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `stock_logs`
--
ALTER TABLE `stock_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounting_category`
--
ALTER TABLE `accounting_category`
  ADD CONSTRAINT `fk_accounting_type` FOREIGN KEY (`id_type`) REFERENCES `in_and_exp_types` (`id_type`);

--
-- Constraints for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  ADD CONSTRAINT `cash_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cash_category` FOREIGN KEY (`category_id`) REFERENCES `accounting_category` (`id`),
  ADD CONSTRAINT `fk_cash_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `fk_cash_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`purchase_id`);

--
-- Constraints for table `customer`
--
ALTER TABLE `customer`
  ADD CONSTRAINT `customer_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery`
--
ALTER TABLE `delivery`
  ADD CONSTRAINT `fk_delivery_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `offers`
--
ALTER TABLE `offers`
  ADD CONSTRAINT `fk_offers_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `fk_options_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `fk_orders_payment` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`payment_method_id`),
  ADD CONSTRAINT `fk_orders_type` FOREIGN KEY (`order_type_id`) REFERENCES `order_type` (`order_type_id`),
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  ADD CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`);

--
-- Constraints for table `order_item_options`
--
ALTER TABLE `order_item_options`
  ADD CONSTRAINT `fk_oio_option` FOREIGN KEY (`option_id`) REFERENCES `options` (`option_id`),
  ADD CONSTRAINT `fk_oio_orderitem` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`order_item_id`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`),
  ADD CONSTRAINT `fk_product_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`);

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchase_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `fk_purchase_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`customer_id`),
  ADD CONSTRAINT `fk_reviews_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `stock_logs`
--
ALTER TABLE `stock_logs`
  ADD CONSTRAINT `fk_stock_product` FOREIGN KEY (`product_id`) REFERENCES `product` (`product_id`),
  ADD CONSTRAINT `stock_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
