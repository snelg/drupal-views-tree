<?php

namespace Drupal\views_tree\Plugin\EntityReferenceSelection;

use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Plugin implementation of the 'selection' entity_reference.
 *
 * @EntityReferenceSelection(
 *   id = "views_tree",
 *   label = @Translation("Tree (Adjacency model)"),
 *   group = "views_tree",
 *   weight = 0
 * )
 */
class TreeViewsSelection extends ViewsSelection {

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $handler_settings = $this->configuration['handler_settings'];
    $display_name = $handler_settings['view']['display_name'];
    $arguments = $handler_settings['view']['arguments'];
    $result = array();
    if ($this->initializeView($match, $match_operator, $limit)) {
      // Get the results.
      $result = $this->view->executeDisplay($display_name, $arguments);
    }


    $this->applyTreeOnResult($this->view, $this->view->result);
    $groups = $this->groupResultByParent($this->view->result);
    $tree = $this->getTreeFromResult($groups);

    $return = [];
    if ($result) {
      array_walk_recursive($tree, function (ResultRow $row) use (&$return) {
        $entity = $row->_entity;
        $return[$entity->bundle()][$entity->id()] = str_repeat('-', $row->views_tree_depth) . $entity->label();
      });
    }

    return $return;
  }

  /**
   * @param \Drupal\views\ViewExecutable $view
   * @param \Drupal\views\ResultRow[] $result
   *
   * @return string
   */
  protected function applyTreeOnResult(ViewExecutable $view, array $result) {
    $fields = $view->field;
    $options = $view->getStyle()->options;

    // @todo Extract this and the logic in \theme_views_tree into its own helper
    if (! $fields[$options['main_field']] instanceof FieldPluginBase) {
      drupal_set_message(t('Main field is invalid: %field', array('%field' => $options['main_field'])), 'error');
      return '';
    }

    if (! $fields[$options['parent_field']] instanceof FieldPluginBase) {
      drupal_set_message(t('Parent field is invalid: %field', array('%field' => $options['parent_field'])), 'error');
      return '';
    }

    // Add the parent items to the result.
    foreach ($result as $row) {
      $row->views_tree_main = views_tree_normalize_key($fields[$options['main_field']]->getValue($row), $fields[$options['main_field']]);
      $row->views_tree_parent = views_tree_normalize_key($fields[$options['parent_field']]->getValue($row), $fields[$options['parent_field']]);
    }

    // Add the depth onto the result.
    foreach ($result as $row) {
      $current_row = $row;
      $depth = 0;
      while ($current_row->views_tree_parent != '0') {
        $depth++;
        if ($parent_row = $this->findRowByParent($result, $current_row->views_tree_parent)) {
          $current_row = $parent_row;
        }
        else {
          break;
        }
      }
      $row->views_tree_depth = $depth;
    }
  }

  protected function findRowByParent(array $result, $parent_id) {
    foreach ($result as $row) {
      if ($parent_id == $row->views_tree_main) {
        return $row;
      }
    }
  }

  /**
   * @param \Drupal\views\ResultRow[] $result
   *
   * @return array
   */
  protected function getTreeFromResult(array $groups, $current_group = '0') {
    $return = [];

    if (empty($groups[$current_group])) {
      return $return;
    }

    foreach ($groups[$current_group] as $item) {
      $return[$current_group][] = $item;
      $return[$current_group] = array_merge($return[$current_group], $this->getTreeFromResult($groups, $item->views_tree_main));
    }
    return $return;
  }

  protected function groupResultByParent(array $result) {
    $return = [];

    foreach ($result as $row) {
      $return[$row->views_tree_parent][] = $row;
    }
    return $return;
  }

}
