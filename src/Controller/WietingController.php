<?php

/**
 * Created by PhpStorm.
 * User: markmcfate
 * Date: 1/28/17
 * Time: 2:44 PM
 */

namespace Drupal\wieting\Controller;

use Drupal\Core\Controller\ControllerBase;

class WietingController extends ControllerBase {

    public function content() {
        return array(
            '#type' => 'markup',
            '#markup' => $this->t('Hello, World!  I am the Wieting!'),
        );
    }

}