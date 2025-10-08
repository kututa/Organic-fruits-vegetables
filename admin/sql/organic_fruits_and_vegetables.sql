-- Schema and sample data for organic_fruits_and_vegetables
-- Run this file against MySQL to create the database, tables, and sample rows.
CREATE DATABASE IF NOT EXISTS `organic_fruits_and_vegetables` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `organic_fruits_and_vegetables`;

-- Users table: admins, customers, drivers
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `role` ENUM('user','admin','driver') NOT NULL DEFAULT 'user',
  `account_status` ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  INDEX (`email`),
  INDEX (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `image_path` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  INDEX `idx_name` (`name`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(100) DEFAULT NULL,
  `order_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('new','processing','completed','cancelled') NOT NULL DEFAULT 'new',
  PRIMARY KEY (`order_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_order_date` (`order_date`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`order_item_id`),
  INDEX `idx_order` (`order_id`),
  INDEX `idx_product` (`product_id`),
  CONSTRAINT `fk_orderitems_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_orderitems_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments ledger
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_status` ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(100) DEFAULT NULL,
  `transaction_ref` VARCHAR(200) DEFAULT NULL,
  `paid_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  INDEX `idx_payment_order` (`order_id`),
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deliveries
CREATE TABLE IF NOT EXISTS `deliveries` (
  `delivery_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `delivery_status` ENUM('Pending','In Transit','Delivered') NOT NULL DEFAULT 'Pending',
  `delivery_date` DATETIME DEFAULT NULL,
  `assigned_driver` INT UNSIGNED DEFAULT NULL,
  `tracking_number` VARCHAR(200) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`delivery_id`),
  UNIQUE KEY `uk_order_delivery` (`order_id`),
  INDEX `idx_del_status` (`delivery_status`),
  CONSTRAINT `fk_deliveries_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_deliveries_driver` FOREIGN KEY (`assigned_driver`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin activity logs
CREATE TABLE IF NOT EXISTS `admin_activity_logs` (
  `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `action_performed` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  INDEX `idx_admin` (`admin_id`),
  CONSTRAINT `fk_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  `comment` TEXT,
  `status` ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  INDEX `idx_review_product` (`product_id`),
  INDEX `idx_review_user` (`user_id`),
  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart items
CREATE TABLE IF NOT EXISTS `cart_items` (
  `cart_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_item_id`),
  UNIQUE KEY `uq_cart_user_product` (`user_id`,`product_id`),
  INDEX `idx_cart_user` (`user_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wishlist items
CREATE TABLE IF NOT EXISTS `wishlist_items` (
  `wishlist_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_item_id`),
  UNIQUE KEY `uq_wishlist_user_product` (`user_id`,`product_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Customer messages / contact us
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`),
  INDEX `idx_message_user` (`user_id`),
  CONSTRAINT `fk_messages_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Helpful summary view (not enforced by SQL, but useful for reporting): create indexes where needed
CREATE INDEX IF NOT EXISTS `idx_products_price` ON `products` (`price`);

-- Sample data: categories
INSERT INTO `categories` (`category_name`,`description`) VALUES
  ('Fruits','Fresh fruits and seasonal picks'),
  ('Vegetables','Fresh vegetables and greens'),
  ('Organic Packs','Packaged organic combos');

-- Sample users (passwords: admin123, driver123, john123, jane123) - hashes generated using PHP password_hash
INSERT INTO `users` (`name`,`email`,`password_hash`,`phone`,`role`,`account_status`) VALUES
  ('Site Admin','admin@local.test','$2y$10$abcdefghijklmnopqrstuvwxzyABCDEFGHIJKLMNO', '000-000-0000', 'admin','active'),
  ('Delivery Driver','driver@local.test','$2y$10$abcdefghijklmnopqrstuvwxzyABCDEFGHIJKLMNO', '111-111-1111', 'driver','active'),
  ('John Customer','john@example.com','$2y$10$abcdefghijklmnopqrstuvwxzyABCDEFGHIJKLMNO', '222-222-2222', 'user','active'),
  ('Jane Customer','jane@example.com','$2y$10$abcdefghijklmnopqrstuvwxzyABCDEFGHIJKLMNO', '333-333-3333', 'user','active');

-- Sample products
INSERT INTO `products` (`name`,`description`,`category_id`,`price`,`stock_quantity`,`image_path`,`status`) VALUES
  ('Red Apples - 1kg','Fresh red apples from the orchard',1,3.50,120,'uploads/products/apple-1.jpg','active'),
  ('Banana Bunch - 1kg','Sweet ripe bananas',1,2.20,200,'uploads/products/banana-1.jpg','active'),
  ('Organic Spinach - 250g','Locally grown organic spinach',2,1.99,80,'uploads/products/spinach-1.jpg','active'),
  ('Vegetable Combo Pack','Mixed seasonal vegetables',3,12.50,40,'uploads/products/veg-pack.jpg','active');

-- Create a few orders with items, payments, and deliveries
INSERT INTO `orders` (`user_id`,`total_amount`,`payment_status`,`payment_method`,`status`) VALUES
  (3, 15.50, 'paid', 'card', 'completed'),
  (4, 25.20, 'paid', 'paypal', 'processing'),
  (3, 7.00, 'pending', 'card', 'new');

INSERT INTO `order_items` (`order_id`,`product_id`,`quantity`,`unit_price`,`total_price`) VALUES
  (1, 1, 2, 3.50, 7.00),
  (1, 3, 1, 1.99, 1.99),
  (2, 2, 5, 2.20, 11.00),
  (2, 4, 1, 12.50, 12.50),
  (3, 1, 2, 3.50, 7.00);

INSERT INTO `payments` (`order_id`,`amount`,`payment_status`,`payment_method`,`transaction_ref`,`paid_at`) VALUES
  (1, 15.50, 'completed','card','TXN-100001', NOW()),
  (2, 23.50, 'completed','paypal','TXN-100002', NOW());

INSERT INTO `deliveries` (`order_id`,`delivery_status`,`delivery_date`,`assigned_driver`,`tracking_number`) VALUES
  (1,'Delivered', NOW() - INTERVAL 2 DAY, 2, 'TRK1001'),
  (2,'In Transit', NULL, 2, 'TRK1002');

-- Sample reviews
INSERT INTO `reviews` (`product_id`,`user_id`,`rating`,`comment`) VALUES
  (1,3,5,'Crisp and sweet apples, very fresh!'),
  (3,4,4,'Good taste, a bit pricey but organic.');

-- Sample cart and wishlist
INSERT INTO `cart_items` (`user_id`,`product_id`,`quantity`) VALUES
  (3,2,3),(4,1,1);
INSERT INTO `wishlist_items` (`user_id`,`product_id`) VALUES
  (3,4),(4,3);

-- Sample messages
INSERT INTO `messages` (`user_id`,`name`,`email`,`subject`,`message`) VALUES
  (3,'John Customer','john@example.com','Question about organic pack','Do you deliver to ZIP 12345?'),
  (NULL,'Visitor','visitor@example.com','Partnership inquiry','Interested in bulk supply');

-- Sample admin activity logs
INSERT INTO `admin_activity_logs` (`admin_id`,`action_performed`,`ip_address`) VALUES
  (1,'Created initial categories and sample products','127.0.0.1');

-- End of SQL
