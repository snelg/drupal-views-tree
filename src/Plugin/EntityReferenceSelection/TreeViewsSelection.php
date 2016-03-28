<?php

namespace Drupal\views_tree\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_tree\Tree;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * @var \Drupal\views_tree\Tree
   */
  protected $tree;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager, ModuleHandlerInterface $module_handler, AccountInterface $current_user, Tree $tree) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_manager, $module_handler, $current_user);

    $this->tree = $tree;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('views_tree.tree')
    );
  }


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
    $tree = $this->tree->getTreeFromResult($this->view->result);

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

}
