<?php

/**
 * @file
 * Contains \Drupal\Tests\views_tree\Unit\TreeTest.
 */

namespace Drupal\Tests\views_tree\Unit;

use Drupal\views\ResultRow;
use Drupal\views_tree\Tree;

/**
 * @coversDefaultClass \Drupal\views_tree\Tree
 * @group views_tree
 */
class TreeTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::getTreeFromResult
   */
  public function testGetTreeFromResultFromEmptyResult() {
    $tree = new Tree();
    $this->assertEquals([], $tree->getTreeFromResult([]));
  }

  /**
   * @covers ::getTreeFromResult
   */
  public function testGetTreeFromResultWithNoHierarchy() {
    $tree = new Tree();
    $tree_data = [];
    $tree_data[] = new ResultRow([
      'views_tree_main' => 1,
      'views_tree_parent' => 0,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 2,
      'views_tree_parent' => 0,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 3,
      'views_tree_parent' => 0,
    ]);
    $this->assertEquals([0 => $tree_data], $tree->getTreeFromResult($tree_data));
  }

  /**
   * @covers ::getTreeFromResult
   */
  public function testGetTreeFromResultWithOneLevelHierarchy() {
    $tree = new Tree();
    $tree_data = [];
    $tree_data[] = new ResultRow([
      'views_tree_main' => 1,
      'views_tree_parent' => 0,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 2,
      'views_tree_parent' => 1,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 3,
      'views_tree_parent' => 1,
    ]);
    $this->assertEquals(
      [
        [
          $tree_data[0],
          [
            $tree_data[1], $tree_data[2],
          ],
        ]
      ], $tree->getTreeFromResult($tree_data));
  }

  /**
   * @covers ::getTreeFromResult
   */
  public function testGetTreeFromResultWithMultipleLevelHierarchy() {
    $tree = new Tree();
    $tree_data = [];
    $tree_data[] = new ResultRow([
      'views_tree_main' => 1,
      'views_tree_parent' => 0,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 2,
      'views_tree_parent' => 1,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 3,
      'views_tree_parent' => 1,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 4,
      'views_tree_parent' => 1,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 5,
      'views_tree_parent' => 4,
    ]);
    $tree_data[] = new ResultRow([
      'views_tree_main' => 6,
      'views_tree_parent' => 5,
    ]);
    $this->assertEquals(
      [
        [
          $tree_data[0],
          [
            $tree_data[1], $tree_data[2], $tree_data[3],
            [
              $tree_data[4],
              [
                $tree_data[5],
              ],
            ],
          ],
        ],
      ], $tree->getTreeFromResult($tree_data));
  }

}
