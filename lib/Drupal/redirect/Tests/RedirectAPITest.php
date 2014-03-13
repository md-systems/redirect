<?php

namespace Drupal\redirect\Tests;

use Drupal\simpletest\UnitTestBase;

class RedirectAPITest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Redirect unit tests',
      'description' => 'Test basic functions and functionality.',
      'group' => 'Redirect',
    );
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

