<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Form\FormBase;

class RedirectDeleteForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {

    $form['rid'] = array(
      '#type' => 'value',
      '#value' => $redirect->rid,
    );

    return confirm_form(
      $form,
      t('Are you sure you want to delete the URL redirect from %source to %redirect?', array('%source' => $redirect->source, '%redirect' => $redirect->redirect)),
      'admin/config/search/redirect'
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    redirect_delete($form_state['values']['rid']);
    drupal_set_message(t('The redirect has been deleted.'));
    $form_state['redirect'] = 'admin/config/search/redirect';
  }
}
