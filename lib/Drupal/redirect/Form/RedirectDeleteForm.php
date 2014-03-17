<?php

namespace Drupal\redirect\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RedirectDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * Constructs a NodeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    parent::__construct($entity_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete %redirect?', array('%redirect' => $this->entity->label()));
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);

    // @todo Convert to getCancelRoute() after http://drupal.org/node/1987778.
    $uri = $this->entity->urlInfo();
    $actions['cancel']['#route_name'] = $uri['route_name'];
    $actions['cancel']['#route_parameters'] = $uri['route_parameters'];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelRoute() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    $this->entity->delete();
    drupal_set_message(t('The redirect %redirect has been deleted.', array('%redirect' => $this->entity->label())));
    //$form_state['redirect_route']['route_name'] = '<front>';
  }

}
