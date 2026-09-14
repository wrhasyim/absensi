<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;

class ReportController extends Controller {
    public function daily() {
        Auth::require();
        $date = $this->input('date', date('Y-m-d'));
        $role = $this->input('role', 'siswa'); 

        if ($role === 'guru') {
            $rows = Database::fetchAll("
                SELECT t.nip as nis, t.name as student_name, 'Guru' as class_name,
                       COALESCE(a.status,'belum_absen') status, a.time_in, a.time_out
                FROM teachers t
                LEFT JOIN attendances a ON a.teacher_id=t.id AND a.attendance_date=?
                ORDER BY t.name
            ", [$date]);
        } else {
            $rows = Database::fetchAll("
                SELECT s.nis, s.name student_name, c.name class_name,
                       COALESCE(a.status,'belum_absen') status, a.time_in, a.time_out
                FROM students s
                LEFT JOIN classes c ON c.id=s.class_id
                LEFT JOIN attendances a ON a.student_id=s.id AND a.attendance_date=?
                WHERE s.status='aktif'
                ORDER BY c.name, s.name
            ", [$date]);
        }

        $summary = ['hadir'=>0,'terlambat'=>0,'izin'=>0,'sakit'=>0,'alpa'=>0,'belum_absen'=>0,'dispensasi'=>0];
        foreach ($rows as $r) { if (isset($summary[$r['status']])) $summary[$r['status']]++; }
        
        $this->view('reports.daily', compact('rows','date','summary','role') + ['title'=>'Laporan Harian']);
    }

    public function monthly() {
        Auth::require();
        $month = $this->input('month', date('Y-m'));
        $role = $this->input('role', 'siswa');
        [$y,$m] = explode('-', $month);

        if ($role === 'guru') {
            $rows = Database::fetchAll("
                SELECT t.nip as nis, t.name, 'Guru' as class_name,
                       SUM(a.status='hadir') hadir,
                       SUM(a.status='terlambat') terlambat,
                       SUM(a.status='izin') izin,
                       SUM(a.status='sakit') sakit,
                       SUM(a.status='alpa') alpa
                FROM teachers t
                LEFT JOIN attendances a ON a.teacher_id=t.id AND YEAR(a.attendance_date)=? AND MONTH(a.attendance_date)=?
                GROUP BY t.id ORDER BY t.name
            ", [$y, $m]);
        } else {
            $rows = Database::fetchAll("
                SELECT s.nis, s.name, c.name class_name,
                       SUM(a.status='hadir') hadir,
                       SUM(a.status='terlambat') terlambat,
                       SUM(a.status='izin') izin,
                       SUM(a.status='sakit') sakit,
                       SUM(a.status='alpa') alpa
                FROM students s
                LEFT JOIN classes c ON c.id=s.class_id
                LEFT JOIN attendances a ON a.student_id=s.id AND YEAR(a.attendance_date)=? AND MONTH(a.attendance_date)=?
                WHERE s.status='aktif'
                GROUP BY s.id ORDER BY c.name, s.name
            ", [$y, $m]);
        }
        
        $this->view('reports.monthly', compact('rows','month','role') + ['title'=>'Laporan Bulanan']);
    }

    public function whatsapp() {
        Auth::require();
        $summary = [
            'total'=>(int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue")['c'],
            'sent'=>(int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='sent'")['c'],
            'failed'=>(int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='failed'")['c'],
            'pending'=>(int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status IN ('pending','retry')")['c'],
        ];
        $rows = Database::fetchAll("SELECT * FROM whatsapp_queue ORDER BY id DESC LIMIT 100");
        $this->view('reports.whatsapp', compact('summary','rows') + ['title'=>'Laporan WhatsApp']);
    }

    public function health() {
        Auth::require();
        $svc = new \App\Services\WhatsAppService();
        $wa = $svc->status();
        try { \App\Core\Database::conn(); $db='ONLINE'; } catch (\Throwable $e) { $db='OFFLINE'; }
        $devices = Database::fetchAll("SELECT * FROM devices WHERE is_active=1");
        $waQ = (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status IN ('pending','retry','processing')")['c'];
        $waFailed = (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='failed'")['c'];
        $this->view('reports.health', compact('wa','db','devices','waQ','waFailed') + ['title'=>'System Health']);
    }

    public function settings() {
        Auth::require(['super_admin','admin']);
        $rows = Database::fetchAll("SELECT * FROM school_settings");
        $settings = [];
        foreach ($rows as $r) $settings[$r['setting_key']] = $r['setting_value'];
        $this->view('settings.index', compact('settings') + ['title'=>'Pengaturan Sekolah']);
    }

    public function settingsSave() {
        Auth::require(['super_admin','admin']);
        \App\Core\Csrf::verify();
        foreach ($_POST as $k => $v) {
            if (in_array($k, ['_csrf','_method'])) continue;
            $exists = Database::fetch("SELECT id FROM school_settings WHERE setting_key=?", [$k]);
            if ($exists) Database::update('school_settings', ['setting_value'=>$v], 'id=:id', ['id'=>$exists['id']]);
            else Database::insert('school_settings', ['setting_key'=>$k,'setting_value'=>$v]);
        }
        flash('success','Pengaturan disimpan.');
        redirect('/settings');
    }
}