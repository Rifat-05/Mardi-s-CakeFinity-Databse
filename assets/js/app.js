// Confirm dialogs for risky actions
document.querySelectorAll('form[data-confirm]').forEach(function (f) {
  f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirm)) e.preventDefault(); });
});
// Copy all generated codes
document.querySelectorAll('[data-copy]').forEach(function (b) {
  b.addEventListener('click', function () {
    var text = Array.from(document.querySelectorAll(b.dataset.copy + ' .code')).map(function (c) { return c.textContent; }).join('\n');
    (navigator.clipboard ? navigator.clipboard.writeText(text) : Promise.reject()).then(function () { b.textContent = 'Copied'; }, function () { alert(text); });
  });
});
// Click an available code to add it to the assign box
document.querySelectorAll('[data-pick]').forEach(function (b) {
  b.addEventListener('click', function () {
    var t = document.querySelector('textarea[name=codes]');
    if (t.value.split(/\s+/).indexOf(b.dataset.pick) === -1) t.value = (t.value.trim() + '\n' + b.dataset.pick).trim();
  });
});
