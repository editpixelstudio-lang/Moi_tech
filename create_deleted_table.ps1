
$sql = @"
CREATE TABLE IF NOT EXISTS deleted_collections (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    function_id INT,
    user_id INT,
    computer_number VARCHAR(50),
    location VARCHAR(255),
    initial_name VARCHAR(50),
    name1 VARCHAR(100),
    name2 VARCHAR(100),
    occupation VARCHAR(100),
    relationship_priority TINYINT,
    village_going_to VARCHAR(255),
    phone VARCHAR(20),
    customer_number VARCHAR(50),
    description TEXT,
    total_amount DECIMAL(10,2),
    denom_2000 INT,
    denom_500 INT,
    denom_200 INT,
    denom_100 INT,
    denom_50 INT,
    denom_20 INT,
    denom_10 INT,
    denom_5 INT,
    denom_2 INT,
    denom_1 INT,
    collection_date DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    updated_by INT,
    uuid CHAR(36),
    is_synced TINYINT(1),
    remote_id INT,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_by INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
"@

$cmd = "C:\wamp64\bin\mysql\mysql8.0.21\bin\mysql.exe -u root uzrs-moi-net -e ""$sql"""
Invoke-Expression $cmd
