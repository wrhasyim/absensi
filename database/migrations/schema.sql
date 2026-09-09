-- Sistem Absensi Sekolah - Database Schema
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS whatsapp_logs;
DROP TABLE IF EXISTS whatsapp_queue;
DROP TABLE IF EXISTS whatsapp_templates;
DROP TABLE IF EXISTS attendance_corrections;
DROP TABLE IF EXISTS attendances;
DROP TABLE IF EXISTS fingerprint_logs;
DROP TABLE IF EXISTS holidays;
DROP TABLE IF EXISTS devices;
DROP TABLE IF EXISTS student_parents;
DROP TABLE IF EXISTS parents;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS majors;
DROP TABLE IF EXISTS teachers;
DROP TABLE IF EXISTS school_settings;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  role ENUM('super_admin','admin','guru','kepala_sekolah') NOT NULL DEFAULT 'admin',
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE majors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE classes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  major_id INT,
  name VARCHAR(50) NOT NULL,
  grade VARCHAR(10),
  homeroom_teacher_id INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nip VARCHAR(30) UNIQUE,
  name VARCHAR(100) NOT NULL,
  gender ENUM('L','P'),
  phone VARCHAR(20),
  whatsapp VARCHAR(20),
  email VARCHAR(100),
  address TEXT,
  user_id INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE parents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  father_name VARCHAR(100),
  father_whatsapp VARCHAR(20),
  mother_name VARCHAR(100),
  mother_whatsapp VARCHAR(20),
  guardian_name VARCHAR(100),
  guardian_whatsapp VARCHAR(20),
  primary_whatsapp VARCHAR(20) NOT NULL,
  relation VARCHAR(50) DEFAULT 'Orang Tua',
  wa_active TINYINT(1) DEFAULT 1,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nis VARCHAR(30) UNIQUE NOT NULL,
  nisn VARCHAR(30),
  name VARCHAR(100) NOT NULL,
  nickname VARCHAR(50),
  gender ENUM('L','P') DEFAULT 'L',
  birth_place VARCHAR(100),
  birth_date DATE,
  address TEXT,
  phone VARCHAR(20),
  whatsapp VARCHAR(20),
  class_id INT,
  major_id INT,
  entry_year YEAR,
  status ENUM('aktif','pindah','lulus','keluar') DEFAULT 'aktif',
  fingerprint_id VARCHAR(20) UNIQUE,
  photo VARCHAR(255),
  parent_id INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_student_nis (nis),
  INDEX idx_student_fp (fingerprint_id),
  INDEX idx_student_class (class_id),
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
  FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE SET NULL,
  FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE student_parents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  parent_id INT NOT NULL,
  UNIQUE KEY uk_sp (student_id, parent_id),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES parents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE devices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  port INT DEFAULT 4370,
  comm_key VARCHAR(50) DEFAULT '0',
  location VARCHAR(100),
  adapter VARCHAR(50) DEFAULT 'mock',
  status ENUM('online','offline','unknown') DEFAULT 'unknown',
  last_connection DATETIME,
  last_sync DATETIME,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE fingerprint_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  device_id INT,
  fingerprint_id VARCHAR(20) NOT NULL,
  student_id INT,
  log_date DATE NOT NULL,
  log_time TIME NOT NULL,
  log_datetime DATETIME NOT NULL,
  transaction_type ENUM('in','out','unknown') DEFAULT 'in',
  status VARCHAR(20),
  raw_data TEXT,
  synced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_fp_log (device_id, fingerprint_id, log_datetime),
  INDEX idx_fp_date (log_date),
  INDEX idx_fp_student (student_id),
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE attendances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  class_id INT,
  attendance_date DATE NOT NULL,
  time_in TIME,
  time_out TIME,
  status ENUM('hadir','terlambat','izin','sakit','alpa','dispensasi','belum_absen') NOT NULL DEFAULT 'belum_absen',
  device_id INT,
  note TEXT,
  attachment VARCHAR(255),
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_att (student_id, attendance_date),
  INDEX idx_att_date (attendance_date),
  INDEX idx_att_status (status),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE attendance_corrections (
  id INT AUTO_INCREMENT PRIMARY KEY,
  attendance_id INT NOT NULL,
  old_status VARCHAR(50),
  new_status VARCHAR(50),
  reason TEXT,
  corrected_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (attendance_id) REFERENCES attendances(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE holidays (
  id INT AUTO_INCREMENT PRIMARY KEY,
  holiday_date DATE NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE school_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) UNIQUE NOT NULL,
  setting_value TEXT,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE whatsapp_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE NOT NULL,
  name VARCHAR(100) NOT NULL,
  content TEXT NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE whatsapp_queue (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  phone VARCHAR(20) NOT NULL,
  message TEXT NOT NULL,
  template_code VARCHAR(50),
  status ENUM('pending','processing','sent','failed','retry') DEFAULT 'pending',
  attempt INT DEFAULT 0,
  max_attempt INT DEFAULT 3,
  last_error TEXT,
  scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_wa_status (status),
  INDEX idx_wa_sched (scheduled_at)
) ENGINE=InnoDB;

CREATE TABLE whatsapp_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  queue_id INT,
  phone VARCHAR(20),
  message TEXT,
  status VARCHAR(20),
  response TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_wal_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  activity VARCHAR(200) NOT NULL,
  detail TEXT,
  ip_address VARCHAR(45),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_al_created (created_at)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS=1;
