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
    // ksm($entity);

    $node = \Drupal\node\Entity\Node::load($entity->id( ));         // ...performance exists, load it for update.
    $title = $entity->getTitle( );

    $msg = "This is \\Drupal\\wieting\\Plugin\\Action\\CalcAvailability\\execute working on '$title'.";
    \Drupal::logger('wieting')->notice($msg);
    // drupal_set_message($msg);

    // If there's no text in the message buffer, initialize it now.
    if (!$buffer = \Drupal\wieting\Plugin\Action\Common::getBufferedText()) {
      $cID = \Drupal::currentUser()->id();
      $account = \Drupal\user\Entity\User::load($cID);  // pass your uid
      $current = $account->getUsername();
      // $html = "<html><header><title>Auto-Schedule Log for $title</title></header><body>";
      // \Drupal\wieting\Plugin\Action\Common::setBufferedText($html);      // initialize the message buffer
      $message = "$current has initiated auto-schedule performance changes...";
      \Drupal\wieting\Plugin\Action\Common::setBufferedText($message);
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
          $msg = "$name with a score of $score is available as a MANAGER and is now assigned to work '$title'.";
          \Drupal\wieting\Plugin\Action\Common::appendBufferedText($msg);
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
          $msg = "$name with a score of $score is available as a MONITOR and is now assigned to work '$title'.";
          \Drupal\wieting\Plugin\Action\Common::appendBufferedText($msg);
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
          $msg = "$name with a score of $score is available for CONCESSIONS and is now assigned to work '$title'.";
          \Drupal\wieting\Plugin\Action\Common::appendBufferedText($msg);
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
          $msg = "$name with a score of $score is available as a TICKET SELLER and is now assigned to work '$title'.";
          \Drupal\wieting\Plugin\Action\Common::appendBufferedText($msg);
          break;
        }
      }
    }

  $text = \Drupal\wieting\Plugin\Action\Common::getBufferedText( ); // . "</body></html>";
  $logname = "auto-schedule " . $title . ".log";
  $logpath = "public://" . $logname;
  $log = file_save_data($text, $logpath, FILE_EXISTS_REPLACE);
//  $link = $log->url( );
  $msg = t("A log of auto-schedule activity for '@title' has been written to '@logname'.", array('@title' => $title, '@logname' => $logpath));
  drupal_set_message($msg, 'info');

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
