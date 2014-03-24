<?php

/**
 * @file
 * Contains \Drupal\redirect\EventSubscriber\RedirectSubscriber.
 */

namespace Drupal\redirect\EventSubscriber;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\redirect\Entity\Redirect;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Redirect subscriber for controller requests.
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /** @var  \Drupal\redirect\RedirectRepository */
  protected $redirectRepository;

  /**
   * @var \Drupal\Core\Routing\UrlGenerator
   */
  protected $urlGenerator;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectSubscriber object.
   *
   * @param \Drupal\Core\Routing\UrlGenerator $url_generator
   *   The URL generator service.
   * @param \Drupal\redirect\RedirectRepository $redirect_repository
   *   The redirect entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager service.
   */
  public function __construct(UrlGenerator $url_generator, RedirectRepository $redirect_repository, LanguageManagerInterface $language_manager) {
    $this->urlGenerator = $url_generator;
    $this->redirectRepository = $redirect_repository;
    $this->languageManager = $language_manager;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(GetResponseEvent $event) {
    $request = $event->getRequest();

    // Get URL info and process it to be used for hash generation.
    parse_str($request->getQueryString(), $query);
    $path = ltrim($request->getPathInfo(), '/');

    $redirect = $this->redirectRepository->findMatchingRedirect($path, $query, $this->languageManager->getCurrentLanguage());

    if (!empty($redirect)) {
      // Handle internal path.
      if ($route_name = $redirect->getRedirectRouteName()) {
        $url = $this->urlGenerator->generateFromRoute($route_name, $redirect->getRedirectRouteParameters(), array(
          'absolute' => TRUE,
          'query' => $redirect->getRedirectOption('query'),
        ));
      }
      // Handle external path.
      else {
        $url = $redirect->getRedirectUrl();
      }
      $response = new RedirectResponse($url, $redirect->getStatusCode());
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestCheckRedirect', 50);
    return $events;
  }
}
