<?php

/**
 * @file
 * Contains \Drupal\redirect\Test\RedirectAPITest
 */

namespace Drupal\redirect\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Language\Language;

class RedirectAPITest extends DrupalUnitTestBase {

  /**
   * @var \Drupal\Core\Entity\FieldableDatabaseStorageController
   */
  protected $controller;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('redirect', 'link', 'field', 'system', 'user');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Redirect API tests',
      'description' => 'Redirect entity and redirect API test coverage.',
      'group' => 'Redirect',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installSchema('redirect', array('redirect'));
    $this->installSchema('user', array('users'));
    $this->installConfig(array('redirect'));

    $this->controller = $this->container->get('entity.manager')->getStorage('redirect');
  }

  /**
   * Test redirect entity logic.
   */
  public function testRedirectEntity() {
    // Create a redirect and test if hash has been generated correctly.
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->controller->create();
    $redirect->setSource('some-url', array('query' => array('key' => 'val')));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('some-url', array('key' => 'val'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    // Update the redirect source query and check if hash has been updated as
    // expected.
    $redirect->setSource('some-url', array('query' => array('key1' => 'val1')));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('some-url', array('key1' => 'val1'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    // Update the redirect source path and check if hash has been updated as
    // expected.
    $redirect->setSource('another-url', array('query' => array('key1' => 'val1')));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('another-url', array('key1' => 'val1'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    // Update the redirect language and check if hash has been updated as
    // expected.
    $redirect->setLanguage(Language::LANGCODE_DEFAULT);
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('another-url', array('key1' => 'val1'), Language::LANGCODE_DEFAULT), $redirect->getHash());

    // Create a few more redirects to test the select.
    for ($i = 0; $i < 5; $i++) {
      $redirect = $this->controller->create();
      $redirect->setSource($this->randomName());
      $redirect->save();
    }

    /** @var \Drupal\redirect\RedirectRepository $repository */
    $repository = \Drupal::service('redirect.repository');
    $redirect = $repository->findMatchingRedirect('another-url', array('key1' => 'val1'), Language::LANGCODE_DEFAULT);

    if (!empty($redirect)) {
      $this->assertEqual($redirect->getSourceUrl(), 'another-url?key1=val1');
      $this->assertEqual($redirect->getSourceOption('query'), array('key1' => 'val1'));
    }
    else {
      $this->fail(t('Failed to find matching redirect.'));
    }

    // Load the redirect based on url.
    $redirects = $repository->findBySourcePath('another-url');
    $redirect = array_shift($redirects);
    if (!empty($redirect)) {
      $this->assertEqual($redirect->getSourceUrl(), 'another-url?key1=val1');
      $this->assertEqual($redirect->getSourceOption('query'), array('key1' => 'val1'));
    }
    else {
      $this->fail(t('Failed to find redirect by source path.'));
    }
  }

  /**
   * Test the redirect_compare_array_recursive() function.
   */
  public function testCompareArrayRecursive() {
    $haystack = array('a' => 'aa', 'b' => 'bb', 'c' => array('c1' => 'cc1', 'c2' => 'cc2'));
    $cases = array(
      array('query' => array('a' => 'aa', 'b' => 'invalid'), 'result' => FALSE),
      array('query' => array('b' => 'bb', 'b' => 'bb'), 'result' => TRUE),
      array('query' => array('b' => 'bb', 'c' => 'invalid'), 'result' => FALSE),
      array('query' => array('b' => 'bb', 'c' => array()), 'result' => TRUE),
      array('query' => array('b' => 'bb', 'c' => array('invalid')), 'result' => FALSE),
      array('query' => array('b' => 'bb', 'c' => array('c2' => 'invalid')), 'result' => FALSE),
      array('query' => array('b' => 'bb', 'c' => array('c2' => 'cc2')), 'result' => TRUE),
    );
    foreach ($cases as $index => $case) {
      $this->assertEqual($case['result'], redirect_compare_array_recursive($case['query'], $haystack));
    }
  }

  /**
   * Test redirect_sort_recursive().
   */
  public function testSortRecursive() {
    $test_cases = array(
      array(
        'input' => array('b' => 'aa', 'c' => array('c2' => 'aa', 'c1' => 'aa'), 'a' => 'aa'),
        'expected' => array('a' => 'aa', 'b' => 'aa', 'c' => array('c1' => 'aa', 'c2' => 'aa')),
        'callback' => 'ksort',
      ),
    );
    foreach ($test_cases as $index => $test_case) {
      $output = $test_case['input'];
      redirect_sort_recursive($output, $test_case['callback']);
      $this->assertIdentical($output, $test_case['expected']);
    }
  }

  /**
   * Test redirect_parse_url().
   */
  public function testParseURL() {
    //$test_cases = array(
    //  array(
    //    'input' => array('b' => 'aa', 'c' => array('c2' => 'aa', 'c1' => 'aa'), 'a' => 'aa'),
    //    'expected' => array('a' => 'aa', 'b' => 'aa', 'c' => array('c1' => 'aa', 'c2' => 'aa')),
    //  ),
    //);
    //foreach ($test_cases as $index => $test_case) {
    //  $output = redirect_parse_url($test_case['input']);
    //  $this->assertIdentical($output, $test_case['expected']);
    //}
  }
}
