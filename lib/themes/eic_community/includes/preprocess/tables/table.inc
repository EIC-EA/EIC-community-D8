<?php

/**
 * @file
 * Contains implementation for hook_preprocess_table().
 */

/**
 * Implements hook_preprocess_table().
 */
function eic_community_preprocess_table(array &$variables): void {
  /** @var \Drupal\Core\Render\RendererInterface $renderer */
  $renderer = \Drupal::service('renderer');

  // Assign data to table headers as ECL expects it.
  $ecl_table_header_data = [];
  $variables['header'] = isset($variables['header']) ? $variables['header'] : [];
  foreach ($variables['header'] as &$cell) {
    $cell['label'] = $cell['content'];

    // We need to generate the value of the data-ecl-table-header attribute for
    // each row cell. Table headers may be strings or render arrays, so we count
    // on the inline template render element to do the right thing.
    $cell_header_render_array = [
      '#type' => 'inline_template',
      '#template' => '{{ label|render|striptags }}',
      '#context' => [
        'label' => $cell['content'],
      ],
    ];
    $cell_header_data = $renderer->render($cell_header_render_array);

    // Assign colspan value to the cell, if any. It will be used later.
    if (isset($cell['attributes']['colspan'])) {
      $cell['colspan'] = (int) $cell['attributes']['colspan']->value();
    }

    // Account for colspan values when counting the number of columns.
    // This ensures that header content is is displayed on related columns
    // when the table is displayed on small screen devices.
    $columns_count = isset($cell['colspan']) ? $cell['colspan'] : 1;
    for ($i = 1; $i <= $columns_count; $i++) {
      $ecl_table_header_data[] = $cell_header_data;
    }
  }
  $variables['headers'] = [$variables['header']];

  // Assign data to table rows as ECL expects it.
  foreach ($variables['rows'] as &$row) {
    $index = 0;
    foreach ($row['cells'] as &$cell) {
      $cell['label'] = $cell['content'];
      if (isset($ecl_table_header_data[$index])) {
        $cell['data-ecl-table-header'] = $ecl_table_header_data[$index];
      }
      $index++;
    }
  }
}
