CREATE DATABASE IF NOT EXISTS college_events CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE college_events;

CREATE TABLE departments (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 department_id INT NULL,
 role ENUM('student','admin') NOT NULL DEFAULT 'student',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_user_department FOREIGN KEY(department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE events (
 id INT AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 description TEXT NOT NULL,
 event_date DATE NOT NULL,
 start_time TIME NOT NULL,
 venue VARCHAR(180) NOT NULL,
 department_id INT NOT NULL,
 fee DECIMAL(10,2) NOT NULL DEFAULT 0,
 created_by INT NOT NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_event_status_date (status, event_date, start_time),
 INDEX idx_event_department_date (department_id, event_date),
 CONSTRAINT fk_event_department FOREIGN KEY(department_id) REFERENCES departments(id),
 CONSTRAINT fk_event_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE event_registrations (
 id INT AUTO_INCREMENT PRIMARY KEY,
 event_id INT NOT NULL,
 user_id INT NOT NULL,
 pass_token CHAR(48) NOT NULL UNIQUE,
 payment_reference VARCHAR(100) NULL,
 payment_status ENUM('not_required','pending','verified','rejected') NOT NULL DEFAULT 'not_required',
 attendance_status ENUM('registered','attended') NOT NULL DEFAULT 'registered',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_event_user(event_id,user_id),
 INDEX idx_registration_user_created (user_id, created_at),
 INDEX idx_registration_payment (payment_status),
 CONSTRAINT fk_reg_event FOREIGN KEY(event_id) REFERENCES events(id) ON DELETE CASCADE,
 CONSTRAINT fk_reg_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE notifications (
 id INT AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL,
 subject VARCHAR(255) NOT NULL,
 body TEXT NOT NULL,
 sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO departments(name) VALUES
('Computer Science'),('Information Technology'),('Mechanical Engineering'),
('Electrical Engineering'),('Civil Engineering'),('Commerce'),('Arts and Humanities');
