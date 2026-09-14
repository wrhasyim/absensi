<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

class DashboardController extends Controller {
    public function index() {
        Auth::require();
        $today = date('Y-m-d');
        
        $stats = [
            'total_siswa' => (int)Database::fetch("SELECT COUNT(*) c FROM students WHERE status='aktif'")['c'],
            'total_guru' => (int)Database::fetch("SELECT COUNT(*) c FROM teachers")['c'],
            'hadir_siswa' => (int)Database::fetch("SELECT COUNT(*) c FROM attendances WHERE attendance_date=? AND student_id IS NOT NULL AND status='hadir'", [$today])['c'],
            'hadir_guru' => (int)Database::fetch("SELECT COUNT(*) c FROM attendances WHERE attendance_date=? AND teacher_id IS NOT NULL AND status='hadir'", [$today])['c'],
            
            // Total gabungan untuk ringkasan cepat
            'hadir' => (int)Database::fetch("SELECT COUNT(*) c FROM attendances WHERE attendance_date=? AND status='hadir'", [$today])['c'],
            'terlambat' => (int)Database::fetch("SELECT COUNT(*) c FROM attendances WHERE attendance_date=? AND status='terlambat'", [$today])['c'],
            'izin' => (int)Database::fetch("SELECT COUNT(*) c FROM attendances WHERE attendance_date=? AND status='izin'", [$today])['c'],
            'sakit' => (int)Database::fetch("SELECT COUNT(*) c FROM attendances WHERE attendance_date=? AND status='sakit'", [$today])['c'],
            'alpa' => (int)Database::fetch("SELECT COUNT(*) c FROM attendances WHERE attendance_date=? AND status='alpa'", [$today])['c'],
            
            'wa_sent' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='sent' AND DATE(sent_at)=?", [$today])['c'],
            'wa_failed' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='failed' AND DATE(created_at)=?", [$today])['c'],
            'wa_pending' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status IN ('pending','retry','processing')")['c'],
        ];
        
        $totalPengguna = $stats['total_siswa'] + $stats['total_guru'];
        $stats['total_siswa_guru'] = $totalPengguna;
        $stats['belum_absen'] = max(0, $totalPengguna - $stats['hadir'] - $stats['terlambat'] - $stats['izin'] - $stats['sakit'] - $stats['alpa']);
        $stats['persentase'] = $totalPengguna > 0 ? round((($stats['hadir'] + $stats['terlambat']) / $totalPengguna) * 100, 1) : 0;

        // Trend 14 hari (line chart) gabungan
        $trend = Database::fetchAll("
            SELECT attendance_date,
                   SUM(status='hadir') hadir,
                   SUM(status='terlambat') terlambat,
                   SUM(status='alpa') alpa,
                   SUM(status IN ('izin','sakit','dispensasi')) izin_sakit
            FROM attendances
            WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
            GROUP BY attendance_date
            ORDER BY attendance_date
        ");

        // Kehadiran per kelas hari ini (Stacked bar khusus siswa)
        $perClass = Database::fetchAll("
            SELECT c.name class_name,
                   SUM(a.status='hadir') hadir,
                   SUM(a.status='terlambat') terlambat,
                   SUM(a.status IN ('izin','sakit','dispensasi')) izin,
                   SUM(a.status='alpa') alpa
            FROM classes c
            LEFT JOIN attendances a ON a.class_id=c.id AND a.attendance_date=?
            GROUP BY c.id, c.name
            ORDER BY c.name
        ", [$today]);

        // Top 5 siswa terlambat bulan ini
        $topLate = Database::fetchAll("
            SELECT s.name, c.name class_name, COUNT(*) total_terlambat
            FROM attendances a
            JOIN students s ON s.id=a.student_id
            LEFT JOIN classes c ON c.id=a.class_id
            WHERE a.status='terlambat' AND YEAR(a.attendance_date)=YEAR(CURDATE()) AND MONTH(a.attendance_date)=MONTH(CURDATE())
            GROUP BY s.id, s.name, c.name
            ORDER BY total_terlambat DESC
            LIMIT 5
        ");

        $devices = Database::fetchAll("SELECT * FROM devices WHERE is_active=1 LIMIT 5");
        
        // Aktivitas absensi terbaru (Siswa & Guru digabung via COALESCE)
        $recent = Database::fetchAll("
            SELECT a.*, 
                   COALESCE(s.name, t.name) as user_name, 
                   COALESCE(s.nis, t.nip, '-') as identifier, 
                   COALESCE(c.name, 'Guru / Staff') as class_name,
                   CASE WHEN a.student_id IS NOT NULL THEN 'Siswa' ELSE 'Guru' END as role
            FROM attendances a
            LEFT JOIN students s ON s.id = a.student_id
            LEFT JOIN teachers t ON t.id = a.teacher_id
            LEFT JOIN classes c ON c.id = a.class_id
            WHERE a.attendance_date = ?
            ORDER BY a.time_in DESC LIMIT 10
        ", [$today]);

        $this->view('dashboard.index', compact('stats', 'trend', 'perClass', 'topLate', 'devices', 'recent') + ['title' => 'Dashboard']);
    }
}