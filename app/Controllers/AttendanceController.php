<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

class AttendanceController extends Controller {
    public function monitor() {
        Auth::require();
        $today = date('Y-m-d');
        $rows = Database::fetchAll("
            SELECT a.*, 
                   COALESCE(s.name, t.name) as user_name, 
                   COALESCE(s.nis, t.nip, '-') as identifier, 
                   COALESCE(c.name, 'Guru / Staff') as class_name, 
                   d.name device_name,
                   CASE WHEN a.student_id IS NOT NULL THEN 'Siswa' ELSE 'Guru' END as role,
                   (SELECT status FROM whatsapp_queue wq WHERE (wq.student_id=a.student_id OR wq.teacher_id=a.teacher_id) AND DATE(wq.created_at)=a.attendance_date ORDER BY wq.id DESC LIMIT 1) wa_status
            FROM attendances a
            LEFT JOIN students s ON s.id=a.student_id
            LEFT JOIN teachers t ON t.id=a.teacher_id
            LEFT JOIN classes c ON c.id=a.class_id
            LEFT JOIN devices d ON d.id=a.device_id
            WHERE a.attendance_date=?
            ORDER BY a.time_in DESC
        ", [$today]);
        $this->view('attendance.monitor', compact('rows') + ['title'=>'Monitor Absensi']);
    }

    public function monitorJson() {
        Auth::require();
        $today = date('Y-m-d');
        $rows = Database::fetchAll("
            SELECT a.time_in, a.status, 
                   COALESCE(s.name, t.name) as user_name, 
                   COALESCE(s.nis, t.nip, '-') as identifier, 
                   COALESCE(c.name, 'Guru / Staff') as class_name, 
                   d.name device_name,
                   CASE WHEN a.student_id IS NOT NULL THEN 'Siswa' ELSE 'Guru' END as role,
                   (SELECT status FROM whatsapp_queue wq WHERE (wq.student_id=a.student_id OR wq.teacher_id=a.teacher_id) AND DATE(wq.created_at)=a.attendance_date ORDER BY wq.id DESC LIMIT 1) wa_status
            FROM attendances a
            LEFT JOIN students s ON s.id=a.student_id
            LEFT JOIN teachers t ON t.id=a.teacher_id
            LEFT JOIN classes c ON c.id=a.class_id
            LEFT JOIN devices d ON d.id=a.device_id
            WHERE a.attendance_date=?
            ORDER BY a.time_in DESC LIMIT 50
        ", [$today]);
        $this->json(['data' => $rows, 'time' => date('H:i:s')]);
    }

    public function index() {
        Auth::require();
        $date = $this->input('date', date('Y-m-d'));
        $classId = $this->input('class_id', '');
        
        $where = "a.attendance_date=?"; $params=[$date];
        if ($classId) { $where.=" AND a.class_id=?"; $params[]=$classId; }
        
        $rows = Database::fetchAll("
            SELECT a.*, 
                   COALESCE(s.name, t.name) as user_name, 
                   COALESCE(s.nis, t.nip, '-') as identifier, 
                   COALESCE(c.name, 'Guru / Staff') as class_name,
                   CASE WHEN a.student_id IS NOT NULL THEN 'Siswa' ELSE 'Guru' END as role
            FROM attendances a
            LEFT JOIN students s ON s.id=a.student_id
            LEFT JOIN teachers t ON t.id=a.teacher_id
            LEFT JOIN classes c ON c.id=a.class_id
            WHERE $where ORDER BY user_name
        ", $params);
        
        $classes = Database::fetchAll("SELECT * FROM classes ORDER BY name");
        $this->view('attendance.index', compact('rows','classes','date','classId') + ['title'=>'Data Absensi']);
    }

    public function permitCreate() {
        Auth::require(['super_admin','admin','guru']);
        $students = Database::fetchAll("SELECT id, name, nis as identifier, 'S_Siswa' as role FROM students WHERE status='aktif'");
        
        // Ubah '-' menjadi nip agar tampil rapi di dropdown
        $teachers = Database::fetchAll("SELECT id, name, nip as identifier, 'T_Guru' as role FROM teachers");
        
        $users = array_merge($students, $teachers);
        usort($users, fn($a, $b) => strcmp($a['name'], $b['name']));
        
        $this->view('attendance.permit', compact('users') + ['title'=>'Input Izin/Sakit']);
    }

    public function permitStore() {
        Auth::require(['super_admin','admin','guru']); Csrf::verify();
        
        $user_val = $this->input('user_id'); // Format dari form harus e.g. "S_1" atau "T_5"
        $date = $this->input('date');
        $status = $this->input('status');
        $note = $this->input('note','');
        
        $parts = explode('_', $user_val);
        $role = $parts[0] ?? '';
        $id = $parts[1] ?? 0;

        if ($role === 'S') {
            $user = Database::fetch("SELECT * FROM students WHERE id=?", [$id]);
            $studentId = $id; $teacherId = null; $classId = $user['class_id'];
        } else {
            $user = Database::fetch("SELECT * FROM teachers WHERE id=?", [$id]);
            $studentId = null; $teacherId = $id; $classId = null;
        }

        if (!$user) { flash('error','Data pengguna tidak ditemukan.'); redirect('/attendance/permit'); }
        
        $exists = Database::fetch("SELECT id FROM attendances WHERE (student_id=? OR teacher_id=?) AND attendance_date=?", [$studentId, $teacherId, $date]);
        
        if ($exists) {
            Database::update('attendances', ['status'=>$status,'note'=>$note], 'id=:id', ['id'=>$exists['id']]);
        } else {
            Database::insert('attendances', [
                'student_id' => $studentId, 
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'attendance_date' => $date, 
                'status' => $status, 
                'note' => $note,
                'created_by' => Auth::user()['id'],
            ]);
        }
        
        Auth::logActivity('input_permit', "{$user['name']} - $status");
        flash('success','Izin/Sakit tersimpan.');
        redirect('/attendance');
    }
}