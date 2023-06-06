CREATE database gate_pass;

use gate_pass;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_cpfno ON employee(cpfno);

ALTER TABLE users
ADD CONSTRAINT fk_employee_cpfno FOREIGN KEY (cpfno)
REFERENCES employee(cpfno);
