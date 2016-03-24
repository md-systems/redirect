<?php

/**
 * @file
 * Contains \Drupal\redirect\Plugin\migrate\source\d7\PathRedirect.
 */

namespace Drupal\redirect\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 path redirect source from database.
 *
 * @MigrateSource(
 *   id = "d7_path_redirect"
 * )
 */
class PathRedirect extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select path redirects.
    $query = $this->select('redirect', 'p')
      ->fields('p');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // @todo Complete this function for D7.
    $fields = array(
      'rid' => $this->t('Redirect ID'),
      'source' => $this->t('Source'),
      'redirect' => $this->t('Redirect'),
      'query' => $this->t('Query'),
      'fragment' => $this->t('Fragment'),
      'language' => $this->t('Language'),
      'type' => $this->t('Type'),
      'last_used' => $this->t('Last Used'),
    );
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['rid']['type'] = 'integer';
    return $ids;
  }

}
