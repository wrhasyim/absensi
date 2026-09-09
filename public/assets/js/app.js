// Absensi Sekolah - app.js
window.postJson = async function(url, data = {}) {
  const res = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-Token': window.CSRF},
    body: JSON.stringify(data), credentials:'same-origin'
  });
  try { return await res.json(); } catch(e) { return {success:false, error:'invalid response'};}
};

window.postForm = function(url) {
  const f = document.createElement('form');
  f.method='POST'; f.action=url;
  const i = document.createElement('input'); i.type='hidden'; i.name='_csrf'; i.value=window.CSRF; f.appendChild(i);
  document.body.appendChild(f); f.submit();
};

// Confirm delete
document.addEventListener('submit', function(e) {
  const form = e.target;
  if (form.dataset.confirm) {
    if (!confirm(form.dataset.confirm)) { e.preventDefault(); return false; }
  }
});
