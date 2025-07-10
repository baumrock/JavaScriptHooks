<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 10.07.2025
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class ProcessJavaScriptHooks extends Process
{
  public function execute()
  {
    $this->headline('JavaScriptHooks Tests/Examples');
    $this->browserTitle('JavaScriptHooks Tests/Examples');
    /** @var InputfieldForm $form */
    $form = $this->wire->modules->get('InputfieldForm');
    $file = wire()->input->get('file', 'string');

    $form->add([
      'type' => 'markup',
      'label' => 'Navigation',
      'icon' => 'sitemap',
      'value' => $this->renderNav(),
      'collapsed' => $file ? true : false,
    ]);

    if ($file) {
      $form->add([
        'type' => 'markup',
        'label' => 'Example',
        'icon' => 'code',
        'value' => $this->renderExample($file),
      ]);
    }

    return $form->render();
  }

  private function renderExample(string $file): string
  {
    return wire()->files->render(__DIR__ . '/examples/' . $file . '.php')
      . wire()->files->render(__DIR__ . '/examples/assets.php');
  }

  private function renderNav(): string
  {
    $files = glob(__DIR__ . '/examples/*.php');
    $table = '<table class="uk-table uk-table-striped uk-table-small uk-margin-remove">';
    foreach ($files as $file) {
      $base = basename($file, '.php');
      if ($base == 'assets') continue;
      $table .= '<tr>';
      $table .= '<td><a href="?file=' . $base . '">' . $base . '</a></td>';
      $table .= '</tr>';
    }
    $table .= '</table>';
    return $table;
  }
}
