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
            SELECT a.*, s.name student_name, s.nis, s.photo, c.name class_name, d.name device_name,
                   (SELECT status FROM whatsapp_queue wq WHERE wq.student_id=a.student_id AND DATE(wq.created_at)=a.attendance_date ORDER BY wq.id DESC LIMIT 1) wa_status
            FROM attendances a
            JOIN students s ON s.id=a.student_id
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
            SELECT a.time_in, a.status, s.name student_name, s.nis, c.name class_name, d.name device_name,
                   (SELECT status FROM whatsapp_queue wq WHERE wq.student_id=a.student_id AND DATE(wq.created_at)=a.attendance_date ORDER BY wq.id DESC LIMIT 1) wa_status
            FROM attendances a
            JOIN students s ON s.id=a.student_id
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
            SELECT a.*, s.name student_name, s.nis, c.name class_name
            FROM attendances a
            JOIN students s ON s.id=a.student_id
            LEFT JOIN classes c ON c.id=a.class_id
            WHERE $where ORDER BY s.name
        ", $params);
        $classes = Database::fetchAll("SELECT * FROM classes ORDER BY name");
        $this->view('attendance.index', compact('rows','classes','date','classId') + ['title'=>'Data Absensi']);
    }

    public function permitCreate() {
        Auth::require(['super_admin','admin','guru']);
        $students = Database::fetchAll("SELECT id,name,nis FROM students WHERE status='aktif' ORDER BY name");
        $this->view('attendance.permit', compact('students') + ['title'=>'Input Izin/Sakit']);
    }

    public function permitStore() {
        Auth::require(['super_admin','admin','guru']); Csrf::verify();
        $studentId = $this->input('student_id');
        $date = $this->input('date');
        $status = $this->input('status');
        $note = $this->input('note','');
        $student = Database::fetch("SELECT * FROM students WHERE id=?", [$studentId]);
        if (!$student) { flash('error','Siswa tidak ditemukan.'); redirect('/attendance/permit'); }
        $exists = Database::fetch("SELECT id FROM attendances WHERE student_id=? AND attendance_date=?", [$studentId, $date]);
        if ($exists) {
            Database::update('attendances', ['status'=>$status,'note'=>$note], 'id=:id', ['id'=>$exists['id']]);
        } else {
            Database::insert('attendances', [
                'student_id'=>$studentId, 'class_id'=>$student['class_id'],
                'attendance_date'=>$date, 'status'=>$status, 'note'=>$note,
                'created_by'=>Auth::user()['id'],
            ]);
        }
        Auth::logActivity('input_permit', "Siswa: {$student['name']} - $status");
        flash('success','Izin/Sakit tersimpan.');
        redirect('/attendance');
    }
}
