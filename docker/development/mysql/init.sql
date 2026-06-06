CREATE DATABASE IF NOT EXISTS `proposalkit_testing`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

GRANT ALL PRIVILEGES ON `proposalkit_testing`.* TO 'laravel'@'%';

FLUSH PRIVILEGES;
