(function () {
  "use strict";

  function initRRDOrderSubmission() {
    // Get order ID from localized data
    const orderId = rrdOrderSubmission?.orderId;

    if (!orderId) {
      console.error("RRD: Order ID not found");
      return;
    }

    const previewBtn = document.getElementById("rrd-preview-button");
    const submitBtn = document.getElementById("rrd-submit-button");
    const loading = document.getElementById("rrd-loading");

    // Collapsible headers
    document.querySelectorAll(".rrd-collapsible-header").forEach((header) => {
      header.addEventListener("click", function () {
        this.nextElementSibling.classList.toggle("open");
      });
    });

    // Preview button
    if (previewBtn) {
      previewBtn.addEventListener("click", function () {
        showPayloadPreview(orderId);
      });
    }

    // Submit button
    if (submitBtn) {
      submitBtn.addEventListener("click", function () {
        if (confirm(rrdOrderSubmission.confirmText)) {
          submitOrderToRRD(orderId);
        }
      });
    }

    function showPayloadPreview(id) {
      loading.style.display = "block";
      fetch(rrdOrderSubmission.ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          action: "rrd_preview_payload",
          order_id: id,
          nonce: rrdOrderSubmission.previewNonce,
        }),
      })
        .then((r) => r.json())
        .then((data) => {
          loading.style.display = "none";
          console.log("Preview response:", data);
          if (data.success) {
            alert("Payload:\n\n" + JSON.stringify(data.data.payload, null, 2));
          } else {
            alert(
              "Error: " +
                (data.data?.message || data.message || "Unknown error"),
            );
          }
        })
        .catch((err) => {
          loading.style.display = "none";
          console.error("Preview error:", err);
          alert("Error: " + err.message);
        });
    }

    function submitOrderToRRD(id) {
      loading.style.display = "block";
      fetch(rrdOrderSubmission.ajaxUrl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          action: "rrd_submit_order",
          order_id: id,
          nonce: rrdOrderSubmission.submitNonce,
        }),
      })
        .then((r) => r.json())
        .then((data) => {
          loading.style.display = "none";
          console.log("Submit response:", data);
          if (data.success) {
            alert("Order submitted successfully. Refreshing...");
            location.reload();
          } else {
            alert(
              "Error: " +
                (data.data?.message || data.message || "Unknown error"),
            );
          }
        })
        .catch((err) => {
          loading.style.display = "none";
          console.error("Submit error:", err);
          alert("Error: " + err.message);
        });
    }
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRRDOrderSubmission);
  } else {
    initRRDOrderSubmission();
  }
})();
