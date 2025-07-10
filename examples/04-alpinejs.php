<h2>Alpine.js Example</h2>

<p>This is a basic Alpine.js example showing a counter. In the next example we will make it hookable!</p>

<div show-code>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <div x-data="{ count: 0 }">
    <button @click.prevent="count++">Increment</button>
    <span x-text="count">0</span>
  </div>
</div>