<?php

/**
 * @file
 * Contains \Drupal\redirect\RedirectStorageSchema.
 */

namespace Drupal\redirect;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the redirect schema.
 */
class RedirectStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    // Add indexes.
    $schema['redirect']['unique keys'] += [
      'hash' => ['hash'],
    ];
    $schema['redirect']['indexes'] += [
      'source_language' => ['redirect_source__path', 'language'],
    ];

    return $schema;
  }

}
