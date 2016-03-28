<?php

/**
 * @file
 * Contains \Drupal\views_tree\Tree.
 */

namespace Drupal\views_tree;

use Drupal\views\ResultRow;

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
      $return[$current_group][$item->index] = $item;
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

  /**
   * @param array $result_tree
   * @param array $rows
   */
  public function getItemsTreeFromResultTree(array $result_tree, array $rows) {
    $result_tree = $result_tree[0];
    $items = [];

    foreach ($result_tree as $tree_node) {
      if ($tree_node instanceof ResultRow) {
        $items[] = $rows[$tree_node->index];
      }
      else {
        $items[]
      }
    }
    /** @var \Drupal\views\ResultRow $sibling_items */
    $sibling_items = array_filter($result_tree, function ($item) {
      return $item instanceof ResultRow;
    });

    foreach ($sibling_items as $item) {
      $items[] = $rows[$item->index];
    }
  }

}
