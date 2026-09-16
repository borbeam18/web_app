  </div>
</main>
<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    this.parentElement.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
  });
});
document.querySelectorAll('.modal-backdrop').forEach(m => {
  m.addEventListener('click', e => { if (e.target === m) m.classList.add('hidden'); });
});
function openModal(id) { document.getElementById(id).classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
function closeModal(id) { document.getElementById(id).classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
function openMobileMenu() {
  document.getElementById('mainSidebar')?.classList.add('mobile-open');
  document.getElementById('mobileOverlay')?.classList.add('visible');
  document.body.classList.add('overflow-hidden');
}
function closeMobileMenu() {
  document.getElementById('mainSidebar')?.classList.remove('mobile-open');
  document.getElementById('mobileOverlay')?.classList.remove('visible');
  document.body.classList.remove('overflow-hidden');
}
document.querySelectorAll('#mainSidebar a').forEach(link => link.addEventListener('click', closeMobileMenu));
window.addEventListener('resize', () => {
  if (window.innerWidth >= 768) closeMobileMenu();
});
function showToast(msg) {
  const t = document.getElementById('toast');
  if (!t) return;
  document.getElementById('toastMsg').textContent = msg;
  t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 2500);
}
</script>
<div id="toast" class="fixed bottom-6 right-6 bg-green-600 text-white px-5 py-3 rounded-lg shadow-lg hidden z-50 fade-in flex items-center gap-2">
  <span id="toastMsg">สำเร็จ</span>
</div>
</body>
</html>