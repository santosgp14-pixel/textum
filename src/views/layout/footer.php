    </main><!-- /page-content -->
  </div><!-- /main-content -->
</div><!-- /app-wrapper -->

<script src="<?= BASE_URL ?>/js/app.js"></script>
<script>
// Global: async confirm for any form with data-confirm attribute
document.querySelectorAll('form[data-confirm]:not([data-confirm-init])').forEach(form => {
  form.dataset.confirmInit = '1';
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const ok = await Confirm({ title: form.dataset.confirm, icon: '🗑', okText: 'Confirmar' });
    if (ok) form.submit();
  });
});
// Flash alerts: auto dismiss
document.querySelectorAll('.alert[data-autodismiss]').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity .4s';
    el.style.opacity = '0';
    setTimeout(() => el.remove(), 420);
  }, 3200);
});
</script>
</body>
</html>
