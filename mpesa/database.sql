CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    member_type ENUM('student','non_student') NOT NULL,
    institution VARCHAR(150),
    course VARCHAR(150),
    county VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    merchant_request_id VARCHAR(100),
    checkout_request_id VARCHAR(100),
    mpesa_receipt VARCHAR(100),
    payment_status ENUM('PENDING','PAID','FAILED') DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);