<?php
/**
 * @file
 * Contains Drupal\redirect\Plugin\views\field\FieldRedirectLinkEdit.
 */

namespace Drupal\redirect\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;

class FieldRedirectLinkEdit extends FieldPluginBase {
  function construct() {
    parent::construct();
    $this->additional_fields['rid'] = 'rid';
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => '', 'translatable' => TRUE);
    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
  }

  function query() {
    $this->ensure_my_table();
    $this->add_additional_fields();
  }

  function render($values) {
    $rid = $values->{$this->aliases['rid']};
    if (($redirect = redirect_load($rid)) && redirect_access('update', $redirect)) {
      $text = !empty($this->options['text']) ? $this->options['text'] : t('Edit');
      return l($text, "admin/config/search/redirect/edit/" . $rid, array('query' => drupal_get_destination()));
    }
  }
}
