<div class="stat-grid">
  <div class="stat primary"><i class="bi bi-people icon"></i><div class="label">Total Siswa</div><div class="value" data-testid="stat-total-siswa"><?= $stats['total_siswa'] ?></div><div class="foot">Siswa aktif</div></div>
  <div class="stat success"><i class="bi bi-check2-circle icon"></i><div class="label">Hadir Hari Ini</div><div class="value" data-testid="stat-hadir"><?= $stats['hadir'] ?></div><div class="foot">Sudah absen</div></div>
  <div class="stat warning"><i class="bi bi-alarm icon"></i><div class="label">Terlambat</div><div class="value" data-testid="stat-terlambat"><?= $stats['terlambat'] ?></div><div class="foot">Lewat toleransi</div></div>
  <div class="stat info"><i class="bi bi-file-medical icon"></i><div class="label">Izin/Sakit</div><div class="value" data-testid="stat-izin-sakit"><?= $stats['izin'] + $stats['sakit'] ?></div><div class="foot">I:<?= $stats['izin'] ?> S:<?= $stats['sakit'] ?></div></div>
  <div class="stat danger"><i class="bi bi-x-circle icon"></i><div class="label">Belum Absen</div><div class="value" data-testid="stat-belum-absen"><?= $stats['belum_absen'] ?></div><div class="foot">Persentase: <?= $stats['persentase'] ?>%</div></div>
  <div class="stat success"><i class="bi bi-whatsapp icon"></i><div class="label">WA Terkirim</div><div class="value" data-testid="stat-wa-terkirim"><?= $stats['wa_sent'] ?></div><div class="foot">Gagal: <?= $stats['wa_failed'] ?> · Pending: <?= $stats['wa_pending'] ?></div></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-graph-up-arrow"></i> Tren Kehadiran 14 Hari</span>
        <div class="small text-muted"><span class="dot" style="background:#22c55e"></span>Hadir <span class="dot" style="background:#f59e0b"></span>Terlambat <span class="dot" style="background:#38bdf8"></span>Izin/Sakit <span class="dot" style="background:#ef4444"></span>Alpa</div>
      </div>
      <div class="card-body"><canvas id="chartTrend" height="110" data-testid="chart-trend"></canvas></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-pie-chart"></i> Ringkasan Hari Ini</div>
      <div class="card-body d-flex align-items-center justify-content-center">
        <div style="max-width:280px; width:100%"><canvas id="chartToday" data-testid="chart-today"></canvas></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-7">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-bar-chart-line"></i> Kehadiran per Kelas (Hari Ini)</div>
      <div class="card-body"><canvas id="chartClass" height="150" data-testid="chart-class"></canvas></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card h-100">
      <div class="card-header"><i class="bi bi-alarm"></i> Top Terlambat Bulan Ini</div>
      <div class="card-body"><canvas id="chartLate" height="150" data-testid="chart-late"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-broadcast"></i> Absensi Terbaru Hari Ini</span>
        <a href="<?= url('/attendance/monitor') ?>" class="btn btn-sm btn-outline-primary">Monitor Realtime</a>
      </div>
      <div class="table-responsive"><table class="table table-hover mb-0">
        <thead><tr><th>Jam</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status</th></tr></thead>
        <tbody data-testid="dashboard-recent">
        <?php if (empty($recent)): ?>
          <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada absensi hari ini.</td></tr>
        <?php else: foreach ($recent as $r): ?>
          <tr>
            <td><?= e(substr($r['time_in'] ?? '', 0, 5)) ?></td>
            <td><?= e($r['nis']) ?></td>
            <td><?= e($r['student_name']) ?></td>
            <td><?= e($r['class_name']) ?></td>
            <td><span class="badge-status st-<?= e($r['status']) ?>"><?= strtoupper(e($r['status'])) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header"><i class="bi bi-hdd-network"></i> Status Perangkat</div>
      <div class="card-body">
        <?php if (empty($devices)): ?>
          <div class="text-muted">Belum ada perangkat.</div>
        <?php else: foreach ($devices as $d): ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div><b><?= e($d['name']) ?></b></div>
              <small class="text-muted"><?= e($d['ip_address']) ?>:<?= e($d['port']) ?></small>
            </div>
            <span class="badge-status st-<?= e($d['status']) ?>"><?= strtoupper($d['status']) ?></span>
          </div>
        <?php endforeach; endif; ?>
        <a href="<?= url('/devices') ?>" class="btn btn-sm btn-outline-primary w-100 mt-2">Kelola Perangkat</a>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  const TREND = <?= json_encode($trend) ?>;
  const PER_CLASS = <?= json_encode($perClass) ?>;
  const TOP_LATE = <?= json_encode($topLate) ?>;
  const TODAY_SUMMARY = <?= json_encode([
      'Hadir' => $stats['hadir'],
      'Terlambat' => $stats['terlambat'],
      'Izin' => $stats['izin'],
      'Sakit' => $stats['sakit'],
      'Alpa' => $stats['alpa'],
      'Belum Absen' => $stats['belum_absen'],
  ]) ?>;

  Chart.defaults.color = '#c6d2f2';
  Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.font.size = 12;

  // 14-day trend line
  const trendLabels = TREND.map(t => new Date(t.attendance_date).toLocaleDateString('id-ID',{day:'2-digit',month:'short'}));
  new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
      labels: trendLabels,
      datasets: [
        {label:'Hadir', data: TREND.map(t=>+t.hadir), borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,0.15)', tension:0.35, fill:true, pointRadius:3, pointHoverRadius:6, borderWidth:2},
        {label:'Terlambat', data: TREND.map(t=>+t.terlambat), borderColor:'#f59e0b', backgroundColor:'rgba(245,158,11,0.15)', tension:0.35, fill:true, pointRadius:3, pointHoverRadius:6, borderWidth:2},
        {label:'Izin/Sakit', data: TREND.map(t=>+t.izin_sakit), borderColor:'#38bdf8', backgroundColor:'transparent', tension:0.35, borderDash:[5,4], pointRadius:2, borderWidth:2},
        {label:'Alpa', data: TREND.map(t=>+t.alpa), borderColor:'#ef4444', backgroundColor:'transparent', tension:0.35, pointRadius:2, borderWidth:2},
      ],
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      plugins:{ legend:{display:false}, tooltip:{mode:'index',intersect:false, backgroundColor:'#1a2338', borderColor:'#2a3552', borderWidth:1, padding:10}},
      scales:{
        y:{beginAtZero:true, grid:{color:'rgba(255,255,255,0.05)'}, ticks:{precision:0}},
        x:{grid:{display:false}}
      },
      interaction:{mode:'nearest',axis:'x',intersect:false}
    }
  });

  // Today donut
  const tKeys = Object.keys(TODAY_SUMMARY);
  const tVals = tKeys.map(k=>TODAY_SUMMARY[k]);
  const tColors = ['#22c55e','#f59e0b','#38bdf8','#a855f7','#ef4444','#64748b'];
  new Chart(document.getElementById('chartToday'), {
    type:'doughnut',
    data:{labels: tKeys, datasets:[{data: tVals, backgroundColor: tColors, borderColor:'#1a2338', borderWidth:2, hoverOffset:8}]},
    options:{
      responsive:true, maintainAspectRatio:true, cutout:'62%',
      plugins:{
        legend:{position:'bottom', labels:{boxWidth:10, padding:10, font:{size:11}}},
        tooltip:{backgroundColor:'#1a2338', borderColor:'#2a3552', borderWidth:1, padding:10}
      }
    }
  });

  // Per class stacked bar
  new Chart(document.getElementById('chartClass'), {
    type:'bar',
    data:{
      labels: PER_CLASS.map(c=>c.class_name),
      datasets:[
        {label:'Hadir', data: PER_CLASS.map(c=>+c.hadir), backgroundColor:'#22c55e', stack:'x', borderRadius:6},
        {label:'Terlambat', data: PER_CLASS.map(c=>+c.terlambat), backgroundColor:'#f59e0b', stack:'x', borderRadius:6},
        {label:'Izin/Sakit', data: PER_CLASS.map(c=>+c.izin), backgroundColor:'#38bdf8', stack:'x', borderRadius:6},
        {label:'Alpa', data: PER_CLASS.map(c=>+c.alpa), backgroundColor:'#ef4444', stack:'x', borderRadius:6},
      ]
    },
    options:{
      responsive:true, maintainAspectRatio:false,
      plugins:{legend:{position:'bottom', labels:{boxWidth:10, padding:10}}, tooltip:{backgroundColor:'#1a2338'}},
      scales:{
        x:{stacked:true, grid:{display:false}},
        y:{stacked:true, beginAtZero:true, grid:{color:'rgba(255,255,255,0.05)'}, ticks:{precision:0}}
      }
    }
  });

  // Top late horizontal bar
  const lateEl = document.getElementById('chartLate');
  if (TOP_LATE.length === 0) {
    lateEl.parentElement.innerHTML = '<div class="text-muted text-center py-4">Belum ada data keterlambatan bulan ini.</div>';
  } else {
    new Chart(lateEl, {
      type:'bar',
      data:{
        labels: TOP_LATE.map(x=>x.name + ' — ' + (x.class_name||'-')),
        datasets:[{label:'Terlambat', data: TOP_LATE.map(x=>+x.total_terlambat), backgroundColor:'#fbbf24', borderRadius:6, barThickness:22}]
      },
      options:{
        indexAxis:'y', responsive:true, maintainAspectRatio:false,
        plugins:{legend:{display:false}, tooltip:{backgroundColor:'#1a2338'}},
        scales:{
          x:{beginAtZero:true, grid:{color:'rgba(255,255,255,0.05)'}, ticks:{precision:0}},
          y:{grid:{display:false}}
        }
      }
    });
  }
})();
</script>
<style>
.dot{ display:inline-block; width:10px; height:10px; border-radius:50%; margin: 0 4px 0 10px; vertical-align: middle;}
</style>
