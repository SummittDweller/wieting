<?php

/**
 * Created by PhpStorm.
 * User: markmcfate
 * Date: 1/28/17
 * Time: 2:44 PM
 */

namespace Drupal\wieting\Controller;

/* The following lifted from https://atendesigngroup.com/blog/storing-session-data-drupal-8 on 5-Apr-2017
// But it doesn't work...issues with static stuff!

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PrivateTempStoreFactory;


class WietingController extends ControllerBase {
  
  protected $tempStore;
  
  // Pass the dependency to the object constructor
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('wieting');
  }
  
  // Uses Symfony's ContainerInterface to declare dependency to be passed to constructor
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }
  
  // Save some temporary data
  public function wieting_set($var, $value) {
    $this->tempStore->set($var, $value);
    // Do other stuff, return a render array, etc...
  }
  
  // Read some temporary data
  public function wieting_get($var) {
    return $this->tempStore->get($var);
    // Do other stuff, return a render array, etc...
  }
  
}  */

use Drupal\Core\Controller\ControllerBase;

class WietingController extends ControllerBase {

    public function content() {
        return array(
            '#type' => 'markup',
            '#markup' => $this->t('Hello, World!  I am the Wieting!'),
        );
    }
}
