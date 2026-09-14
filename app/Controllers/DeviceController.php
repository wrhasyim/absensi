<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

require_once APP_ROOT . '/services/FingerprintService.php';

class DeviceController extends Controller {
    public function index() {
        Auth::require();
        $devices = Database::fetchAll("SELECT * FROM devices ORDER BY name");
        $this->view('devices.index', compact('devices') + ['title'=>'Perangkat Fingerprint']);
    }

    public function store() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $id = $this->input('id');
        $data = [
            'name'=>trim($this->input('name','')),
            'ip_address'=>trim($this->input('ip_address','')),
            'port'=>(int)$this->input('port',4370),
            'location'=>trim($this->input('location','')),
            'adapter'=>$this->input('adapter','mock'),
            'comm_key'=>trim($this->input('comm_key','0')),
            'is_active'=>$this->input('is_active','1'),
        ];
        if ($id) Database::update('devices',$data,'id=:id',['id'=>$id]);
        else Database::insert('devices',$data);
        flash('success','Perangkat disimpan.');
        redirect('/devices');
    }

    public function delete($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::query("DELETE FROM devices WHERE id=?", [$id]);
        flash('success','Perangkat dihapus.'); redirect('/devices');
    }

    public function testConnection($id) {
        Auth::require(['super_admin','admin']);
        $device = Database::fetch("SELECT * FROM devices WHERE id=?", [$id]);
        if (!$device) $this->json(['success'=>false,'message'=>'Perangkat tidak ditemukan']);
        $service = new \App\Services\FingerprintService($device);
        $res = $service->testConnection();
        Database::update('devices', ['status' => $res['success'] ? 'online':'offline','last_connection'=>date('Y-m-d H:i:s')], 'id=:id',['id'=>$id]);
        $this->json($res);
    }

    public function sync($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $device = Database::fetch("SELECT * FROM devices WHERE id=?", [$id]);
        if (!$device) $this->json(['success'=>false,'message'=>'Perangkat tidak ditemukan']);
        $service = new \App\Services\FingerprintService($device);
        $res = $service->synchronize();
        Auth::logActivity('sync_fingerprint', json_encode($res));
        $this->json($res);
    }

    public function logs() {
        Auth::require();
        $logs = Database::fetchAll("
            SELECT fl.*, 
                   DATE(fl.log_datetime) as log_date,
                   TIME(fl.log_datetime) as log_time,
                   COALESCE(s.name, t.name) as user_name,
                   CASE 
                       WHEN s.id IS NOT NULL THEN CONCAT('Siswa (NIS: ', COALESCE(s.nis, '-'), ')') 
                       WHEN t.id IS NOT NULL THEN CONCAT('Guru (NIP: ', COALESCE(t.nip, '-'), ')') 
                       ELSE 'Tidak dikenal' 
                   END as user_role,
                   d.name device_name
            FROM fingerprint_logs fl
            LEFT JOIN students s ON s.id=fl.student_id
            LEFT JOIN teachers t ON t.id=fl.teacher_id
            LEFT JOIN devices d ON d.id=fl.device_id
            ORDER BY fl.log_datetime DESC LIMIT 200
        ");
        $this->view('devices.logs', compact('logs') + ['title'=>'Log Fingerprint']);
    }
}