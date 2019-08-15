<?php

namespace Drupal\ymlp\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the submitted email is a unique email address.
 *
 * @Constraint(
 *   id = "UniqueSubscriberEmail",
 *   label = @Translation("Unique Subscriber Email", context = "Validation"),
 *   type = "string"
 * )
 */
class UniqueSubscriberEmail extends Constraint {

  // Message to show if the value is not valid.
  public $notValid = 'Email address %value is already exists!';

 // Message to show if the value is not unique.
  public $notUnique = 'Email address %value is already exists!';

}