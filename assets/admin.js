(function () {
  var btn = document.getElementById("max-qr-test-btn");
  var status = document.getElementById("max-qr-test-status");
  if (!btn || !window.maxQrCodeAdmin) return;

  btn.addEventListener("click", function () {
    status.textContent = maxQrCodeAdmin.i18n.testing;
    status.className = "max-qr-status";
    btn.disabled = true;

    var body = new FormData();
    body.append("action", "max_qr_code_test_connection");
    body.append("nonce", maxQrCodeAdmin.nonce);

    fetch(maxQrCodeAdmin.ajaxUrl, { method: "POST", body: body, credentials: "same-origin" })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        status.textContent = (data && data.message) || maxQrCodeAdmin.i18n.failed;
        status.className = "max-qr-status " + (data && data.ok ? "is-ok" : "is-err");
      })
      .catch(function () {
        status.textContent = maxQrCodeAdmin.i18n.failed;
        status.className = "max-qr-status is-err";
      })
      .finally(function () {
        btn.disabled = false;
      });
  });
})();
