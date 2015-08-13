<?php

/**
 * @file
 * Contains \Drupal\redirect\Tests\RedirectUILanguageTest.
 */

namespace Drupal\redirect\Tests;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;

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

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $language = ConfigurableLanguage::createFromLangcode('de');
    $language->save();
    $language = ConfigurableLanguage::createFromLangcode('es');
    $language->save();
  }

  /**
   * Test multilingual scenarios.
   */
  public function testLanguageSpecificRedirects() {
    $this->drupalLogin($this->adminUser);

    // Add a redirect for english.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '/user',
      'language[0][value]' => 'en',
    ), t('Save'));

    // Add a redirect for germany.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '<front>',
      'language[0][value]' => 'de',
    ), t('Save'));

    // Check redirect for english.
    $this->assertRedirect('langpath', '/user', 'HTTP/1.1 301 Moved Permanently');

    // Check redirect for germany.
    $this->assertRedirect('de/langpath', '/de', 'HTTP/1.1 301 Moved Permanently');

    // Check no redirect for spanish.
    $this->assertRedirect('es/langpath', NULL, 'HTTP/1.1 404 Not Found');
  }

  /**
   * Test non-language specific redirect.
   */
  public function testUndefinedLangugageRedirects() {
    $this->drupalLogin($this->adminUser);

    // Add a redirect for english.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][path]' => 'langpath',
      'redirect_redirect[0][uri]' => '/user',
      'language[0][value]' => 'und',
    ), t('Save'));

    // Check redirect for english.
    $this->assertRedirect('langpath', '/user', 'HTTP/1.1 301 Moved Permanently');

    // Check redirect for spanish.
    $this->assertRedirect('es/langpath', '/es/user', 'HTTP/1.1 301 Moved Permanently');
  }


  public function testFix404RedirectList() {
    $this->drupalLogin($this->adminUser);

    // Add predefined language.
    $this->drupalPostForm('admin/config/regional/language/add', array('predefined_langcode' => 'fr'), t('Add language'));
    $this->assertText('French');

    // Visit a non existing page to have the 404 redirect_error entry.
    $this->drupalGet('fr/testing');

    $redirect = db_select('redirect_error')->fields('redirect_error')->condition('source', 'fr/testing')->execute()->fetchAll();
    if (count($redirect) == 0) {
      $this->fail('No record was added');
    }

    // Go to the "fix 404" page and check the listing.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('fr/testing');
    $this->assertText('French');
    $this->clickLink(t('Add redirect'));

    // Check if we generate correct Add redirect url and if the form is
    // pre-filled.
    $destination = Url::fromUri('base:admin/config/search/redirect/404')->toString();
    $this->assertUrl('admin/config/search/redirect/add', ['query' => ['source' => 'fr/testing', 'language' => 'fr', 'destination' => $destination]]);
    $this->assertFieldByName('redirect_source[0][path]', 'fr/testing');
    $this->assertOptionSelected('edit-language-0-value', 'fr');

    // Save the redirect.
    $this->drupalPostForm(NULL, array('redirect_redirect[0][uri]' => '/node'), t('Save'));
    $this->assertUrl('admin/config/search/redirect/404');

    // Check if the redirect works as expected.
    $this->drupalGet('admin/config/search/redirect');
    $this->drupalGet('fr/testing');
    $this->assertUrl('fr/node');
  }

}
