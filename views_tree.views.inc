<?php

/**
 * Implementation of hook_views_plugins()
 */
function views_tree_views_plugins() {
  return array(
    'style' => array(
      'tree' => array(
        'title'           => t('Tree (Adjacency model)'),
        'help'            => t('Display the results as a nested tree'),
        'handler'         => 'views_tree_plugin_style_tree',
        'uses options'    => TRUE,
        'uses row plugin' => TRUE,
        'uses fields'     => TRUE,
        'uses grouping'   => FALSE,
        'type'            => 'normal',
        'parent'          => 'list',
        'path'            => drupal_get_path('module', 'views_tree'),
        'theme'           => 'views_tree',
        'theme path'      => drupal_get_path('module', 'views_tree'),
        'theme file'      => 'views_tree.theme.inc',
      ),
    ),
  );
}
