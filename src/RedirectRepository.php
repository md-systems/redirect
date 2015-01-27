<?php

/**
 * @file
 * Contains \Drupal\redirect\RedirectRepository
 */

namespace Drupal\redirect;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\redirect\Entity\Redirect;

class RedirectRepository {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $manager;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->manager = $manager;
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
  public function findMatchingRedirect($source_path, array $query = array(), $language = Language::LANGCODE_NOT_SPECIFIED) {

    $hashes = array(Redirect::generateHash($source_path, $query, $language));
    if ($language != Language::LANGCODE_NOT_SPECIFIED) {
      $hashes[] = Redirect::generateHash($source_path, $query, Language::LANGCODE_NOT_SPECIFIED);
    }

    // Load redirects by hash.
    $redirects = $this->manager->getStorage('redirect')->loadByProperties(array('hash' => $hashes));
    if (!empty($redirects)) {
      return array_shift($redirects);
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
    return $this->manager->getStorage('redirect')->loadByProperties(array('redirect_source__uri' => $source_path));
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
