<?php

/**
 * @file
 * Contains \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber.
 */

namespace Drupal\redirect\EventSubscriber;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\KeyValueStore\StateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\redirect\RedirectRepository;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Redirect subscriber for controller requests.
 */
class RedirectRequestSubscriber implements EventSubscriberInterface {

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
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\KeyValueStore\StateInterface
   */
  protected $state;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Routing\UrlGenerator $url_generator
   *   The URL generator service.
   * @param \Drupal\redirect\RedirectRepository $redirect_repository
   *   The redirect entity repository.
   * @param \Drupal\Core\Language\LanguageManagerInterface
   *   The language manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface
   *   The config.
   * @param \Drupal\Core\KeyValueStore\StateInterface
   *   The state.
   */
  public function __construct(UrlGenerator $url_generator, RedirectRepository $redirect_repository, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config, StateInterface $state) {
    $this->urlGenerator = $url_generator;
    $this->redirectRepository = $redirect_repository;
    $this->languageManager = $language_manager;
    $this->config = $config->get('redirect.settings');
    $this->state = $state;
  }

  /**
   * Handles the redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckRedirect(GetResponseEvent $event) {
    $request = $event->getRequest();

    if (!$this->canRedirect($request)) {
      return;
    }

    // Get URL info and process it to be used for hash generation.
    parse_str($request->getQueryString(), $request_query);
    $path = ltrim($request->getPathInfo(), '/');

    $redirect = $this->redirectRepository->findMatchingRedirect($path, $request_query, $this->languageManager->getCurrentLanguage());

    if (!empty($redirect)) {
      // Handle internal path.
      if ($route_name = $redirect->getRedirectRouteName()) {

        $redirect_query = $redirect->getRedirectOption('query', array());
        if ($this->config->get('passthrough_querystring')) {
          $redirect_query += $request_query;
        }

        $url = $this->urlGenerator->generateFromRoute($route_name, $redirect->getRedirectRouteParameters(), array(
          'absolute' => TRUE,
          'query' => $redirect_query,
        ));
      }
      // Handle external path.
      else {
        $url = $redirect->getRedirectUrl();
        $parsed_url = UrlHelper::parse($url);

        $redirect_query = $parsed_url['query'];
        if ($this->config->get('passthrough_querystring')) {
          $redirect_query += $request_query;
        }

        $url = $this->urlGenerator->generateFromPath($parsed_url['path'], array(
          'external' => TRUE,
          'query' => $redirect_query,
        ));
      }
      $response = new RedirectResponse($url, $redirect->getStatusCode(), array('X-Redirect-ID' => $redirect->id()));
      $event->setResponse($response);
    }
  }

  /**
   * Determines if redirect may be performed.
   *
   * @param Request $request
   *   The current request object.
   *
   * @return bool
   *   TRUE if redirect may be performed.
   */
  public function canRedirect(Request $request) {
    $can_redirect = TRUE;

    $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    if ($route) {
      $is_admin = (bool) $route->getOption('_admin_route');
    }

    if ($request->getScriptName() != 'index.php') {
      // Do not redirect if the root script is not /index.php.
      $can_redirect = FALSE;
    }
    elseif (!$request->isMethod('GET')) {
      // Do not redirect if this is other than GET request.
      $can_redirect = FALSE;
    }
    elseif ($this->state->get('system.maintenance_mode') || defined('MAINTENANCE_MODE')) {
      // Do not redirect in offline or maintenance mode.
      $can_redirect = FALSE;
    }
    elseif (!$this->config->get('global_admin_paths') && !empty($is_admin)) {
      // Do not redirect on admin paths.
      $can_redirect = FALSE;
    }

    return $can_redirect;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestCheckRedirect', 50);
    return $events;
  }
}
