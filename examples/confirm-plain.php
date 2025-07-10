<script>
  document.addEventListener("click", (e) => {
    const el = e.target;

    // only intercept modal clicks
    if (!el.classList.contains("modal")) return;

    // only intercept if confirm class exists
    if (!el.classList.contains("confirm")) return;

    // show a confirmation dialog
    UIkit.modal.confirm("Click CANCEL to dismiss this confirmation!");
  });
</script>

<button class='modal confirm'>Confirm Modal</button>