-- GeoAttend – Full schema + demo seed
-- Import via phpMyAdmin, or:  mysql -u root < attendance_system.sql

CREATE DATABASE IF NOT EXISTS attendance_system
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE attendance_system;

DROP TABLE IF EXISTS attendance;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS classrooms;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS teachers;

CREATE TABLE teachers (
  teacher_id   INT AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  department   VARCHAR(100),
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE classrooms (
  classroom_id INT AUTO_INCREMENT PRIMARY KEY,
  room_name    VARCHAR(50) NOT NULL,
  building     VARCHAR(100),
  latitude     DECIMAL(10,7) NOT NULL,
  longitude    DECIMAL(10,7) NOT NULL,
  radius_m     INT NOT NULL DEFAULT 30
) ENGINE=InnoDB;

CREATE TABLE subjects (
  subject_id   INT AUTO_INCREMENT PRIMARY KEY,
  subject_code VARCHAR(20) NOT NULL UNIQUE,
  subject_name VARCHAR(150) NOT NULL,
  teacher_id   INT NOT NULL,
  FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE students (
  student_id   INT AUTO_INCREMENT PRIMARY KEY,
  roll_no      VARCHAR(20) NOT NULL UNIQUE,
  name         VARCHAR(100) NOT NULL,
  email        VARCHAR(150) NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,
  branch       VARCHAR(50),
  semester     INT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE attendance_sessions (
  session_id     INT AUTO_INCREMENT PRIMARY KEY,
  teacher_id     INT NOT NULL,
  subject_id     INT NOT NULL,
  classroom_id   INT NOT NULL,
  start_time     DATETIME NOT NULL,
  end_time       DATETIME NOT NULL,
  status         ENUM('active','closed') NOT NULL DEFAULT 'active',
  FOREIGN KEY (teacher_id)   REFERENCES teachers(teacher_id),
  FOREIGN KEY (subject_id)   REFERENCES subjects(subject_id),
  FOREIGN KEY (classroom_id) REFERENCES classrooms(classroom_id)
) ENGINE=InnoDB;

CREATE TABLE attendance (
  attendance_id INT AUTO_INCREMENT PRIMARY KEY,
  session_id    INT NOT NULL,
  student_id    INT NOT NULL,
  marked_at     DATETIME NOT NULL,
  latitude      DECIMAL(10,7),
  longitude     DECIMAL(10,7),
  status        ENUM('present','absent') NOT NULL DEFAULT 'present',
  UNIQUE KEY uniq_session_student (session_id, student_id),
  FOREIGN KEY (session_id) REFERENCES attendance_sessions(session_id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Demo hashes are for "password123" but regenerate on your machine if login fails:
--   php -r "echo password_hash('password123', PASSWORD_DEFAULT), PHP_EOL;"
INSERT INTO teachers (name,email,password,department) VALUES
('Dr. Anita Rao','anita@geoattend.local',
 '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlBqk5s7bqf3.7lQ0aZk4mHm6mE7C.', 'CSE');

INSERT INTO classrooms (room_name,building,latitude,longitude,radius_m) VALUES
('CS-201','Block A', 12.9716000, 77.5946000, 30);

INSERT INTO subjects (subject_code,subject_name,teacher_id) VALUES
('CS501','Web Technologies',1);

INSERT INTO students (roll_no,name,email,password,branch,semester) VALUES
('1CS21001','Ravi Kumar','ravi@geoattend.local',
 '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlBqk5s7bqf3.7lQ0aZk4mHm6mE7C.', 'CSE', 5);
