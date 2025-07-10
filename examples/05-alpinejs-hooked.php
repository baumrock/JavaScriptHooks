<h2>Alpine.js HookedExample</h2>

<p>In this example we create a hookable Alpine.js component!</p>

<div show-code>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <div x-data="MyCounter">
    <button @click.prevent="decrement()">-</button>
    <span x-text="count">0</span>
    <button @click.prevent="increment()">+</button>
  </div>
  <script>
    document.addEventListener('alpine:init', () => {
      Alpine.data('MyCounter', (el) => {
        return ProcessWire.wire({
            count: 0,

            init() {
              this.$watch('count', this.countChanged);
            },

            ___increment() {
              this.count++;
            },

            ___decrement() {
              this.count--;
            },

            countChanged(value) {
              UIkit.notification(`Count is now ${value}`, 'success');
            },
          },
          // for plain objects we need to define the name of the component
          // this is what will be used for the hook selector MyCounter::...
          'MyCounter'
        );
      })
    })
  </script>
</div>

<p>Now let's add a hook to prevent counts below 0 and above 5!</p>

<div show-code>
  <script>
    ProcessWire.addHookBefore('MyCounter::increment', (event) => {
      if (event.object.count >= 5) {
        UIkit.notification('Count cannot be greater than 5', 'danger');
        event.replace = true;
      }
    });
    ProcessWire.addHookBefore('MyCounter::decrement', (event) => {
      if (event.object.count <= 0) {
        UIkit.notification('Count cannot be less than 0', 'danger');
        event.replace = true;
      }
    });
  </script>
</div>