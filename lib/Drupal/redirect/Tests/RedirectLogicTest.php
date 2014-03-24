<?php

/**
 * @file
 * Contains \Drupal\redirect\Tests\RedirectLogicTest.
 */

namespace Drupal\redirect\Tests;

use Drupal\Core\Language\Language;
use Drupal\redirect\EventSubscriber\RedirectSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Tests the redirect logic.
 */
class RedirectLogicTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Redirect logic tests',
      'description' => 'Redirect event subscriber class unit tests.',
      'group' => 'Redirect',
    );
  }

  /**
   * Unit test of the RedirectSubscriber::onKernelRequestCheckRedirect().
   */
  public function testRedirectLogic() {

    // We have the route name based on which we should get the url matched by
    // the url generator.
    $subscriber = new RedirectSubscriber(
      $this->getUrlGenerator('redirect_url'),
      $this->getRedirectRepository('dummy_route_name'),
      $this->getLanguageManager()
    );
    $event = $this->getResponseEvent();
    $subscriber->onKernelRequestCheckRedirect($event);

    $this->assertEquals('redirect_url', $event->getResponse()->getTargetUrl());
    $this->assertEquals(301, $event->getResponse()->getStatusCode());

    // Neither url nor route name provide for the redirect repository which
    // means no redirect will be found.
    $subscriber = new RedirectSubscriber(
      $this->getUrlGenerator('does_not_matter'),
      $this->getRedirectRepository(),
      $this->getLanguageManager()
    );
    $event = $this->getResponseEvent();
    $subscriber->onKernelRequestCheckRedirect($event);

    $this->assertEquals(NULL, $event->getResponse());

    // We provide the redirect url which means the redirect url is not processed
    // by the url generator, but directly used the one from redirect repository.
    $subscriber = new RedirectSubscriber(
      $this->getUrlGenerator('dummy_value'),
      $this->getRedirectRepository(NULL, 'absolute_url', 302),
      $this->getLanguageManager()
    );
    $event = $this->getResponseEvent();
    $subscriber->onKernelRequestCheckRedirect($event);

    $this->assertEquals('absolute_url', $event->getResponse()->getTargetUrl());
    $this->assertEquals(302, $event->getResponse()->getStatusCode());
  }

  /**
   * Gets response event object.
   *
   * @return GetResponseEvent
   */
  protected function getResponseEvent() {

    $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
      ->disableOriginalConstructor()
      ->getMock();
    $request->expects($this->any())
      ->method('getQueryString')
      ->will($this->returnValue('does_not_matter'));
    $request->expects($this->any())
      ->method('getPathInfo')
      ->will($this->returnValue('does_not_matter'));

    $http_kernel = $this->getMockBuilder('\Symfony\Component\HttpKernel\HttpKernelInterface')
      ->getMock();
    return new GetResponseEvent($http_kernel, $request, 'test');
  }

  /**
   * Gets the language manager mock object.
   *
   * @return \Drupal\language\ConfigurableLanguageManagerInterface|PHPUnit_Framework_MockObject_MockObject
   */
  protected function getLanguageManager() {
    $language_manager = $this->getMockBuilder('Drupal\language\ConfigurableLanguageManagerInterface')
      ->getMock();
    $language_manager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(new Language(array('id' => 'does_not_matter'))));

    return $language_manager;
  }

  /**
   * Gets the redirect repository mock object.
   *
   * @param string $redirect_route_name
   *   Set to simulate having the route name - the redirect url will be what is
   *   returned by the UrlGenerator
   * @param string $redirect_url
   *   To simulate having the absolute redirect url - the redirect url will be
   *   the this value if $redirect_route_name is null.
   * @param int $status_code
   *   Status code of the redirect.
   *
   * @return \Drupal\redirect\RedirectRepository|PHPUnit_Framework_MockObject_MockObject
   *
   * @see self::getUrlGenerator()
   */
  protected function getRedirectRepository($redirect_route_name = NULL, $redirect_url = NULL, $status_code = 301) {
    $repository = $this->getMockBuilder('Drupal\redirect\RedirectRepository')
      ->disableOriginalConstructor()
      ->getMock();

    // If none of them provided it means we simulate not having matched any
    // redirect.
    if (empty($redirect_route_name) && empty($redirect_url)) {
      $redirect = NULL;
    }
    else {
      $redirect = $this->getMockBuilder('Drupal\redirect\Entity\Redirect')
        ->disableOriginalConstructor()
        ->getMock();
      $redirect->expects($this->any())
        ->method('getRedirectRouteName')
        ->will($this->returnValue($redirect_route_name));
      $redirect->expects($this->any())
        ->method('getRedirectRouteParameters')
        ->will($this->returnValue('does_not_matter'));
      $redirect->expects($this->any())
        ->method('getRedirectOption')
        ->will($this->returnValue('does_not_matter'));
      $redirect->expects($this->any())
        ->method('getRedirectUrl')
        ->will($this->returnValue($redirect_url));
      $redirect->expects($this->any())
        ->method('getStatusCode')
        ->will($this->returnValue($status_code));
    }

    $repository->expects($this->any())
      ->method('findMatchingRedirect')
      ->will($this->returnValue($redirect));

    return $repository;
  }

  /**
   * Gets the url generator mock object.
   *
   * @param string $url
   *   The URL returned by the url generator.
   *
   * @return \Drupal\Core\Routing\UrlGenerator|PHPUnit_Framework_MockObject_MockObject
   */
  protected function getUrlGenerator($url) {
    $url_generator = $this->getMockBuilder('Drupal\Core\Routing\UrlGenerator')
      ->disableOriginalConstructor()
      ->getMock();
    $url_generator->expects($this->any())
      ->method('generateFromRoute')
      ->will($this->returnValue($url));

    return $url_generator;
  }

}
