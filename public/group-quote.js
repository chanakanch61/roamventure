(() => {
 'use strict';
 // Invoice form: quick installment presets and the label of the value field.
 const invoiceForm = document.getElementById('gi-form');
 if (invoiceForm) {
  const type = invoiceForm.querySelector('[data-gi-type]');
  const value = invoiceForm.querySelector('[data-gi-value]');
  const sync = () => {
   if (!type) return;
   invoiceForm.querySelector('[data-gi-value-label]').hidden = type.value === 'remaining';
   invoiceForm.querySelector('[data-gi-value-text]').textContent = type.value === 'amount' ? 'จำนวนเงิน (บาท รวม VAT)' : 'เปอร์เซ็นต์ (%)';
  };
  invoiceForm.querySelectorAll('[data-gi-preset]').forEach(button => button.addEventListener('click', () => {
   const [kind, amount, label] = button.dataset.giPreset.split('|');
   type.value = kind; value.value = amount; invoiceForm.elements.label.value = label;
   sync();
  }));
  if (type) type.addEventListener('change', sync);
  sync();
 }
 // Saved item templates (invoice rows, the booking's extra-charge form): picking one fills the line;
 // "other" shows the text box, keeping what was there to edit. The text box is what is posted.
 document.addEventListener('change', event => {
  const pick = event.target.closest('[data-gi-pick]');
  if (!pick) return;
  const line = pick.closest('[data-gq-row],[data-gi-line]');
  const text = line.querySelector('[data-gi-desc]');
  if (pick.value === '__other') { text.hidden = false; text.focus(); text.select(); return; }
  text.hidden = true;
  text.value = pick.value;
  const option = pick.selectedOptions[0];
  if (!pick.value || !option) return;
  const unit = line.querySelector('[data-gi-unit],input[name$="[unit]"]');
  if (unit && option.dataset.unit) unit.value = option.dataset.unit;
  const price = line.querySelector('[data-gq-price]');
  if (price) price.value = option.dataset.price || '0';
  const disb = line.querySelector('[data-gi-disb]');
  if (disb) disb.checked = option.dataset.disb === '1';
  // Let totals previews recalculate.
  if (price) price.dispatchEvent(new Event('input', {bubbles: true}));
 }, true);
 // Invoice of other charges: line items, disbursements outside VAT, totals preview. The server recalculates on save.
 const charges = document.getElementById('gi-charges');
 if (charges && invoiceForm) {
  const rows = charges.querySelector('[data-gq-rows]');
  const template = document.getElementById('gq-row-template');
  const out = name => invoiceForm.querySelector('[data-gq-' + name + ']');
  const number = input => { const v = parseFloat(String(input?.value || '').replace(/[,\s]/g, '')); return Number.isFinite(v) && v > 0 ? v : 0; };
  const round = v => Math.round((v + Number.EPSILON) * 100) / 100;
  const fmt = v => v.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
  let next = rows.querySelectorAll('[data-gq-row]').length;
  const update = () => {
   let taxable = 0, exempt = 0;
   rows.querySelectorAll('[data-gq-row]').forEach(row => {
    const amount = round(number(row.querySelector('[data-gq-qty]')) * round(number(row.querySelector('[data-gq-price]'))));
    row.querySelector('[data-gq-amount]').textContent = fmt(amount);
    if (row.querySelector('[data-gi-disb]').checked) exempt += amount; else taxable += amount;
   });
   taxable = round(taxable); exempt = round(exempt);
   const mode = out('vat-mode').value;
   const rate = number(out('vat-rate')) || 7;
   let vat = 0, subtotal = taxable;
   if (mode === 'excluded') vat = round(taxable * rate / 100);
   else if (mode === 'included') { vat = round(taxable * rate / (100 + rate)); subtotal = round(taxable - vat); }
   const total = round(subtotal + vat + exempt);
   const whtOn = out('wht-on').checked;
   const wht = whtOn ? round(subtotal * (number(out('wht-rate')) || 3) / 100) : 0;
   out('subtotal').textContent = fmt(subtotal);
   out('vat').textContent = mode === 'none' ? '—' : fmt(vat) + (mode === 'included' ? ' (รวมในราคา)' : '');
   invoiceForm.querySelector('[data-gi-exempt]').textContent = fmt(exempt);
   out('total').textContent = '฿' + fmt(total);
   out('wht').textContent = '-' + fmt(wht);
   out('net').textContent = '฿' + fmt(round(total - wht));
   invoiceForm.querySelectorAll('[data-gq-wht-row]').forEach(el => { el.hidden = !whtOn; });
   out('wht-rate-label').hidden = !whtOn;
   out('vat-rate').closest('label').hidden = mode === 'none';
  };
  const add = charges.querySelector('[data-gq-add]');
  if (add) add.addEventListener('click', () => {
   rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__i__', String(next++)));
   rows.lastElementChild.querySelector('input').focus();
   update();
  });
  rows.addEventListener('click', event => {
   const button = event.target.closest('[data-gq-remove]');
   if (!button) return;
   const all = rows.querySelectorAll('[data-gq-row]');
   if (all.length === 1) {
    all[0].querySelectorAll('input').forEach(i => { if (i.type === 'checkbox') i.checked = false; else i.value = i.matches('[data-gq-qty]') ? '1' : (i.matches('[data-gq-price]') ? '0' : ''); });
    const pick = all[0].querySelector('[data-gi-pick]');
    if (pick) { pick.value = ''; all[0].querySelector('[data-gi-desc]').hidden = true; }
   } else button.closest('[data-gq-row]').remove();
   update();
  });
  invoiceForm.addEventListener('input', update);
  invoiceForm.addEventListener('change', update);
  update();
 }
 // Template list (/admin/group-invoices/templates): add, remove and reorder rows; row order is the saved order.
 const templates = document.getElementById('gi-templates');
 if (templates) {
  const rows = templates.querySelector('[data-gq-rows]');
  const template = document.getElementById('gq-row-template');
  let next = rows.querySelectorAll('[data-gq-row]').length;
  templates.querySelector('[data-gq-add]').addEventListener('click', () => {
   rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__i__', String(next++)));
   rows.lastElementChild.querySelector('input').focus();
  });
  rows.addEventListener('click', event => {
   const row = event.target.closest('[data-gq-row]');
   if (!row) return;
   if (event.target.closest('[data-gq-remove]')) {
    if (rows.children.length === 1) row.querySelectorAll('input').forEach(i => { if (i.type === 'checkbox') i.checked = false; else i.value = ''; });
    else row.remove();
   } else if (event.target.closest('[data-gt-up]') && row.previousElementSibling) row.previousElementSibling.before(row);
   else if (event.target.closest('[data-gt-down]') && row.nextElementSibling) row.nextElementSibling.after(row);
  });
 }
 // Quotation form: add / remove line items and preview totals. The server recalculates everything on save.
 const form = document.getElementById('gq-form');
 if (!form) return;
 const rows = form.querySelector('[data-gq-rows]');
 const template = document.getElementById('gq-row-template');
 const out = name => form.querySelector('[data-gq-' + name + ']');
 const number = input => { const v = parseFloat(String(input?.value || '').replace(/[,\s]/g, '')); return Number.isFinite(v) && v > 0 ? v : 0; };
 const round = v => Math.round((v + Number.EPSILON) * 100) / 100;
 const fmt = v => v.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
 let next = rows.querySelectorAll('[data-gq-row]').length;

 const update = () => {
  const whtOn = out('wht-on').checked;
  const whtRate = number(out('wht-rate')) || 3;
  const gross = whtOn && out('gross').checked;
  // Company practice: the withholding rate is added straight onto the typed unit price, then × quantity.
  const unit = price => gross ? round(price * (1 + whtRate / 100)) : round(price);
  let sum = 0;
  rows.querySelectorAll('[data-gq-row]').forEach(row => {
   const price = unit(number(row.querySelector('[data-gq-price]')));
   const amount = round(number(row.querySelector('[data-gq-qty]')) * price);
   const cell = row.querySelector('[data-gq-amount]');
   cell.textContent = fmt(amount);
   if (gross) { const note = document.createElement('small'); note.textContent = '@ ' + fmt(price); cell.append(note); }
   sum += amount;
  });
  out('price-head').textContent = gross ? 'ราคาฐาน / หน่วย' : 'ราคา / หน่วย';
  sum = round(sum);
  const discount = Math.min(round(number(out('discount'))), sum);
  const after = round(sum - discount);
  const mode = out('vat-mode').value;
  const rate = number(out('vat-rate')) || 7;
  let vat = 0, subtotal = after, total = after;
  if (mode === 'excluded') { vat = round(after * rate / 100); total = round(after + vat); }
  else if (mode === 'included') { vat = round(after * rate / (100 + rate)); subtotal = round(total - vat); }
  out('sum').textContent = fmt(sum);
  out('discount-out').textContent = discount ? '-' + fmt(discount) : '0.00';
  out('subtotal').textContent = fmt(subtotal);
  out('vat').textContent = mode === 'none' ? '—' : fmt(vat) + (mode === 'included' ? ' (รวมในราคา)' : '');
  out('total').textContent = '฿' + fmt(total);
  const wht = whtOn ? round(subtotal * whtRate / 100) : 0;
  out('wht').textContent = '-' + fmt(wht);
  out('net').textContent = '฿' + fmt(round(total - wht));
  form.querySelectorAll('[data-gq-wht-row]').forEach(el => { el.hidden = !whtOn; });
  out('wht-rate-label').hidden = !whtOn;
  out('gross-label').hidden = !whtOn;
  out('gross-hint').hidden = !whtOn;
  out('vat-rate').closest('label').hidden = mode === 'none';
 };

 form.querySelector('[data-gq-add]').addEventListener('click', () => {
  const html = template.innerHTML.replaceAll('__i__', String(next++));
  rows.insertAdjacentHTML('beforeend', html);
  rows.lastElementChild.querySelector('input').focus();
  update();
 });
 rows.addEventListener('click', event => {
  const button = event.target.closest('[data-gq-remove]');
  if (!button) return;
  const all = rows.querySelectorAll('[data-gq-row]');
  if (all.length === 1) { all[0].querySelectorAll('input').forEach(i => { i.value = i.matches('[data-gq-qty]') ? '1' : (i.matches('[data-gq-price]') ? '0' : ''); }); }
  else button.closest('[data-gq-row]').remove();
  update();
 });
 form.addEventListener('input', update);
 form.addEventListener('change', update);
 update();
})();
