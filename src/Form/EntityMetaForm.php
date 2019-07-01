<?php

declare(strict_types = 1);

namespace Drupal\emr\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the entity meta entity edit forms.
 */
class EntityMetaForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): void {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
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
