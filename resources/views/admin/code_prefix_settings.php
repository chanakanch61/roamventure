<fieldset class="contact-settings">
 <legend>ตัวย่อรหัส</legend>
 <label for="code-prefix">ตัวย่อสำหรับรหัสโปรแกรมและเลขอ้างอิงการจอง
  <input id="code-prefix" name="code_prefix" required maxlength="10" pattern="[A-Za-z][A-Za-z0-9]{0,9}" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="เช่น RV" value="<?= e($settings['code_prefix'] ?? code_prefix($settings)) ?>" aria-describedby="code-prefix-help">
 </label>
 <small id="code-prefix-help">ใช้ตัวอักษรอังกฤษและตัวเลข 1–10 ตัว โดยขึ้นต้นด้วยตัวอักษรอังกฤษ ระบบจะบันทึกเป็นตัวพิมพ์ใหญ่</small>
 <div class="notice" aria-live="polite">
  ตัวอย่างรหัสโปรแกรม: <b><span data-prefix-preview><?= e(code_prefix($settings)) ?></span>-123</b><br>
  ตัวอย่างเลขอ้างอิงการจอง: <b><span data-prefix-preview><?= e(code_prefix($settings)) ?></span>-<?= e(date('ymd')) ?>-A1B2C3D4</b>
 </div>
 <small>เมื่อบันทึก ระบบจะเปลี่ยนตัวย่อรหัสโปรแกรมทั้งหมด โดยคงเลขรหัสเดิมไว้ ลิงก์เดิมยังเปิดได้ ส่วนเลขอ้างอิงการจองจะเปลี่ยนเฉพาะรายการใหม่</small>
</fieldset>
<script>
(() => {
 const input = document.getElementById('code-prefix');
 input.addEventListener('input', () => {
  const prefix = input.value.trim().toUpperCase();
  document.querySelectorAll('[data-prefix-preview]').forEach(el => { el.textContent = /^[A-Z][A-Z0-9]{0,9}$/.test(prefix) ? prefix : '…'; });
 });
})();
</script>
