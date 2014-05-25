<?php

/**
 * @file
 * Contains \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber.
 */

namespace Drupal\redirect\EventSubscriber;

use Drupal\redirect\RedirectRepository;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Terminate subscriber for controller requests.
 */
class RedirectTerminateSubscriber implements EventSubscriberInterface {

  /** @var  \Drupal\redirect\RedirectRepository */
  protected $redirectRepository;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\redirect\RedirectRepository $redirect_repository
   *   The redirect entity repository.
   */
  public function __construct(RedirectRepository $redirect_repository) {
    $this->redirectRepository = $redirect_repository;
  }

  /**
   * Logs if redirect happened.
   *
   * @param \Symfony\Component\HttpKernel\Event\PostResponseEvent $event
   *   The event to process.
   */
  public function onKernelTerminateLogRedirect(PostResponseEvent $event) {
    $redirect_id = $event->getResponse()->headers->get('X-Redirect-ID');
    if (!empty($redirect_id) && $redirect = $this->redirectRepository->load($redirect_id)) {
      $redirect->setLastAccessed(REQUEST_TIME);
      $redirect->setCount($redirect->getCount() + 1);
      $redirect->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::TERMINATE][] = array('onKernelTerminateLogRedirect', 50);
    return $events;
  }
}
