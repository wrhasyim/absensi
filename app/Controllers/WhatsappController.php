<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Csrf;

require_once APP_ROOT . '/services/WhatsAppService.php';

class WhatsappController extends Controller {
    public function dashboard() {
        Auth::require();
        $svc = new \App\Services\WhatsAppService();
        $status = $svc->status();
        $stats = [
            'pending' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='pending'")['c'],
            'processing' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='processing'")['c'],
            'sent' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='sent'")['c'],
            'failed' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='failed'")['c'],
            'retry' => (int)Database::fetch("SELECT COUNT(*) c FROM whatsapp_queue WHERE status='retry'")['c'],
        ];
        $recent = Database::fetchAll("SELECT * FROM whatsapp_queue ORDER BY id DESC LIMIT 20");
        $this->view('whatsapp.dashboard', compact('status','stats','recent') + ['title'=>'WhatsApp Gateway']);
    }

    public function statusJson() {
        Auth::require();
        $svc = new \App\Services\WhatsAppService();
        $this->json($svc->status());
    }

    public function qrJson() {
        Auth::require();
        $svc = new \App\Services\WhatsAppService();
        $this->json($svc->qr());
    }

    public function reconnect() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $svc = new \App\Services\WhatsAppService();
        $this->json($svc->reconnect());
    }

    public function logout() {
        Auth::require(['super_admin']); Csrf::verify();
        $svc = new \App\Services\WhatsAppService();
        $this->json($svc->logout());
    }

    public function templates() {
        Auth::require(['super_admin','admin']);
        $templates = Database::fetchAll("SELECT * FROM whatsapp_templates ORDER BY code");
        $this->view('whatsapp.templates', compact('templates') + ['title'=>'Template WhatsApp']);
    }

    public function templateSave() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $id = $this->input('id');
        $data = [
            'code'=>trim($this->input('code','')),
            'name'=>trim($this->input('name','')),
            'content'=>$this->input('content',''),
            'is_active'=>$this->input('is_active','1'),
        ];
        if ($id) Database::update('whatsapp_templates',$data,'id=:id',['id'=>$id]);
        else Database::insert('whatsapp_templates',$data);
        flash('success','Template disimpan.'); redirect('/whatsapp/templates');
    }

    public function queue() {
        Auth::require();
        $status = $this->input('status','');
        $where = "1=1"; $params=[];
        if ($status) { $where.=" AND wq.status=?"; $params[]=$status; }
        
        // Menggunakan COALESCE untuk menarik nama Siswa atau Guru, serta mendeteksi rolenya
        $rows = Database::fetchAll("
            SELECT wq.*, 
                   COALESCE(s.name, t.name) as student_name,
                   CASE WHEN wq.student_id IS NOT NULL THEN 'Siswa' ELSE 'Guru' END as target_role
            FROM whatsapp_queue wq
            LEFT JOIN students s ON s.id=wq.student_id
            LEFT JOIN teachers t ON t.id=wq.teacher_id
            WHERE $where ORDER BY wq.id DESC LIMIT 200
        ", $params);
        
        $this->view('whatsapp.queue', compact('rows','status') + ['title'=>'Queue Pesan']);
    }

    public function resend($id) {
        Auth::require(['super_admin','admin']); Csrf::verify();
        Database::update('whatsapp_queue', ['status'=>'pending','attempt'=>0,'last_error'=>null,'scheduled_at'=>date('Y-m-d H:i:s')], 'id=:id', ['id'=>$id]);
        // Process this one immediately
        $svc = new \App\Services\WhatsAppService();
        $svc->processQueue(1);
        flash('success','Pesan diproses ulang.'); redirect('/whatsapp/queue');
    }

    public function processQueueNow() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $svc = new \App\Services\WhatsAppService();
        $res = $svc->processQueue(50);
        $this->json($res);
    }

    public function testSend() {
        Auth::require(['super_admin','admin']); Csrf::verify();
        $phone = \App\Services\WhatsAppService::normalizePhone($this->input('phone',''));
        $message = $this->input('message','Test dari Sistem Absensi.');
        $svc = new \App\Services\WhatsAppService();
        $this->json($svc->sendDirect($phone, $message));
    }
}
