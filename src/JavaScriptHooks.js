// create global ProcessWire object if it doesn't exist
// This makes it possible to use ProcessWire hooks in the frontend (standalone)
if (typeof ProcessWire == "undefined") ProcessWire = {};

// function for custom scope to not pollute global namespace
(() => {
  // ##### Public ProcessWire API #####
  ProcessWire.addHookAfter = addHookAfter;
  ProcessWire.addHookBefore = addHookBefore;
  ProcessWire.wire = wire;

  // ##### Internal code to support hooks #####

  // hooks storage
  const hooks = {
    after: {},
    before: {},
  };

  // HookEvent class to use in hooks
  // eg event.arguments() or event.return
  class HookEvent {
    constructor(data) {
      this.object = data.object;
      this.args = data.arguments;
      this.replace = false;
      this.return = data.return;
    }

    // dynamic arguments getter
    // this is to access hook arguments either via event.arguments
    // or event.arguments(0) or event.arguments(1) etc
    get arguments() {
      const self = this;
      return new Proxy(
        function () {
          // requested as property, eg event.arguments
          // return the arguments array
          if (arguments.length === 0) return self.args;

          // requested as method, eg event.arguments(0)
          // return the requested array element
          if (arguments.length === 1) return self.args[arguments[0]];

          // requested as method to set a value, eg event.arguments(0, "foo")
          // set the requested array element
          if (arguments.length === 2) self.args[arguments[0]] = arguments[1];
        },
        {
          get(target, prop) {
            if (prop === "length") return self.args.length;
            const index = parseInt(prop, 10);
            return isNaN(index) ? undefined : self.args[index];
          },
          set(target, prop, value) {
            const index = parseInt(prop, 10);
            if (!isNaN(index)) {
              self.args[index] = value;
              return true;
            }
            return false;
          },
        }
      );
    }
  }

  // addHookAfter
  function addHookAfter(name, fn, priority = 100) {
    const _hooks = hooks.after[name] || [];
    _hooks.push({ name, fn, priority });
    _hooks.sort((a, b) => a.priority - b.priority);
    hooks.after[name] = _hooks;
  }

  // addHookBefore
  function addHookBefore(name, fn, priority = 100) {
    const _hooks = hooks.before[name] || [];
    _hooks.push({ name, fn, priority });
    _hooks.sort((a, b) => a.priority - b.priority);
    hooks.before[name] = _hooks;
  }

  // executeHooks
  // this executes all attached before and after hooks
  function executeHooks(type, hookName, hookEvent) {
    const _hooks = hooks[type][hookName] || [];
    for (let i = 0; i < _hooks.length; i++) {
      try {
        // get the hook and execute its "fn" callback
        // send the hookEvent to this callback to support our familiar syntax:
        // addHookAfter('...', function(event) { ... });
        _hooks[i].fn(hookEvent);

        // if the callback has set replace to true we stop here
        // as far as I know only before hooks can replace following hooks
        // so we do this only for before hooks
        if (hookEvent.replace && type === "before") break;
      } catch (error) {
        console.error(`Error in ${type} hook for ${hookName}:`, error);
        console.log("Hook:", _hooks[i]);
        console.log("HookEvent:", hookEvent);
      }
    }
  }

  // wire() method to apply HookHandler to an object
  // this is all we need to make any object hookable :)
  function wire(object, name = null, noProxy = false) {
    // check if object is an instance of a class
    // if it is, throw an error
    if (object.constructor.name !== "Object") {
      name = object.constructor.name;
    }

    // if the object is not a class it will have name "Object"
    // in that case we throw an error so that the developer provides a name
    // that we can use for the hook identifier like "Foo::hello" or otherwise
    // all generic objects would have the same hook name "Object::hello"
    if (!name) {
      throw new Error("Please provide a name: ProcessWire.wire(object, name)");
    }

    // for generic objects we always use the non-proxy version
    if (object.constructor.name === "Object") noProxy = true;

    // for classes we use the proxy
    // for everything else we use the non-proxy version (alpine js, plain objects)
    if (noProxy) return wireNoProxy(object, name);
    else return wireProxy(object, name);
  }

  // wire object without using proxies
  // This is for situations where proxies might interfere with other libraries
  // for example when having an alpine component that is itself a proxy.
  // When using this method we simply look for methods with ___ prefix and
  // add the corresponding non-prefixed methods to the object that will take
  // care of executing before and after hooks when called.
  function wireNoProxy(object, name) {
    // loop all properties of the object
    // and add corresponding methods instead of methods with ___ prefix
    let props = Object.getOwnPropertyDescriptors(object);
    for (let key in props) {
      // non prefixed props stay untouched
      if (!key.startsWith("___")) continue;

      // get the original method
      // we only support hookable methods at this point, no properties
      const originalMethod = props[key].value;
      if (typeof originalMethod !== "function") continue;

      // generate new method name, eg MyClass::myMethod
      let newMethod = key.slice(3);
      const hookName = `${name}::${newMethod}`;

      // add the new method to the object
      props[newMethod] = {
        value: function (...args) {
          // Create hookEvent object using HookEvent class
          const hookEvent = new HookEvent({
            arguments: args,
            object: this,
          });

          // Execute before hooks
          executeHooks("before", hookName, hookEvent);

          // if event.replace is true we do not call the original method
          if (hookEvent.replace) return hookEvent.return;

          // Call the original method
          hookEvent.return = originalMethod.apply(this, hookEvent.arguments());

          // Execute after hooks
          executeHooks("after", hookName, hookEvent);

          return hookEvent.return;
        },
      };
    }

    // create the new object and return it
    return Object.create(Object.getPrototypeOf(object), props);
  }

  // add hooks via proxies

  // HookHandler is a Proxy that intercepts every method call
  // and delegates it to the corresponding hookable method, if it
  // exists. For example calling .foo() will delegate to ___foo()
  const HookHandler = {
    get: function (data, prop) {
      const object = data.object;
      if (typeof prop !== "string") return object[prop];

      // build hook selector
      let hookObjectName = data.name;
      if (!hookObjectName) hookObjectName = object.constructor.name;
      const selector = `${hookObjectName}::${prop}`;
      // console.log(selector);

      // if prop starts with ___ we return the original value
      if (prop.startsWith("___")) return object[prop];

      // if ___prop is not defined we return the original value
      if (typeof object[`___${prop}`] === "undefined") return object[prop];

      // if prop does not start with ___ we return a function that executes
      // hooks and the original method
      return function (...args) {
        // Create hookEvent object using HookEvent class
        const hookEvent = new HookEvent({
          arguments: args,
          object: this,
        });

        // Execute before hooks
        executeHooks("before", selector, hookEvent);

        // if event.replace is true we do not call the original method
        if (hookEvent.replace) return hookEvent.return;

        // Call the original method
        hookEvent.return = object[`___${prop}`].apply(
          this,
          hookEvent.arguments()
        );

        // Execute after hooks
        executeHooks("after", selector, hookEvent);

        return hookEvent.return;
      };
    },
  };

  // wire object using proxies
  // very simple, as the HookHandler does all the work
  function wireProxy(object, name) {
    return new Proxy(
      {
        object: object,
        name: name,
      },
      HookHandler
    );
  }
})();
