
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin','user') DEFAULT 'user'
);

INSERT INTO users (nama, username, password, role)
VALUES ('Administrator','admin','$2b$12$dRZBwPClrOqxZTYUYhVERORUSc5VtbcNDY/AcpuNo5suG9soTbJ1G','admin');
