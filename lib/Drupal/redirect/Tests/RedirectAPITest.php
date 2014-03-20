<?php

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

  public static function getInfo() {
    return array(
      'name' => 'Redirect API tests',
      'description' => 'Test basic functions and functionality.',
      'group' => 'Redirect',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();

    $this->installSchema('redirect', array('redirect'));
    $this->installSchema('user', array('users'));
    $this->installConfig(array('redirect'));

    $this->controller = $this->container->get('entity.manager')->getStorageController('redirect');
  }

  /**
   * Test redirect entity logic.
   */
  function testRedirectEntity() {
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    $redirect = $this->controller->create();
    $redirect->setSource('some-url', array('query' => array('key' => 'val')));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('some-url', array('key' => 'val'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    $redirect->setSource('some-url', array('query' => array('key1' => 'val1')));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('some-url', array('key1' => 'val1'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    $redirect->setSource('another-url', array('query' => array('key1' => 'val1')));
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('another-url', array('key1' => 'val1'), Language::LANGCODE_NOT_SPECIFIED), $redirect->getHash());

    $redirect->setLanguage(Language::LANGCODE_DEFAULT);
    $redirect->save();
    $this->assertEqual(Redirect::generateHash('another-url', array('key1' => 'val1'), Language::LANGCODE_DEFAULT), $redirect->getHash());

    // Create a few more redirects.
    for ($i = 0; $i < 5; $i++) {
      $redirect = $this->controller->create();
      $redirect->setSource($this->randomName());
      $redirect->save();
    }

    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('hash' => Redirect::generateHash('another-url', array('key1' => 'val1'), Language::LANGCODE_DEFAULT)));
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), 'another-url');
    $this->assertEqual($redirect->getSourceOption('query'), array('key1' => 'val1'));

    $redirects = \Drupal::entityManager()
      ->getStorageController('redirect')
      ->loadByProperties(array('redirect_source__url' => 'another-url'));
    $redirect = array_shift($redirects);
    $this->assertEqual($redirect->getSourceUrl(), 'another-url');
    $this->assertEqual($redirect->getSourceOption('query'), array('key1' => 'val1'));
  }

  /**
   * Test the redirect_compare_array_recursive() function.
   */
  function testCompareArrayRecursive() {
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
  function testSortRecursive() {
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
  function testParseURL() {
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

