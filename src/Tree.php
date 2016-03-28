<?php

/**
 * @file
 * Contains \Drupal\views_tree\Tree.
 */

namespace Drupal\views_tree;

class Tree {

  /**
   * Builds a tree from a views result.
   *
   * @param array $result
   *   The views results with views_tree_main and views_tree_parent set.
   *
   * @return array
   *   A tree representation.
   */
  public function getTreeFromResult(array $result) {
    $groups = $this->groupResultByParent($result);
    return $this->getTreeFromGroups($groups);
  }

  protected function getTreeFromGroups(array $groups, $current_group = '0') {
    $return = [];

    if (empty($groups[$current_group])) {
      return $return;
    }

    foreach ($groups[$current_group] as $item) {
      $return[$current_group][] = $item;
      $return[$current_group] = array_merge($return[$current_group], $this->getTreeFromGroups($groups, $item->views_tree_main));
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
