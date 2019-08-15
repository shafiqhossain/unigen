<?php

namespace Drupal\ymlp\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Validates the UniqueInteger constraint.
 */
class UniqueSubscriberEmailValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Entity Manager.
   *
   * @var EntityTypeManagerInterface $manager
   */
  protected $entityTypeManager;

  /**
   * The Entity Query.
   *
   * @var QueryFactory $entityQuery
   */
  protected $entityQuery;

  /**
   * Constructs a validator object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity Manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, QueryFactory $query_factory) {
    $this->entityTypeManager = $entity_manager;
    $this->entityQuery = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity)) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */

      // Check if the email value is valid.
      if (!$this->isValid($entity->getEmailAddress())) {
        $this->context->addViolation($constraint->notValid, ['%value' => $entity->getEmailAddress()]);
      }

      // Check if the email value is unique.
      if (!$this->isUnique($entity->getEmailAddress(), $entity)) {
        $this->context->addViolation($constraint->notUnique, ['%value' => $entity->getEmailAddress()]);
      }
    }
  }

  /**
   * Is unique?
   *
   * @param string $value
   */
  private function isUnique($value, $entity) {
    if (!$entity->isNew()) {
      $ids = $this->entityQuery->get('ymlp_subscriber')
        ->condition('mail', $value)
        ->condition('id', $entity->id(), '!=')
        ->execute();
    }
    else {
      $ids = $this->entityQuery->get('ymlp_subscriber')
        ->condition('mail', $value)
        ->execute();
    }

    return (bool) (is_array($ids) && count($ids)>0 ? 0 : 1);
  }

  /**
   * Is unique?
   *
   * @param string $value
   */
  private function isValid($value) {
	return \Drupal::service('email.validator')->isValid($value);
  }

}