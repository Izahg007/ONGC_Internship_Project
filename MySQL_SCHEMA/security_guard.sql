CREATE TABLE security_guard (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  guard_name VARCHAR(200) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  phone_no VARCHAR(10) NOT NULL,
  venue ENUM('N', 'V', 'H'),
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE security_guard AUTO_INCREMENT = 1;