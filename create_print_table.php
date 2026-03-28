<?php
require_once '../config/database.php';

$conn = getDBConnection();

$sql = "CREATE TABLE IF NOT EXISTS collection_print_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_id INT NOT NULL,
    printer_name VARCHAR(255) DEFAULT 'RecieptPrinter',
    status ENUM('Pending', 'Printed', 'Failed') DEFAULT 'Pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (status)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'collection_print_queue' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

closeDBConnection($conn);
?>
