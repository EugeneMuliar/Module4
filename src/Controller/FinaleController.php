<?php

namespace Drupal\finale\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines FinaleController class.
 */
class FinaleController extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * FormBuilder constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   */
  public function __construct(FormBuilder $form_builder) {
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Get form and send to template.
   *
   * @return array
   *   Return array with variables for templates.
   */
  public function content() {
    $form = $this->formBuilder()
      ->getForm('Drupal\finale\Form\FinaleForm');

    $build = [
      '#theme' => 'awesome-form',
      '#form' => $form,
    ];

    return $build;
  }

}
