<script>
  // hookable modal class implementation
  class Modal {

    // NEW: add three underscores to make this method hookable
    ___open(e) {
      UIkit.modal.dialog("<div class='uk-modal-body'>I am a better modal :)</div>");
    }
  }

  // create a new instance
  // NEW: wrap the instance in ProcessWire.wire() to make it hookable
  var modal = ProcessWire.wire(new Modal());

  // click listener (no changes)
  document.addEventListener("click", (e) => {
    if (!e.target.classList.contains("modal")) return;
    e.preventDefault();
    modal.open(e);
  });
</script>

<button class="modal">Open Modal</button>