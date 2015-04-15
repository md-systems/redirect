<?php

/**
 * @file
 * Contains \Drupal\redirect\Tests\RedirectUILanguageTest
 */

namespace Drupal\redirect\Tests;

/**
 * UI tests for redirect module with language and content translation modules.
 *
 * This runs the exact same tests as RedirectUITest, but with both the language
 * and content translation modules enabled.
 *
 * @group redirect
 */
class RedirectUILanguageTest extends RedirectUITest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['redirect', 'node', 'path', 'dblog', 'views', 'taxonomy', 'language', 'content_translation'];

}
