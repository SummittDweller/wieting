<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\Common.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

// use Drupal\Core\Action\ActionBase;
// use Drupal\Core\Session\AccountInterface;

/**
 *
 * This Common class defines functions that may be used by more than one Action.
 *
 */

class Common {

  const SYSTEM_ADMIN = "1";     // uid of SYSTEM ADMIN
  const HELP_NEEDED = "249";    // uid of HELP NEEDED
  const MANAGER = "64";         // term ID of MANAGER
  const MONITOR = "65";         // term ID of MONITOR
  const CONCESSIONS = "66";     // term ID of CONCESSIONS
  const TICKET_SELLER = "67";   // term ID of TICKET_SELLER
  const PARTNER = "74";         // term ID of PARTNER
  
  /** getActiveUID --------------------------------------------------------------------------
   *
   * Gets the UID of the ACTIVE user.  If none is set this returns the UID of the currentUser.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|null|static
   */
  public static function getActiveUID( ) {
    if (!$uid = \Drupal::state()->get('wieting_selected_volunteer_id') ?: false) {
      $uid = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    }
    return $uid;
  }
  
  
  /** createOnePerformance --------------------------------------------------------------------------
   *
   * @param $entity
   * @param $title
   * @param string $utc - Performance start time in UTC (GMT time)
   * @param $final
   */
  public static function createOnePerformance($entity, $title, $utc, $final) {

    // Check if the performance already exists, load if not or create if necessary.
    $values = \Drupal::entityQuery('node')
        ->condition('type', 'performance')
        ->condition('title', $title)
        ->execute();
    if ($node_exists = !empty($values)) {
      $nid = $values[key($values)];
      $node = \Drupal\node\Entity\Node::load($nid);         // ...performance exists, load it for update.
      // ksm("Common::createOnePerformance existing node...", $node);
    } else {
      $node = \Drupal\node\Entity\Node::create(array(       // ...performance doesn't exist, create it.
        'type' => 'performance',
        'title' => $title,
        'langcode' => 'en',
        'uid' => '1',
        'status' => 1,
      ));
    }

    // Pass the show's format on to the performance...except if the show is 3D and this is the final performance.
    $format = $entity->get('field_format')->value;
    if ($format === '3D' && $final) { $format = '2D'; }

    // Complete the performance info assigning HELP NEEDED to all roles.
    $node->field_show->entity = $entity;
    $running = intval($entity->get('field_running_time')->value) * 60;
    $end = strtotime($utc) + $running;
    $endT = date("Y-m-d\TH:i:s", $end);
    // ksm("utc...", $utc, "running", $running, "end...", $end, "endT...", $endT);
    $local = strtotime($utc.' UTC');    // returns a "local" timestamp!
    $node->set('field_performance_times', $utc);
    $node->set('field_performance_ends', $endT);
    $node->set('field_final_wieting_performance_', $final);
    $node->set('field_format', $format);
    $node->set('field_performance_date', date("Y-m-d", $local));  // @TODO Kept to overcome Views calendar BUG
    $node->set('field_volunteer_manager', array('target_id' => Common::HELP_NEEDED));
    $node->set('field_volunteer_monitor', array('target_id' => Common::HELP_NEEDED));
    $node->set('field_volunteer_concessions', array('target_id' => Common::HELP_NEEDED));
    $node->set('field_volunteer_ticket_seller', array('target_id' => Common::HELP_NEEDED));
    $node->save();
  }
  
