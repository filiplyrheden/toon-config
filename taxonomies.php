<?php

/**
 * Register custom taxonomies from cpt-toon/taxonomies.toon in the active theme.
 *
 * File format (columnar toon table without a leading key name):
 *
 *   [N]{name,label,singular,slug,post_types,hierarchical,show_admin_column}:
 *     style,Styles,Style,style,item,1,1
 *
 * Multiple post types must be wrapped in double-quotes:
 *
 *   genre,Genres,Genre,genre,"book,movie",1,1
 *
 * Each non-empty line after the header is one taxonomy row.
 */

add_action('init', 'taxonomies_register_from_toon');

function taxonomies_register_from_toon()
{
  $toon_path = get_template_directory() . '/cpt-toon/taxonomies.toon';

  if (!file_exists($toon_path)) {
    return;
  }

  $raw = file_get_contents($toon_path);
  if ($raw === false || trim($raw) === '') {
    return;
  }

  $lines = array_values(array_filter(
    explode("\n", $raw),
    fn($l) => trim($l) !== ''
  ));

  if (empty($lines)) {
    return;
  }

  // Parse header: [N]{col1,col2,...}:
  if (!preg_match('/^\[(\d+)\]\{([^}]+)\}:\s*$/', trim($lines[0]), $m)) {
    return;
  }

  $cols = array_map('trim', explode(',', $m[2]));
  $rows = array_slice($lines, 1);

  foreach ($rows as $row_line) {
    $values = toon_parse_csv_row(trim($row_line));

    if (count($values) !== count($cols)) {
      continue;
    }

    $entry = array_combine($cols, $values);

    if (empty($entry['name']) || empty($entry['label'])) {
      continue;
    }

    $name     = sanitize_key($entry['name']);
    $label    = $entry['label'];
    $singular = !empty($entry['singular']) ? $entry['singular'] : $label;
    $slug     = !empty($entry['slug']) ? $entry['slug'] : $name;

    $post_types = isset($entry['post_types']) && $entry['post_types'] !== ''
      ? array_map('sanitize_key', array_map('trim', explode(',', $entry['post_types'])))
      : [];

    $hierarchical      = !empty($entry['hierarchical']);
    $show_admin_column = !empty($entry['show_admin_column']);

    $labels = [
      'name'              => $label,
      'singular_name'     => $singular,
      'search_items'      => sprintf(__('Search %s', 'toon-config'), $label),
      'all_items'         => sprintf(__('All %s', 'toon-config'), $label),
      'parent_item'       => sprintf(__('Parent %s', 'toon-config'), $singular),
      'parent_item_colon' => sprintf(__('Parent %s:', 'toon-config'), $singular),
      'edit_item'         => sprintf(__('Edit %s', 'toon-config'), $singular),
      'update_item'       => sprintf(__('Update %s', 'toon-config'), $singular),
      'add_new_item'      => sprintf(__('Add New %s', 'toon-config'), $singular),
      'new_item_name'     => sprintf(__('New %s Name', 'toon-config'), $singular),
      'menu_name'         => $singular,
    ];

    register_taxonomy($name, $post_types, [
      'labels'             => $labels,
      'rewrite'            => ['slug' => $slug],
      'hierarchical'       => $hierarchical,
      'show_admin_column'  => $show_admin_column,
    ]);
  }
}
