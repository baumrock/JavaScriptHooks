<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 10.07.2025
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class JavaScriptHooks extends WireData implements Module, ConfigurableModule
{
  public function ready()
  {
    $this->loadJsFile();
    $this->devtools();
  }

  private function code(string $file): string
  {
    $markup = wire()->files->render(__DIR__ . "/examples/{$file}");
    return "<pre class='uk-margin-remove-top'><code>"
      . wire()->sanitizer->entities($markup)
      . "</code></pre>"
      . $markup;
  }

  /**
   * Auto-compile all JS assets via RockDevTools
   * @return void
   * @throws WireException
   */
  private function devtools(): void
  {
    if (!wire()->config->rockdevtools) return;
    try {
      rockdevtools()
        ->assets()
        ->js()
        ->add(__DIR__ . '/src/**.js')
        ->save(
          __DIR__ . '/dst/JavaScriptHooks.min.js',
          // minify: false,
        );
    } catch (\Throwable $th) {
      bd($th->getMessage());
    }
  }

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    $lib = '
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/styles/atom-one-dark.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.11.1/highlight.min.js"></script>
      <script>hljs.highlightAll();</script>
      ';
    if (!wire()->input->get('solution')) {
      $inputfields->add([
        'type' => 'markup',
        'label' => 'The Problem',
        'value' => "$lib
          <p>Imagine ProcessWire's core had a JS modal class like this:</p>

          {$this->code('modal-plain.php')}

          <p>Now imagine you wanted to write a plugin that only allows to open the modal after a confirmation. We can use event listeners to achieve this, right?</p>

          {$this->code('confirm-plain.php')}

          <p>As you can see we have now opened two modals and there is no easy way to intercept the open() implementation of our modal class. Hooks to the rescue!</p>

          <p><a href='./edit?name=JavaScriptHooks&solution=1'>Show The Solution</a></p>
        ",
        'icon' => 'code',
      ]);
    } else {
      $inputfields->add([
        'type' => 'markup',
        'label' => 'The Solution',
        'value' => "$lib
          <p>Now let's make our class hookable with just a few very simple changes:</p>

          {$this->code('modal.php')}

          <p>So far everything works as before, but now we can add hooks to our modal class:</p>

          {$this->code('confirm.php')}

          <p><a href='./edit?name=JavaScriptHooks'>Back To The Problem</a></p>
        ",
        'icon' => 'code',
      ]);
    }

    return $inputfields;
  }

  private function loadJsFile(): void
  {
    if (wire()->config->ajax) return;
    if (wire()->config->external) return;
    $url = wire()->config->urls($this);
    wire()->config->scripts->add($url . "dst/JavaScriptHooks.min.js");
  }
}
