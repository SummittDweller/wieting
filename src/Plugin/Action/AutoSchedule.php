<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\AutoSchedule.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use \Drupal\Core\Action\ActionBase;
use \Drupal\Core\Session\AccountInterface;
use \Drupal\wieting\Plugin\Action\Common;
use \Drupal\user\Entity\User;


/**
 *
 * Action to auto-schedule available volunteers for one selected Performance.
 *
 * @Action(
 *   id = "auto_schedule",
 *   label = @Translation("Auto-schedule volunteers for selected performance(s)"),
 *   type = "node"
 * )
 */
class AutoSchedule extends ActionBase {

  /**
   * {@inheritdoc}
   *
   * Note that this function will attempt to modify the $entity (performance) so that
   * $entity has to be loaded here so that it can be altered within!
   *
   */
  public function execute($entity = NULL) {
    // drupal_set_message('This is \Drupal\wieting\Plugin\Action\CalcAvailability\execute.');
    // ksm($entity);
    $node = \Drupal\node\Entity\Node::load($entity->id( ));         // ...performance exists, load it for update.
  
    // If there's no text in the message buffer, initialize it now.
    if (!$buffer = \Drupal\wieting\Plugin\Action\Common::getBufferedText()) {
      $cID = \Drupal::currentUser()->id();
      $account = \Drupal\user\Entity\User::load($cID);  // pass your uid
      $current = $account->getUsername();
      $message = "$current has initiated the following auto-schedule performance changes: \n\n";
      \Drupal\wieting\Plugin\Action\Common::setBufferedText($message);      // initialize the message buffer
    }
    
    // Update availability scores
    \Drupal\wieting\Plugin\Action\Common::updateAvailability($node);
    
    // Fetch the sorted available volunteers list
    $values = $entity->get('field_availability_scores')->getValue( );
    // ksm($values);
    
    // Manager...
    if (\Drupal\wieting\Plugin\Action\Common::isHelpNeeded('manager', $entity, TRUE)) {
      foreach ($values as $n => $value) {
        list($name, $uid, $score) = explode('|', $value['value']);
        // drupal_set_message('$n, $value, $name, $uid and $score are '.$n.', '.$value['value'].', '.$name.', '.$uid.', '.$score);
        if (\Drupal\wieting\Plugin\Action\Common::allowedVolunteerRole($uid, 'manager', TRUE)) {
          \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $node, 'manager', TRUE);
          unset($values[$n]);
          break;
        }
      }
    }
  
    // Monitors...
    if (\Drupal\wieting\Plugin\Action\Common::isHelpNeeded('monitor', $entity, TRUE)) {
      foreach ($values as $n => $value) {
        list($name, $uid, $score) = explode('|', $value['value']);
        // drupal_set_message('$n, $value, $name, $uid and $score are '.$n.', '.$value['value'].', '.$name.', '.$uid.', '.$score);
        if (\Drupal\wieting\Plugin\Action\Common::allowedVolunteerRole($uid, 'monitor', TRUE)) {
          \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $node, 'monitor', TRUE);
          unset($values[$n]);
          break;
        }
      }
    }

    // Concessions...
    if (\Drupal\wieting\Plugin\Action\Common::isHelpNeeded('concessions', $entity, TRUE)) {
      foreach ($values as $n => $value) {
        list($name, $uid, $score) = explode('|', $value['value']);
        // drupal_set_message('$n, $value, $name, $uid and $score are '.$n.', '.$value['value'].', '.$name.', '.$uid.', '.$score);
        if (\Drupal\wieting\Plugin\Action\Common::allowedVolunteerRole($uid, 'concessions', TRUE)) {
          \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $node, 'concessions', TRUE);
          unset($values[$n]);
          break;
        }
      }
    }

    // Ticket Seller..
    if (\Drupal\wieting\Plugin\Action\Common::isHelpNeeded('ticket_seller', $entity, TRUE)) {
      foreach ($values as $n => $value) {
        list($name, $uid, $score) = explode('|', $value['value']);
        // drupal_set_message('$n, $value, $name, $uid and $score are '.$n.', '.$value['value'].', '.$name.', '.$uid.', '.$score);
        if (\Drupal\wieting\Plugin\Action\Common::allowedVolunteerRole($uid, 'ticket_seller', TRUE)) {
          \Drupal\wieting\Plugin\Action\Common::setPerformanceRole($uid, $node, 'ticket_seller', TRUE);
          unset($values[$n]);
          break;
        }
      }
    }

  }
  
  /**
   * Checks object access.
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $status = \Drupal\wieting\Plugin\Action\Common::hasAccess($object);
    return $status;
  }

}

?>
