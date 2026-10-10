-- Minimal users table matching models/UsersModel.php (test fixture only)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(190) NOT NULL DEFAULT '',
  `login` VARCHAR(190) NOT NULL DEFAULT '',
  `password` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(190) NOT NULL,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `address` VARCHAR(255) NOT NULL DEFAULT '',
  `phone` VARCHAR(50) NOT NULL DEFAULT '',
  `gender` VARCHAR(20) NOT NULL DEFAULT '',
  `role` TINYINT NOT NULL DEFAULT 0,
  `status` TINYINT NOT NULL DEFAULT 2,
  `is_verified` TINYINT NOT NULL DEFAULT 0,
  `verify_type` TINYINT NOT NULL DEFAULT 0,
  `token` VARCHAR(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
