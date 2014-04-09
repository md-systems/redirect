<?php

/**
 * @file
 * Contains Drupal\redirect\Controller\RedirectListController.
 */

namespace Drupal\redirect\Controller;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class RedirectListController extends EntityListBuilder {

  public function buildHeader() {
    $row['redirect_source'] = $this->t('From');
    $row['redirect_redirect'] = $this->t('To');
    $row['status_code'] = $this->t('Status');
    $row['language'] = $this->t('Language');
    $row['count'] = $this->t('Count');
    $row['access'] = $this->t('Last accessed');
    $row['operations'] = $this->t('Operations');
    return $row;
  }

  public function buildRow(EntityInterface $redirect) {
    $row['redirect_source']['data'] = $redirect->getSourceUrl();
    $row['redirect_redirect']['data'] = l($redirect->getRedirectUrl(), $redirect->getRedirectUrl());
    $row['status_code']['data'] = $redirect->getStatusCode();
    $row['language']['data'] = $redirect->getLanguage();
    $row['count']['data'] = $redirect->getCount();

    if ($redirect->getLastAccessed()) {
      $time_ago_message = $this->t('@time ago',
        array('@time' => \Drupal::service('date')->formatInterval(REQUEST_TIME - $redirect->getLastAccessed())));
    }
    else {
      $time_ago_message = $this->t('Never');
    }

    $row['access']['data'] = $time_ago_message;
    $row['operations']['data'] = $this->buildOperations($redirect);
    return $row;
  }

}
