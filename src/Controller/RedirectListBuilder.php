<?php

/**
 * @file
 * Contains Drupal\redirect\Controller\RedirectListController.
 */

namespace Drupal\redirect\Controller;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class RedirectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['redirect_source'] = $this->t('From');
    $row['redirect_redirect'] = $this->t('To');
    $row['status_code'] = $this->t('Status');
    $row['creation'] = $this->t('Creation');
    $row['language'] = $this->t('Language');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $redirect) {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $row['redirect_source']['data'] = $redirect->getSourceUrl();
    if ($url = $redirect->getRedirectUrl()) {
      $row['redirect_redirect']['data'] = \Drupal::l($url->toString(), $url);
    }
    else {
      $row['redirect_redirect']['data'] = '';
    }
    $row['status_code']['data'] = $redirect->getStatusCode();
    $row['creation']['data'] = \Drupal::service('date.formatter')->format($redirect->getCreationDateTime(), 'short');
    $row['language']['data'] = $redirect->language()->getName();

    $row['operations']['data'] = $this->buildOperations($redirect);
    return $row;
  }

}
