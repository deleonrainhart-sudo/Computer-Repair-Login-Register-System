-- Create and select the database
CREATE DATABASE IF NOT EXISTS repairshop_db;
USE repairshop_db;

-- PARENT TABLE: one customer can have many devices
CREATE TABLE IF NOT EXISTS customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    date_of_birth VARCHAR(50),
    contact_number VARCHAR(50),
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CHILDREN TABLE: customer_id links each device to its customer
CREATE TABLE IF NOT EXISTS devices (
    device_id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(50),
    issue VARCHAR(100),
    customer_id INT,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
