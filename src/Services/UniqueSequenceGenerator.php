<?php

namespace Drupal\unigen\Services;

use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Lock\LockBackendInterface;


/**
 * Unique sequence generator utility routines
 */
class UniqueSequenceGenerator {
  use StringTranslationTrait;

  /**
   * The Entity Manager.
   *
   * @var EntityTypeManagerInterface $entityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The Entity Query.
   *
   * @var QueryFactory $queryFactory
   */
  protected $queryFactory;

  /**
   * @var LockBackendInterface $lockBackend
   */
  protected $lockBackend;

  /**
   * {@inheritdoc}
   */
  public function __construct(QueryFactory $queryFactory, EntityTypeManagerInterface $entityTypeManager, LockBackendInterface $lockBackend) {
    $this->entityQuery = $queryFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->lockBackend = $lockBackend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity_type.manager'),
      $container->get('lock')
    );
  }

  /**
   * Get next sequence by entity.
   * This will increase the sequence and return with prefix i.e prefix.sequence
   *
   * @param string $entity_type
   *   Type of entity.
   * @param string $bundle
   *   The bundle name.
   * @param string $field_name
   *   The field name.
   * @param string $sequence_id
   *   The sequence id.
   *
   * @return array
   *   The unique sequence string.
   */
  public function entityNextSequence($entity_type='', $bundle='', $field_name = '', $sequence_id='') {
    $data = ['text' => '', 'number' => '', 'prefix' => '', 'status' => 0];
    $next_id = '';
    while(true) {
      $data = $this->nextSequence($sequence_id);
      $next_id = (isset($data['text']) ? $data['text'] : '');

      //check if new bank id, already exists or not
      if(!empty($next_id) && $next_id != false) {
		$query = $this->entityQuery->get($entity_type)
		  ->condition('type', $bundle, '=')
		  ->condition($field_name.'.unitext', $next_id, '=')
		  ->range(0, 1);
		$sids = $query->execute();

		//if not found, break
		if(!count($sids)) break;
      }  //if
    }  //while

	return $data;
  }

  /**
   * Get next sequence by sequence id.
   * This will increase the sequence and return with prefix i.e prefix.sequence
   *
   * @param string $sequence_id
   *   The sequence id.
   *
   * @return array
   *   The unique sequence string, prefix and number.
   */
  public function nextSequence($sequence_id='') {
    if(empty($sequence_id)) return;
    $sequence = FALSE;
    $data = ['text' => '', 'number' => '', 'prefix' => '', 'status' => 0];

    if($this->lockBackend->acquire('unique_sequence_generator')) {
	  $sequence = $this->entityTypeManager->getStorage('unique_sequence')->load($sequence_id);

	  //check if this sequence exists or not
	  if($sequence != false) {
	    $sequence_no = (isset($sequence->sequence_no) && !empty($sequence->sequence_no) ? intval($sequence->sequence_no) : 0);
	    $sequence_prefix = (isset($sequence->prefix) && !empty($sequence->prefix) ? $sequence->prefix : '');
	    $next_sequence_no = $sequence_no + 1;

	    $sequence->set('sequence_no', $next_sequence_no);
	    $sequence->save();

		$sequence_str = $sequence_prefix.$next_sequence_no;
    	$data = ['text' => $sequence_str, 'number' => $next_sequence_no, 'prefix' => $sequence_prefix, 'status' => 1];
	  }
	  else {
	    //sequence did not found, add a blank record
	    $next_sequence_no = 1;
	    $sequence_prefix = '';

		$values = [
		  'id' => $sequence_id,
		  'label' => $sequence_id,
		  'prefix' => '',
		  'sequence_no' => $next_sequence_no,
		];
	    $sequence = $this->entityTypeManager->getStorage('unique_sequence')->create($values);
	    $sequence->save();
		$sequence_str = $sequence_prefix.$next_sequence_no;
    	$data = ['text' => $sequence_str, 'number' => $next_sequence_no, 'prefix' => $sequence_prefix, 'status' => 1];
	  }

      $this->lockBackend->release('unique_sequence_generator');

      return $data;
    }

    return FALSE;
  }

}