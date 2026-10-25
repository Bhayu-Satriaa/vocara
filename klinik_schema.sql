-- =====================================================
-- Skema Database Sistem Klinik Kesehatan (MySQL / InnoDB)
-- Fitur: multi-role, QR pasien, antrean, rekam medis (SOAP),
--        resep & farmasi, kasir, chatbot AI, AI speech
-- =====================================================

CREATE DATABASE IF NOT EXISTS klinik_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE klinik_db;

-- -----------------------------------------------------
-- 1. AKUN
-- -----------------------------------------------------
CREATE TABLE users (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  google_id     VARCHAR(100) NULL UNIQUE,          -- login Google (pasien)
  avatar        VARCHAR(255) NULL,
  password      VARCHAR(255) NULL,                 -- diisi untuk staf, NULL untuk pasien
  role          ENUM('admin','petugas','dokter','apoteker','pasien') NOT NULL,
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  remember_token VARCHAR(100) NULL,
  created_at    TIMESTAMP NULL,
  updated_at    TIMESTAMP NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 2. PASIEN & QR
-- -----------------------------------------------------
CREATE TABLE patients (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id             BIGINT UNSIGNED NULL UNIQUE,  -- NULL jika didaftarkan petugas (walk-in)
  no_rm               VARCHAR(20) NOT NULL UNIQUE,  -- nomor rekam medis
  nik                 CHAR(16) NOT NULL UNIQUE,
  nama_lengkap        VARCHAR(100) NOT NULL,
  no_wa               VARCHAR(20) NOT NULL,
  tgl_lahir           DATE NOT NULL,
  jenis_kelamin       ENUM('L','P') NOT NULL,
  alamat              TEXT NULL,
  golongan_darah      ENUM('A','B','AB','O','-') NOT NULL DEFAULT '-',
  alergi              TEXT NULL,
  qr_token            CHAR(36) NOT NULL UNIQUE,     -- UUID acak, isi QR (bukan data medis/NIK)
  verification_status ENUM('belum','terverifikasi') NOT NULL DEFAULT 'belum',
  verified_by         BIGINT UNSIGNED NULL,         -- petugas yang memverifikasi
  verified_at         DATETIME NULL,
  created_at          TIMESTAMP NULL,
  updated_at          TIMESTAMP NULL,
  CONSTRAINT fk_patients_user     FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_patients_verifier FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE qr_scan_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id  BIGINT UNSIGNED NOT NULL,
  scanned_by  BIGINT UNSIGNED NOT NULL,
  konteks     ENUM('checkin','verifikasi','pemeriksaan','farmasi') NOT NULL,
  created_at  TIMESTAMP NULL,
  CONSTRAINT fk_qrlog_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
  CONSTRAINT fk_qrlog_user    FOREIGN KEY (scanned_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 3. POLI, DOKTER, JADWAL
-- -----------------------------------------------------
CREATE TABLE polis (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama       VARCHAR(100) NOT NULL UNIQUE,
  deskripsi  TEXT NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE doctors (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          BIGINT UNSIGNED NOT NULL UNIQUE,
  poli_id          BIGINT UNSIGNED NOT NULL,
  no_str           VARCHAR(50) NULL,
  spesialisasi     VARCHAR(100) NULL,
  tarif_konsultasi DECIMAL(12,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_doctors_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_doctors_poli FOREIGN KEY (poli_id) REFERENCES polis(id)
) ENGINE=InnoDB;

CREATE TABLE doctor_schedules (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id   BIGINT UNSIGNED NOT NULL,
  hari        ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
  jam_mulai   TIME NOT NULL,
  jam_selesai TIME NOT NULL,
  kuota       SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_sched_doctor FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 4. KUNJUNGAN & ANTREAN
-- -----------------------------------------------------
CREATE TABLE visits (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id     BIGINT UNSIGNED NOT NULL,
  doctor_id      BIGINT UNSIGNED NOT NULL,
  poli_id        BIGINT UNSIGNED NOT NULL,
  schedule_id    BIGINT UNSIGNED NULL,
  tgl_kunjungan  DATE NOT NULL,
  no_antrian     SMALLINT UNSIGNED NULL,
  status         ENUM('booking','menunggu','diperiksa','farmasi','pembayaran','selesai','batal') NOT NULL DEFAULT 'booking',
  sumber         ENUM('online','walk_in','chatbot') NOT NULL DEFAULT 'online',
  keluhan_awal   TEXT NULL,
  checkin_metode ENUM('qr','manual') NULL,
  checkin_at     DATETIME NULL,
  checkin_by     BIGINT UNSIGNED NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  UNIQUE KEY uq_antrian (poli_id, doctor_id, tgl_kunjungan, no_antrian),
  INDEX idx_visit_tgl (tgl_kunjungan, status),
  CONSTRAINT fk_visits_patient  FOREIGN KEY (patient_id)  REFERENCES patients(id),
  CONSTRAINT fk_visits_doctor   FOREIGN KEY (doctor_id)   REFERENCES doctors(id),
  CONSTRAINT fk_visits_poli     FOREIGN KEY (poli_id)     REFERENCES polis(id),
  CONSTRAINT fk_visits_schedule FOREIGN KEY (schedule_id) REFERENCES doctor_schedules(id) ON DELETE SET NULL,
  CONSTRAINT fk_visits_checkin  FOREIGN KEY (checkin_by)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 5. REKAM MEDIS (SOAP) & DIAGNOSIS
-- -----------------------------------------------------
CREATE TABLE medical_records (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_id         BIGINT UNSIGNED NOT NULL UNIQUE,
  subjective       TEXT NULL,                       -- keluhan
  objective        TEXT NULL,                       -- hasil pemeriksaan fisik
  assessment       TEXT NULL,                       -- penilaian
  plan             TEXT NULL,                       -- rencana tindakan
  tekanan_darah    VARCHAR(10) NULL,                -- contoh: 120/80
  suhu             DECIMAL(4,1) NULL,
  nadi             SMALLINT UNSIGNED NULL,
  napas            SMALLINT UNSIGNED NULL,
  berat_badan      DECIMAL(5,2) NULL,
  tinggi_badan     DECIMAL(5,2) NULL,
  diinput_via_suara TINYINT(1) NOT NULL DEFAULT 0,  -- 1 jika dikte (speech-to-text)
  created_at       TIMESTAMP NULL,
  updated_at       TIMESTAMP NULL,
  CONSTRAINT fk_mr_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE diseases (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_icd10 VARCHAR(10) NOT NULL UNIQUE,
  nama       VARCHAR(200) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE medical_record_diagnoses (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medical_record_id BIGINT UNSIGNED NOT NULL,
  disease_id        BIGINT UNSIGNED NOT NULL,
  tipe              ENUM('primer','sekunder') NOT NULL DEFAULT 'primer',
  CONSTRAINT fk_mrd_record  FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE CASCADE,
  CONSTRAINT fk_mrd_disease FOREIGN KEY (disease_id)        REFERENCES diseases(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 6. LAYANAN / TINDAKAN
-- -----------------------------------------------------
CREATE TABLE services (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama      VARCHAR(150) NOT NULL,
  kategori  VARCHAR(50) NULL,
  tarif     DECIMAL(12,2) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE visit_services (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_id    BIGINT UNSIGNED NOT NULL,
  service_id  BIGINT UNSIGNED NOT NULL,
  qty         SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  harga       DECIMAL(12,2) NOT NULL,               -- harga saat transaksi
  CONSTRAINT fk_vs_visit   FOREIGN KEY (visit_id)   REFERENCES visits(id) ON DELETE CASCADE,
  CONSTRAINT fk_vs_service FOREIGN KEY (service_id) REFERENCES services(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 7. OBAT, RESEP, STOK
-- -----------------------------------------------------
CREATE TABLE medicines (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode          VARCHAR(30) NOT NULL UNIQUE,
  nama          VARCHAR(150) NOT NULL,
  satuan        VARCHAR(30) NOT NULL,               -- tablet, botol, strip
  harga         DECIMAL(12,2) NOT NULL DEFAULT 0,
  stok          INT NOT NULL DEFAULT 0,
  stok_minimum  INT NOT NULL DEFAULT 10,
  is_active     TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE medicine_stock_movements (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medicine_id   BIGINT UNSIGNED NOT NULL,
  tipe          ENUM('masuk','keluar','penyesuaian') NOT NULL,
  jumlah        INT NOT NULL,
  tgl_kedaluwarsa DATE NULL,
  prescription_id BIGINT UNSIGNED NULL,             -- terisi jika keluar karena resep
  keterangan    VARCHAR(255) NULL,
  created_by    BIGINT UNSIGNED NOT NULL,
  created_at    TIMESTAMP NULL,
  CONSTRAINT fk_msm_medicine FOREIGN KEY (medicine_id) REFERENCES medicines(id),
  CONSTRAINT fk_msm_user     FOREIGN KEY (created_by)  REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE prescriptions (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  doctor_id      BIGINT UNSIGNED NOT NULL,
  apoteker_id    BIGINT UNSIGNED NULL,              -- user apoteker yang memproses
  status         ENUM('menunggu','disiapkan','selesai') NOT NULL DEFAULT 'menunggu',
  catatan        TEXT NULL,
  diserahkan_at  DATETIME NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  CONSTRAINT fk_rx_visit    FOREIGN KEY (visit_id)    REFERENCES visits(id) ON DELETE CASCADE,
  CONSTRAINT fk_rx_doctor   FOREIGN KEY (doctor_id)   REFERENCES doctors(id),
  CONSTRAINT fk_rx_apoteker FOREIGN KEY (apoteker_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE medicine_stock_movements
  ADD CONSTRAINT fk_msm_rx FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL;

CREATE TABLE prescription_items (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  prescription_id BIGINT UNSIGNED NOT NULL,
  medicine_id     BIGINT UNSIGNED NOT NULL,
  jumlah          INT NOT NULL,
  dosis           VARCHAR(100) NULL,
  aturan_pakai    VARCHAR(150) NULL,                -- contoh: 3x1 sesudah makan
  harga           DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_rxi_rx       FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
  CONSTRAINT fk_rxi_medicine FOREIGN KEY (medicine_id)     REFERENCES medicines(id)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 8. PEMBAYARAN
-- -----------------------------------------------------
CREATE TABLE payments (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  visit_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  kasir_id       BIGINT UNSIGNED NULL,
  nomor_kwitansi VARCHAR(30) NOT NULL UNIQUE,
  total_jasa     DECIMAL(12,2) NOT NULL DEFAULT 0,  -- konsultasi + tindakan
  total_obat     DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_bayar    DECIMAL(12,2) NOT NULL DEFAULT 0,
  metode         ENUM('tunai','transfer','qris','bpjs') NULL,
  status         ENUM('belum_bayar','lunas') NOT NULL DEFAULT 'belum_bayar',
  dibayar_at     DATETIME NULL,
  created_at     TIMESTAMP NULL,
  updated_at     TIMESTAMP NULL,
  CONSTRAINT fk_pay_visit FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE CASCADE,
  CONSTRAINT fk_pay_kasir FOREIGN KEY (kasir_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 9. CHATBOT AI & AI SPEECH
-- -----------------------------------------------------
CREATE TABLE chatbot_knowledge (
  id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kategori   VARCHAR(50) NOT NULL,                  -- jam_buka, biaya, alur, dll
  pertanyaan VARCHAR(255) NOT NULL,
  jawaban    TEXT NOT NULL,
  is_active  TINYINT(1) NOT NULL DEFAULT 1,
  updated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT fk_kb_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE chat_conversations (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NULL,                 -- NULL jika pengunjung belum login
  patient_id  BIGINT UNSIGNED NULL,
  mode        ENUM('teks','suara') NOT NULL DEFAULT 'teks',
  started_at  DATETIME NOT NULL,
  ended_at    DATETIME NULL,
  CONSTRAINT fk_conv_user    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_conv_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE chat_messages (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id BIGINT UNSIGNED NOT NULL,
  pengirim        ENUM('pengguna','bot') NOT NULL,
  isi             TEXT NOT NULL,
  tipe_input      ENUM('teks','suara') NOT NULL DEFAULT 'teks',
  intent          VARCHAR(50) NULL,                 -- faq, booking, triase
  dibacakan_tts   TINYINT(1) NOT NULL DEFAULT 0,    -- 1 jika dibacakan text-to-speech
  created_at      TIMESTAMP NULL,
  INDEX idx_msg_conv (conversation_id, created_at),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE triage_results (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversation_id   BIGINT UNSIGNED NOT NULL,
  visit_id          BIGINT UNSIGNED NULL,           -- terisi jika berlanjut ke booking
  ringkasan_keluhan TEXT NOT NULL,
  poli_disarankan   BIGINT UNSIGNED NULL,
  tingkat_urgensi   ENUM('rendah','sedang','tinggi') NOT NULL DEFAULT 'rendah',
  created_at        TIMESTAMP NULL,
  CONSTRAINT fk_tri_conv  FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
  CONSTRAINT fk_tri_visit FOREIGN KEY (visit_id)        REFERENCES visits(id) ON DELETE SET NULL,
  CONSTRAINT fk_tri_poli  FOREIGN KEY (poli_disarankan) REFERENCES polis(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE speech_logs (
  id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id           BIGINT UNSIGNED NOT NULL,
  konteks           ENUM('chatbot','soap','antrean') NOT NULL,
  tipe              ENUM('stt','tts') NOT NULL,     -- speech-to-text / text-to-speech
  teks              TEXT NOT NULL,
  medical_record_id BIGINT UNSIGNED NULL,           -- terisi jika dikte SOAP
  durasi_detik      SMALLINT UNSIGNED NULL,
  created_at        TIMESTAMP NULL,
  CONSTRAINT fk_sp_user   FOREIGN KEY (user_id)           REFERENCES users(id),
  CONSTRAINT fk_sp_record FOREIGN KEY (medical_record_id) REFERENCES medical_records(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- 10. AUDIT
-- -----------------------------------------------------
CREATE TABLE activity_logs (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      BIGINT UNSIGNED NULL,
  aksi         VARCHAR(50) NOT NULL,                -- login, lihat_rekam_medis, ubah_data, dll
  subjek_tipe  VARCHAR(50) NULL,
  subjek_id    BIGINT UNSIGNED NULL,
  deskripsi    VARCHAR(255) NULL,
  ip_address   VARCHAR(45) NULL,
  created_at   TIMESTAMP NULL,
  INDEX idx_log_user (user_id, created_at),
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
