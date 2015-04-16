<?php

/**
 * @file
 * Contains \Drupal\redirect\RedirectRepository
 */

namespace Drupal\redirect;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\redirect\Entity\Redirect;

class RedirectRepository {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityManagerInterface $manager, Connection $connection) {
    $this->manager = $manager;
    $this->connection = $connection;
  }

  /**
   * Gets a redirect for given path, query and language.
   *
   * @param string $source_path
   *   The redirect source path.
   * @param array $query
   *   The redirect source path query.
   * @param $language
   *   The language for which is the redirect.
   *
   * @return \Drupal\redirect\Entity\Redirect
   *   The matched redirect entity.
   */
  public function findMatchingRedirect($source_path, array $query = [], $language = Language::LANGCODE_NOT_SPECIFIED) {

    $hashes = [Redirect::generateHash($source_path, $query, $language)];
    if ($language != Language::LANGCODE_NOT_SPECIFIED) {
      $hashes[] = Redirect::generateHash($source_path, $query, Language::LANGCODE_NOT_SPECIFIED);
    }

    // Load redirects by hash. A direct query is used to improve performance.
    $rid = $this->connection->query('SELECT rid FROM {redirect} WHERE hash IN (:hashes[])', [':hashes[]' => $hashes])->fetchField();

    if (!empty($rid)) {
      return $this->load($rid);
    }

    return NULL;
  }

  /**
   * Finds redirects based on the source path.
   *
   * @param string $source_path
   *   The redirect source path (without the query).
   *
   * @return \Drupal\redirect\Entity\Redirect[]
   *   Array of redirect entities.
   */
  public function findBySourcePath($source_path) {
    return $this->manager->getStorage('redirect')->loadByProperties(array('redirect_source__path' => $source_path));
  }

  /**
   * Load redirect entity by id.
   *
   * @param int $redirect_id
   *   The redirect id.
   *
   * @return \Drupal\redirect\Entity\Redirect
   */
  public function load($redirect_id) {
    return $this->manager->getStorage('redirect')->load($redirect_id);
  }

  /**
   * Loads multiple redirect entities.
   *
   * @param array $redirect_ids
   *   Redirect ids to load.
   *
   * @return \Drupal\redirect\Entity\Redirect[]
   *   List of redirect entities.
   */
  public function loadMultiple(array $redirect_ids = NULL) {
    return $this->manager->getStorage('redirect')->loadMultiple($redirect_ids);
  }
}
