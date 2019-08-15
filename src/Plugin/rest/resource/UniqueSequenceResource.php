<?php

namespace Drupal\unigen\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides a resource to get sequence number
 *
 * @RestResource(
 *   id = "unique_sequence_resource",
 *   label = @Translation("Unique sequence resource"),
 *   uri_paths = {
 *     "canonical" = "/api/unigen/sequence",
 *     "https://www.drupal.org/link-relations/create" = "/api/unigen/sequence",
 *   }
 * )
 */
class UniqueSequenceResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

 /**
  * The request object that contains the parameters.
  *
  * @var \Symfony\Component\HttpFoundation\Request
  */
 protected $request;

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
   * Constructs a new UniqueSequenceResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
  * @param \Symfony\Component\HttpFoundation\Request $request
  *   The request object.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    Request $request,
    QueryFactory $queryFactory,
    EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    $this->request = $request;
    $this->entityQuery = $queryFactory;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('unigen'),
      $container->get('current_user'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity.query'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns sequence number.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post() {
    if ($this->request) {
      // get the parameters.
      $sequence_id = $this->request->get('sid');
      $api_key = $this->request->get('api_key');

      $ip_address = $this->request->getClientIp();
      if(empty($ip_address)) {
        throw new AccessDeniedHttpException();
      }

	  //if anyone is emoty access denied
      if(empty($sequence_id) || empty($api_key)) {
        throw new AccessDeniedHttpException();
      }

	  $is_matched = 0;
	  $config = \Drupal::config('unigen.settings');
      $restapi_credentials = $config->get('restapi_credentials');
      $restapi_credentials_arr = explode("\n", $restapi_credentials);
      if(is_array($restapi_credentials_arr) && count($restapi_credentials_arr)) {
        foreach($restapi_credentials_arr as $row) {
          $columns = explode("|", $row);

          $current_ip_address = (isset($columns[0]) ? $columns[0] : '');
          $current_api_key = (isset($columns[1]) ? $columns[1] : '');
          if($current_ip_address == $ip_address && $current_api_key == $api_key) {
	  	    $is_matched = 1;
	  	    break;
          }
        }
      }

	  //check if api key and ip address is matched
      if(!$is_matched) {
        throw new AccessDeniedHttpException();
      }

      // Process request.
      $sequence = \Drupal::service('unigen.helper');
      $next_sequence_data = $sequence->nextSequence($sequence_id);
      if(!is_array($next_sequence_data) | $next_sequence_data == false) {
        $next_sequence_data = ['text' => '', 'number' => '', 'prefix' => '', 'status' => 0];
      }

      // Configure caching settings.
      $build = [
        '#cache' => [
          'max-age' => 0,
        ],
      ];

      // Return results.
      return (new ResourceResponse($next_sequence_data, 200))->addCacheableDependency($build);
    }
  }

  /**
   * Responds to GET requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    if ($this->request) {
      // get the request parameters.
      $sequence_id = $this->request->get('sid');
      $api_key = $this->request->get('api_key');

      $ip_address = $this->request->getClientIp();
      if(empty($ip_address)) {
        throw new AccessDeniedHttpException();
      }

	  //if anyone is emoty access denied
      if(empty($sequence_id) || empty($api_key)) {
        throw new AccessDeniedHttpException();
      }

	  $is_matched = 0;
	  $config = \Drupal::config('unigen.settings');
      $restapi_credentials = $config->get('restapi_credentials');
      $restapi_credentials_arr = explode("\n", $restapi_credentials);
      if(is_array($restapi_credentials_arr) && count($restapi_credentials_arr)) {
        foreach($restapi_credentials_arr as $row) {
          $columns = explode("|", $row);

          $current_ip_address = (isset($columns[0]) ? $columns[0] : '');
          $current_api_key = (isset($columns[1]) ? $columns[1] : '');
          if($current_ip_address == $ip_address && $current_api_key == $api_key) {
	  		$is_matched = 1;
	  		break;
          }
        }
      }

	  //check if api key and ip address is matched
      if(!$is_matched) {
        throw new AccessDeniedHttpException();
      }

      // Process request.
      $sequence = \Drupal::service('unigen.helper');
      $next_sequence_data = $sequence->nextSequence($sequence_id);
      if(!is_array($next_sequence_data) | $next_sequence_data == false) {
    	$next_sequence_data = ['text' => '', 'number' => '', 'prefix' => '', 'status' => 0];
      }

      // Configure caching settings.
      $build = [
        '#cache' => [
          'max-age' => 0,
        ],
      ];

      // Return results.
      return (new ResourceResponse($next_sequence_data, 200))->addCacheableDependency($build);
    }
  }

}
