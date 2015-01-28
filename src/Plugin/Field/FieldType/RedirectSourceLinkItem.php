<?php

/**
 * @file
 * Contains \Drupal\redirect\Plugin\Field\FieldType\RedirectSourceLinkItem.
 */

namespace Drupal\redirect\Plugin\Field\FieldType;

use Drupal\link\Plugin\Field\FieldType\LinkItem;

/**
 * Plugin implementation of the 'link' field type for redirect source.
 *
 * @FieldType(
 *   id = "redirect_source_link",
 *   label = @Translation("Redirect link"),
 *   description = @Translation("Stores a URL string used as the source for the redirect."),
 *   default_widget = "link_default",
 *   default_formatter = "link",
 *   constraints = {"RedirectSourceLinkType" = {}}
 * )
 */
class RedirectSourceLinkItem extends LinkItem {

}
