# Bringing the Power of Hooks to JavaScript in ProcessWire

This document outlines a proposal and implementation for a core JavaScript hooks system in ProcessWire. It mirrors the powerful, flexible, and familiar hook system from ProcessWire's PHP core, enabling developers to write cleaner, more modular, and vastly more extensible client-side code.

## The "Why": Beyond Event Listeners

Modern JavaScript is built around events. While powerful, the traditional event listener model has limitations, especially in a dynamic and extensible CMS environment like ProcessWire.

-   **Limited Control:** Event listeners can't easily modify the *behavior* or *return values* of the functions they are attached to. You can react to an event, but you can't easily change what the original code does.
-   **Code Coupling:** Customizing behavior often requires direct modification of existing objects or fragile DOM manipulations, leading to code that is tightly coupled and prone to breaking with updates.
-   **Complexity:** Implementing cancellable operations or modifying arguments before a function executes can become complex and verbose.

**Hooks solve these problems.** By providing `before`, `after`, and `replace` capabilities, they offer granular control over the execution flow of any method, making our JavaScript architecture as extensible as our PHP architecture.

## The "Aha!" Moment: Hello-World Example

Imagine you want to add a "maximize" button to every modal window opened via `ProcessWire.modal.open()`.

Without hooks, this is a nightmare. You might try a `setInterval` to watch for new modals in the DOM, but that's inefficient and unreliable. You could try to wrap the `ProcessWire.modal.open` function, but that's risky and could conflict with other scripts doing the same thing.

**With JavaScript hooks, it's simple, clean, and robust.**

Let's imagine the core `ProcessWire.modal` object was made hookable:

```javascript
// In ProcessWire's core admin JS (simplified example)
ProcessWire.modal = ProcessWire.wire({
  // The hookable method is prefixed with "___"
  ___open(url, options) {
    // ... core logic to create and open the modal ...
    const $modal = $(`#ProcessWireModal`);
    return $modal; // returns the modal element
  }
});
```

Now, a third-party module developer can easily and reliably add a maximize button to **all** modals with an `after` hook:

```javascript
// In /site/templates/admin.js or a custom module's JS file
ProcessWire.addHookAfter("modal::open", (event) => {
  // event.return is the value from the original method
  const $modal = event.return;
  if(!$modal) return;

  // Add our button to the modal's header
  const $button = $("<button>Max</button>").css({
      position: 'absolute',
      top: '10px',
      right: '50px',
      zIndex: 1000
  });

  $modal.find(".ui-dialog-titlebar").append($button);

  $button.on("click", function() {
    // ... logic to maximize the modal ...
    alert("Modal maximized!");
  });
});
```

This is the power of JavaScript hooks. The module developer can now add functionality without touching the core `ProcessWire.modal` object. The code is decoupled, easy to understand, and will not break if the internal implementation of `modal.open()` changes, as long as the hookable method signature remains.

## How It Works

The system is designed to be intuitive for ProcessWire developers.

1.  **`ProcessWire.wire(object, [name])`**: This is the heart of the system. It takes any object or class instance and returns a "wired" version of it that is hookable. Hookable methods are identified by a `___` prefix.
2.  **`addHookBefore(selector, callback)`**: Adds a hook that runs *before* the original method. You can modify arguments or even prevent the original method from running entirely.
3.  **`addHookAfter(selector, callback)`**: Adds a hook that runs *after* the original method. You can read or modify the return value.
4.  **`event` object**: The callback function receives an `event` object, just like in PHP, with familiar properties:
    *   `event.arguments`: Get or set arguments passed to the method.
    *   `event.return`: Get or set the return value (in `after` hooks).
    *   `event.replace = true`: Prevent the original method and subsequent `before` hooks from executing.

## Usage Guide

### 1. Making an Object or Class Hookable

To make methods on an object or class hookable, you simply prefix them with `___` and wrap the instance with `ProcessWire.wire()`.

**With a Plain Object:**

```javascript
const myGreeter = ProcessWire.wire({
  ___sayHello(name) {
    return `Hello, ${name}!`;
  }
}, "Greeter"); // A name is required for plain objects

console.log(myGreeter.sayHello("World")); // Outputs: Hello, World!
```

**With an ES6 Class:**

The class name is automatically used, so you don't need to provide it.

```javascript
class MyGreeter {
  ___sayHello(name) {
    return `Hello, ${name}!`;
  }
}

const myGreeter = ProcessWire.wire(new MyGreeter());

console.log(myGreeter.sayHello("World")); // Outputs: Hello, World!
```

### 2. Adding Hooks

The selector is a string combining the object name and the method name (without the `___` prefix).

**Modifying the Return Value (`addHookAfter`)**

```javascript
ProcessWire.addHookAfter("MyGreeter::sayHello", (event) => {
  // Let's shout instead
  event.return = event.return.toUpperCase();
});

console.log(myGreeter.sayHello("World")); // Outputs: HELLO, WORLD!
```

**Modifying Arguments (`addHookBefore`)**

```javascript
ProcessWire.addHookBefore("MyGreeter::sayHello", (event) => {
  // Intercept the name and change it
  event.arguments[0] = "ProcessWire";
});

console.log(myGreeter.sayHello("World")); // Outputs: Hello, ProcessWire!
```

**Replacing the Original Method (`addHookBefore`)**

```javascript
ProcessWire.addHookBefore("MyGreeter::sayHello", (event) => {
  // Completely prevent the original method from running
  event.replace = true;
  event.return = "Hooks are powerful!";
});

console.log(myGreeter.sayHello("World")); // Outputs: Hooks are powerful!
```

## A Path to a More Extensible Future

Integrating a JavaScript hook system into the ProcessWire core would be a monumental step forward for client-side development. It empowers module authors to build more deeply integrated, creative, and robust solutions while ensuring their code remains clean, decoupled, and future-proof. This is a proven concept that has been the bedrock of ProcessWire's PHP architecture for years; it's time to bring that same power and elegance to the browser.