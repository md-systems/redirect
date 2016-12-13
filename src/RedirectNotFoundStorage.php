<?php
/**
 * @file
 * Contains \Drupal\redirect\RedirectNotFoundStorage.
 */

namespace Drupal\redirect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the redirect schema.
 */
class RedirectNotFoundStorage extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getDedicatedTableSchema(FieldStorageDefinitionInterface $storage_definition, ContentEntityTypeInterface $entity_type = NULL) {
    $schema = parent::getDedicatedTableSchema($storage_definition, $entity_type);
  }

}
