<?php

declare(strict_types = 1);

namespace Drupal\emr\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the entity meta entity edit forms.
 */
class EntityMetaForm extends ContentEntityForm {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an EntityMetaForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, RendererInterface $renderer) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {
    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => $this->renderer->render($link)];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('New entity meta %label has been created.', $message_arguments));
      $this->logger('emr')->notice('Created new entity meta %label', $logger_arguments);
    }
    else {
      $this->messenger()->addStatus($this->t('The entity meta %label has been updated.', $message_arguments));
      $this->logger('emr')->notice('Updated new entity meta %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.entity_meta.canonical', ['entity_meta' => $entity->id()]);
  }

}
