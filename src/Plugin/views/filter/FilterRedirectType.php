<?php

/**
 * @file
 * Contains Drupal\redirect\Plugin\views\filter\FilterRedirectType.
 */

namespace Drupal\redirect\Plugin\views\field;

use Drupal\views\Plugin\views\filter\InOperator;

class FilterRedirectType extends InOperator {
  function get_value_options() {
    if (!isset($this->value_options)) {
      $this->value_title = t('Redirect type');
      $options = array();
      $types = db_query("SELECT DISTINCT type FROM {redirect}")->fetchCol();
      foreach ($types as $type) {
        $options[$type] = t(drupal_ucfirst($type));
      }
      $this->value_options = $options;
    }
  }
}
