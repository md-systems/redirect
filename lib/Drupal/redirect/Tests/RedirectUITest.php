<?php

/**
 * @file
 * Contains \Drupal\redirect\Tests\RedirectUITest
 */

namespace Drupal\redirect\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

class RedirectUITest extends WebTestBase {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * @var \Drupal\redirect\RedirectRepository
   */
  protected $repository;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('redirect', 'node', 'path', 'dblog', 'views', 'taxonomy');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Redirect UI tests',
      'description' => 'Test interface functionality.',
      'group' => 'Redirect',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article', 'name' => 'Article'));
    $this->adminUser = $this->drupalCreateUser(array(
      'administer redirects',
      'access site reports',
      'access content',
      'create article content',
      'edit any article content',
      'create url aliases',
      'administer taxonomy',
      'administer url aliases',
    ));

    $this->repository = \Drupal::service('redirect.repository');
  }

  /**
   * Test the redirect UI.
   */
  function testRedirectUI() {
    $this->drupalLogin($this->adminUser);

    // Test populating the redirect form with predefined values.
    $this->drupalGet('admin/config/search/redirect/add', array('query' => array(
      'source' => 'non-existing',
      'source_options' => array('query' => array('key' => 'val', 'key1' => 'val1')),
      'redirect' => 'node',
      'redirect_options' => array('query' => array('key' => 'val', 'key1' => 'val1')),
    )));
    $this->assertFieldByName('redirect_source[0][url]', 'non-existing?key=val&key1=val1');
    $this->assertFieldByName('redirect_redirect[0][url]', 'node?key=val&key1=val1');

    // Test creating a new redirect via UI.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => 'non-existing',
      'redirect_redirect[0][url]' => 'node',
    ), t('Save'));

    // Try to find the redirect we just created.
    $redirect = $this->repository->findMatchingRedirect('non-existing');
    $this->assertEqual($redirect->getSourceUrl(), 'non-existing');
    $this->assertEqual($redirect->getRedirectUrl(), 'node');

    // After adding the redirect we should end up in the list. Check if the
    // redirect is listed.
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText('non-existing');
    $this->assertLink('node');
    $this->assertText(Language::LANGCODE_NOT_SPECIFIED);

    // Test the edit form and update action.
    $this->clickLink(t('Edit'));
    $this->assertFieldByName('redirect_source[0][url]', 'non-existing');
    $this->assertFieldByName('redirect_redirect[0][url]', 'node');
    $this->assertFieldByName('status_code', $redirect->getStatusCode());

    // Append a query string to see if we handle query data properly.
    $this->drupalPostForm(NULL, array(
      'redirect_source[0][url]' => 'non-existing?key=value',
    ), t('Save'));

    // Check the location after update and check if the value has been updated
    // in the list.
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText('non-existing?key=value');

    // The url field should not contain the query string and therefore we
    // should be able to load the redirect using only the url part without
    // query.
    \Drupal::entityManager()->getStorageController('redirect')->resetCache();
    $redirects = $this->repository->findBySourcePath('non-existing');
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), 'non-existing?key=value');
    $this->assertEqual($redirect->getSourceOption('query'), array('key' => 'value'));

    // Test the source url hints.
    // The hint about an existing base path.
    $this->drupalPostAjaxForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => 'non-existing?key=value',
    ), 'redirect_source[0][url]');
    $this->assertRaw(t('The base source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
      array('%source' => 'non-existing?key=value', '@edit-page' => url('admin/config/search/redirect/edit/'. $redirect->id()))));

    // The hint about a valid path.
    $this->drupalPostAjaxForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => 'node',
    ), 'redirect_source[0][url]');
    $this->assertRaw(t('The source path %path is likely a valid path. It is preferred to <a href="@url-alias">create URL aliases</a> for existing paths rather than redirects.',
      array('%path' => 'node', '@url-alias' => url('admin/config/search/path/add'))));

    // Test validation.
    // Duplicate redirect.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => 'non-existing?key=value',
      'redirect_redirect[0][url]' => 'node',
    ), t('Save'));
    $this->assertRaw(t('The source path %source is already being redirected. Do you want to <a href="@edit-page">edit the existing redirect</a>?',
      array('%source' => 'non-existing?key=value', '@edit-page' => url('admin/config/search/redirect/edit/'. $redirect->id()))));

    // Redirecting to itself.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => 'node',
      'redirect_redirect[0][url]' => 'node',
    ), t('Save'));
    $this->assertRaw(t('You are attempting to redirect the page to itself. This will result in an infinite loop.'));

    // Redirecting the front page.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => '<front>',
      'redirect_redirect[0][url]' => 'node',
    ), t('Save'));
    $this->assertRaw(t('It is not allowed to create a redirect from the front page.'));

    // Redirecting a url with fragment.
    $this->drupalPostForm('admin/config/search/redirect/add', array(
      'redirect_source[0][url]' => 'page-to-redirect#content',
      'redirect_redirect[0][url]' => 'node',
    ), t('Save'));
    $this->assertRaw(t('The anchor fragments are not allowed.'));

    // Finally test the delete action.
    $this->drupalGet('admin/config/search/redirect');
    $this->clickLink(t('Delete'));
    $this->assertRaw(t('Are you sure you want to delete the URL redirect from %source to %redirect?', array('%source' => 'non-existing?key=value', '%redirect' => 'node')));
    $this->drupalPostForm(NULL, array(), t('Delete'));
    $this->assertUrl('admin/config/search/redirect');
    $this->assertText(t('There is no @label yet.', array('@label' => 'Redirect')));
  }

  /**
   * Tests the fix 404 pages workflow.
   */
  function testFix404Pages() {
    $this->drupalLogin($this->adminUser);

    // Visit a non existing page to have the 404 watchdog entry.
    $this->drupalGet('non-existing');

    // Go to the "fix 404" page and check the listing.
    $this->drupalGet('admin/config/search/redirect/404');
    $this->assertText('non-existing');
    $this->clickLink(t('Add redirect'));

    // Check if we generate correct Add redirect url and if the form is
    // pre-filled.
    $this->assertUrl('admin/config/search/redirect/add?source=non-existing&destination=admin/config/search/redirect/404');
    $this->assertFieldByName('redirect_source[0][url]', 'non-existing');

    // Save the redirect.
    $this->drupalPostForm(NULL, array('redirect_redirect[0][url]' => 'node'), t('Save'));
    $this->assertUrl('admin/config/search/redirect/404');

    // Check if the redirect works as expected.
    $this->drupalGet('non-existing');
    $this->assertUrl('node');

    // Also test if the redirect has been properly logged.
    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $redirect = $repository->findMatchingRedirect('non-existing');
    $this->assertEqual($redirect->getCount(), 1);
    $this->assertTrue($redirect->getLastAccessed() > 0);
  }

  /**
   * Tests redirects being automatically created upon path alias change.
   */
  function testAutomaticRedirects() {
    $this->drupalLogin($this->adminUser);

    // Create a node and update its path alias which should result in a redirect
    // being automatically created from the old alias to the new one.
    $node = $this->drupalCreateNode(array('type' => 'article', 'langcode' => 'en', 'path' => array('alias' => 'node_test_alias')));
    $this->drupalPostForm('node/' . $node->id() . '/edit', array('path[alias]' => 'node_test_alias_updated'), t('Save'));

    $redirect = $this->repository->findMatchingRedirect('node_test_alias', array(), 'en');
    $this->assertEqual($redirect->getRedirectUrl(), 'node_test_alias_updated');

    // Create a term and update its path alias and check if we have a redirect
    // from the previous path alias to the new one.
    $term = $this->createTerm($this->createVocabulary());
    $this->drupalPostForm('taxonomy/term/' . $term->id() . '/edit', array('path[alias]' => 'term_test_alias_updated'), t('Save'));
    $redirect = $this->repository->findMatchingRedirect('term_test_alias');
    $this->assertEqual($redirect->getRedirectUrl(), 'term_test_alias_updated');

    // Test the path alias update via the admin path form.
    $this->drupalPostForm('admin/config/search/path/add', array(
      'source' => 'node',
      'alias' => 'aaa_path_alias',
    ), t('Save'));
    // Note that here we rely on fact that we land on the path alias list page
    // and the default sort is by the alias, which implies that the first edit
    // link leads to the edit page of the aaa_path_alias.
    $this->clickLink(t('edit'));
    $this->drupalPostForm(NULL, array('alias' => 'aaa_path_alias_updated'), t('Save'));
    $redirect = $this->repository->findMatchingRedirect('aaa_path_alias', array(), 'en');
    $this->assertEqual($redirect->getRedirectUrl(), 'aaa_path_alias_updated');
  }

  /**
   * Returns a new vocabulary with random properties.
   */
  function createVocabulary() {
    // Create a vocabulary.
    $vocabulary = entity_create('taxonomy_vocabulary', array(
      'name' => $this->randomName(),
      'description' => $this->randomName(),
      'vid' => drupal_strtolower($this->randomName()),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'weight' => mt_rand(0, 10),
    ));
    $vocabulary->save();
    return $vocabulary;
  }

  /**
   * Returns a new term with random properties in vocabulary $vid.
   */
  function createTerm($vocabulary) {
    $filter_formats = filter_formats();
    $format = array_pop($filter_formats);
    $term = entity_create('taxonomy_term', array(
      'name' => $this->randomName(),
      'description' => array(
        'value' => $this->randomName(),
        // Use the first available text format.
        'format' => $format->format,
      ),
      'vid' => $vocabulary->id(),
      'langcode' => Language::LANGCODE_NOT_SPECIFIED,
      'path' => array('alias' => 'term_test_alias'),
    ));
    $term->save();
    return $term;
  }

}
