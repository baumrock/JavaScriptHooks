<script>
  // super-simple modal class implementation
  class Modal {
    open(e) {
      UIkit.modal.dialog("<div class='uk-modal-body'>I am a modal</div>");
    }
  }

  // create a new instance
  var modal = new Modal();

  // open modal whenever an element with class 'modal' is clicked
  document.addEventListener("click", (e) => {
    if (!e.target.classList.contains("modal")) return;
    e.preventDefault();
    modal.open(e);
  });
</script>

<button class="modal">Open Modal</button>