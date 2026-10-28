-- Run as a database administrator before install-db.sh.
-- Change database name, username and password together with a2billing.conf.
-- This file targets MySQL 8.0 and 8.4 LTS.
-- Existing accounts and databases are not deleted.

CREATE DATABASE IF NOT EXISTS `mya2billing`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
CREATE USER IF NOT EXISTS 'a2billinguser'@'localhost' IDENTIFIED BY 'a2billing';
GRANT ALL PRIVILEGES ON `mya2billing`.* TO 'a2billinguser'@'localhost';