  /** allowedVolunteerRole --------------------------------------------------------------------------
   *
   * @param $uid
   * @param $role
   * @return bool
   */
  public static function allowedVolunteerRole($uid, $role) {
    // Get field data from the user.
    $user = \Drupal\user\Entity\User::load($uid);
    $name = $user->get('name')->value;
    $roles = $user->getRoles( );
    // ksm("allowedVolunteerRole roles...", $roles);
    
    // If the user is blocked...issue a message and return false.
    if (user_is_blocked($name)) {
      drupal_set_message("Sorry, the account for '$name' is currently blocked.", 'warning');
      return false;
    }
  
    // If the user is a manager they can do it all...return true.  If they are a volunteer then keep checking.
    $volunteer = false;
    foreach ($roles as $user_role) {
      if ($user_role === 'manager') {
        return true;                             // managers rock!
      } else if ($user_role === 'volunteer') {
        $volunteer = true;
        break;
      }
    }
    
    // Not a volunteer...issue a message and return false.
    if (!$volunteer) {
      drupal_set_message("Sorry, '$name' is not currently listed as a volunteer.", 'warning');
      return false;
    }
  
    // Get this volunteer's roles
    $vRoles = $user->get('field_volunteer_roles')->getValue( );
    // ksm("allowedVolunteerRole: vRoles...", $vRoles);
    foreach ($vRoles as $key => $vR) {
      if ($role === 'manager' && $vR['target_id'] === Common::MANAGER) { return true; }
      if ($role === 'monitor' && $vR['target_id'] === Common::MONITOR) { return true; }
      if ($role === 'concessions' && $vR['target_id'] === Common::CONCESSIONS) { return true; }
      if ($role === 'ticket_seller' && $vR['target_id'] === Common::TICKET_SELLER) { return true; }
    }
  
    drupal_set_message("Sorry, '$name' is not currently listed for the role of $role.", 'warning');
    return false;
  }
  
  /** isHelpNeeded --------------------------------------------------------------------------
   *
   * @param $role
   * @param $performance
   * @return bool
   */
  public static function isHelpNeeded($role, $performance) {
    $perf = $performance->getTitle( );
    $assigned = $performance->get('field_volunteer_'.$role)->getValue( );
    $assigned_id = $assigned[0]['target_id'];         // @TODO...looks only at the first assigned
    if (strlen($assigned_id) < 1) {
      return true;                                    // nobody assigned, fill it
    } else if ($assigned_id === Common::HELP_NEEDED) {
      return true;                                    // Help Needed...fill it
    } else if ($assigned_id === Common::SYSTEM_ADMIN) {
      return true;                                    // System Admin...fill it
    }
    // ksm("isHelpNeeded perf, assigned, assigned_id, HELP_NEEDED, SYSTEM_ADMIN...", $perf, $assigned, $assigned_id,
    //   Common::HELP_NEEDED, Common::SYSTEM_ADMIN);
    drupal_set_message("Sorry, the role of '$role' is already filled for the '$perf' performance.", 'warning');
    return false;
  }
  
  
  /** allowedPerformanceDate --------------------------------------------------------------------------
   *
   * @param $uid
   * @param $performance
   * @return bool
   */
  public static function allowedPerformanceDate($uid, $performance) {
    // If the ACTIVE user IS the currentUser, then allow overriding available days/times!
    $current_uid = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    if ($uid === $current_uid) { return true; }
    
    // Check this performance against the ACTIVE user's available days/times.
    $user = \Drupal\user\Entity\User::load($uid);
    $name = $user->get('name')->value;
    $available = $user->get('field_available_days_times')->getValue( );
    $utc = $performance->get('field_performance_times')->getValue( );
    $local = strtotime($utc[0]['value'] . " UTC");
    $performance_DOW = date("l", $local);
    $matinee = ($performance->get('field_performance_matinee')->getValue( ) === "0");
    $target = $performance_DOW . ($matinee ? " Matinee" : " Night");
    foreach ($available as $a) {
      if ($target === $a['value']) { return true; }
      // ksm("allowedPerformanceDate a...", $a['value']);
    }
    
    // Nope, not available.
    // ksm("allowedPerformanceDate name, available, utc, local, performance_DOW, matinee, target...", $name, $available, $utc, $local, $performance_DOW, $matinee, $target);
    drupal_set_message("Sorry, '$name' is not generally available for a $target performance.", 'warning');
    return false;
  }
  
  
  /** setPerformanceRole --------------------------------------------------------------------------
   *
   * @param $user
   * @param $performance
   * @param $role
   * @return bool
   */
  public static function setPerformanceRole($uid, $performance, $role) {
    // Get field data from the user and the performance.
    $user = \Drupal\user\Entity\User::load($uid);
    $pid = $performance->id( );
    // drupal_set_message("uid | pid = $uid | $pid");
    // Add the user ID to the performance data
    $performance->set("field_volunteer_".$role, array('target_id' => "$uid"));
    $performance->save( );
    // Add the performance ID to the user data
    // $user->set("field_assigned_".$role, array('target_id' => "$pid"));
    // return;
    // $user->save( );
  }

}

?>
