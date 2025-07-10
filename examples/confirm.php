<script>
  ProcessWire.addHookBefore("Modal::open", function(event) {
    // for debugging check your console!
    console.log(event);

    // in the modal class we have open(e) where e is the initial js event
    // we can access this "e" argument via event.arguments(0)
    // and then access the target property, which is the clicked button
    const button = event.arguments(0).target;

    // if the button does not have the class 'confirm' we do not open the modal
    if (!button.classList.contains("confirm")) return;

    // replace the original method's implementation with our own
    // which will prevent the modal from opening
    event.replace = true;

    // show a confirmation dialog
    UIkit.modal.confirm("Click CANCEL to dismiss this confirmation!").then(
      // confirm callback
      () => {
        button.classList.remove("confirm");
        button.click();
        button.classList.add("confirm");
      },
      // cancel callback
      () => {
        console.log("cancelled");
      }
    );
  });
</script>

<button class='modal confirm'>Confirm Modal</button>