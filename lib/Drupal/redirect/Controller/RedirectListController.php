<?php

namespace Drupal\redirect\Controller;


use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListController;
use Drupal\Core\Language\Language;
use Drupal\Core\Url;

class RedirectListController extends EntityListController {


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
    $row['access']['data'] = \Drupal::service('date')->formatInterval(REQUEST_TIME - $redirect->getLastAccessed());
    $row['operations']['data'] = $this->buildOperations($redirect);
    return $row;
  }

}
