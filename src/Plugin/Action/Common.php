<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\Common.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\wieting\Controller\WietingController;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;


/**
 *
 * This Common class defines functions that may be used by more than one Action.
 *
 */

class Common {

  const SIX_DAYS = 518400;
  const TWO_DAYS = 172800;

  const SYSTEM_ADMIN = "1";     // uid of SYSTEM ADMIN
  const HELP_NEEDED = "249";    // uid of HELP NEEDED
  const FLOATER = "206";        // uid of FLOATER
  const PARENT = "266 ";        // uid of PARENT
  const CLEAR = "306";          // uid of CLEAR ACTIVE LIST
  CONST REPEAT = "307";         // uid of REPEAT SELECTION

  const MANAGER = "64";         // term ID of MANAGER
  const MONITOR = "65";         // term ID of MONITOR
  const CONCESSIONS = "66";     // term ID of CONCESSIONS
  const TICKET_SELLER = "67";   // term ID of TICKET_SELLER
  const PARTNER = "74";         // term ID of PARTNER

/** dispatchVolunteerReminders ------------------------------------------------------------
 *
 */
  public static function dispatchVolunteerReminders( ) {
    // Find all published performances...
    $values = \Drupal::entityQuery('node')
      ->condition('type', 'performance')
      ->condition('status', 1)                       // published
      ->execute();

    // Now test to find any that are less than 6 days or 2 days away WITH pending notifications.
    $now = time();
    $found = 0;

    foreach ($values as $n => $nid) {
      $node = \Drupal\node\Entity\Node::load($nid);
      $title = $node->getTitle( );
      // drupal_set_message("Found published performance node '$nid' with a title of '$title'.");

      // Get performance times
      $val = $node->get('field_performance_times')->getValue();
      $perfTime = $val[0]['value'];
      $utc = strtotime($perfTime . " UTC");
      $diff = $utc - $now;
      // drupal_set_message("__Now, utc, and diff are: $now, $utc, $diff.");

      // Test for less than 6 days out... if yes, fetch the reminders_pending counter.
      if ($diff < Common::SIX_DAYS) {
        $value = $node->get('field_reminders_pending')->getValue( );
        $pending = (int) $value[0]['value'];
        if ($pending == 2) {
          drupal_set_message("Published performance node '$nid' with a title of '$title' is less than 6 days away and has a pending reminder.");
          $found += Common::remindAssignedVolunteers($node);
        } else if (($diff < Common::TWO_DAYS) && ($pending > 0)) {
          drupal_set_message("Published performance node '$nid' with a title of '$title' is less than TWO days away and has a pending reminder!");
          $found += Common::remindAssignedVolunteers($node);
        }
      }
    }
  
    $msg = "$found volunteer reminders were processed and dispatched.";
    drupal_set_message($msg);
    \Drupal::logger('wieting')->notice($msg);
   
  }


  /** remindAssignedVolunteers -----------------------------------------------------------------
   *
   * @param $performance - The performance node.
   * @param $testing - Set TRUE for testing only.
   *
   */
  public static function remindAssignedVolunteers($performance, $testing=FALSE) {
    $done = 0;
    
    // ksm($performance);
    $count = $performance->get('field_reminders_pending')->getValue( );
    $pending = $count[0]['value'];

    $roles = array("as the manager" => $performance->get('field_volunteer_manager')->getValue(),
                   "as monitors" => $performance->get('field_volunteer_monitor')->getValue(),
                   "in concessions" => $performance->get('field_volunteer_concessions')->getValue(),
                   "as the ticket seller" => $performance->get('field_volunteer_ticket_seller')->getValue());

    $message = "This is a @friendly reminder that @partner @are scheduled to work @role for the Wieting performance on @title.  You can check and manage your schedule at https://wieting.tamatoledo.com/calendar-assignments/month. Please do not reply to this email unless you need assistance with your schedule.\r\n\r\n\r\nThank you for being one of THE BEST, a Wieting Theatre volunteer!";

    // For each volunteer role...
    foreach ($roles as $role => $volunteers) {
      $vars['@friendly'] = 'friendly';
      $vars['@are'] = 'are';

      if (substr($role, -1) === 's') {
        $team = TRUE;
        $vars['@partner'] = 'you and your partner';
      } else {
        $team = FALSE;
        $vars['@partner'] = 'you';
      }
      $vars['@role'] = $role;
      $vars['@title'] = $title = $performance->getTitle();
  
      // For each volunteer in the role...
      foreach ($volunteers as $volunteer) {
        // ksm($volunteer);
        $pID = FALSE;
        $mID = FALSE;
        $uid = $volunteer['target_id'];

        // Is this a SPECIAL volunteer, aka FLOATER or PARENT?
        if ($uid === Common::FLOATER || $uid === Common::PARENT) {
          $vars['@friendly'] = 'SPECIAL';
          $special = 'NOBODY';
          $vars['@are'] = 'is';

          if ($role === 'in concessions') {
            if ($s = $performance->get('field_special_concessions')->getValue( )) {
              $special = $s[0]['value'];
            }
          } else if ($role === 'as monitors') {
            if ($s = $performance->get('field_special_monitors')->getValue()) {
              $special = $s[0]['value'];
            }
          }

          $vars['@partner'] = $special;
          $vars['@are'] = 'are';
          
          // Inform the manager too!
          $mgr = $performance->get('field_volunteer_manager')->getValue( );
          $mID = (int) $mgr[0]['target_id'];

          // ksm("special...", $special);

        // Not a special...
        } else if ($user = \Drupal\user\Entity\User::load($uid)) {
          if ($team && ($partner = $user->get('field_has_partner')->getValue())) {       // role needs a partner
            $pID = (int) $partner[0]['target_id'];
          }

        // Could not load the volunteer...
        } else {
          continue;
        }

        $msg = t($message, $vars);
        
        Common::dispatchReminder($uid, $user, $title, $msg, $testing);
        $done++;
        
        // If necessary, send the partner a reminder...
        if ($pID && ($user = \Drupal\user\Entity\User::load($pID))) {
          Common::dispatchReminder($pID, $user, $title, $msg, $testing);
          $done++;
        }
  
        // If necessary, send the manager a SEPCIAL reminder...
        if ($mID && ($user = \Drupal\user\Entity\User::load($mID))) {
          Common::dispatchReminder($mID, $user, $title, $msg, $testing);
          $done++;
        }

      }
    }
  
    // Decrement the number of pending reminders and update
    if (!$testing) {
      $pending--;
      $performance->set('field_reminders_pending', $pending);
      $performance->save();
    }

    return $done;
  }
  
  
  /** dispatchReminder -------------------------------------------------------------
   *
   * @param $uid
   * @param $user
   * @param $title
   * @param $message
   * @param $testing - Set TRUE when testing!
   *
   * @return bool|void
   *
   */
  public static function dispatchReminder($uid, $user, $title, $message, $testing=FALSE) {
    $name = $user->getUsername();
    $mail = $user->getEmail();
    // Trap 'bogus' email addresses and HELP_NEEDED.
    if ($testing) {
      $mail = 'mark@tamatoledo.net';
    } else if ($uid === HELP_NEEDED || $uid === FLOATER || $uid === PARENT) {
      $mail = 'toledowieting@gmail.com';
    } else if (strpos("_$mail", 'bogus') > 0) {
      $msg = "Volunteer $name has a bogus email address ($mail) so no reminder could be dispatched for $title!";
      drupal_set_message($msg, 'warning');
      \Drupal::logger('wieting')->warning($msg);
      return;
    }

    // Send the reminder
    return self::sendMail( 'volunteer_reminder', $message, $name, $mail);

  }


  /** sendMail
   *
   * @param $key
   * @param $message
   * @param $name
   * @param $to
   * @return bool
   */
  public static function sendMail($key, $message, $name, $to) {
    $mailManager = \Drupal::service('plugin.manager.mail');
    $params['message'] = $message;

    // Dispatch it
    $result = $mailManager->mail('wieting', $key, $to, 'en', $params, NULL, true);

    // If it worked return true.  If not, report.
    if (!$result['result']) {
      $msg = "There was a problem sending a $key email to $name via $to.";
      drupal_set_message($msg, 'error');
      \Drupal::logger('wieting')->error($msg);
      return false;
    } else {
      $msg = "A $key email was successfully dispatched to $name via $to.";
      drupal_set_message($msg);
      \Drupal::logger('wieting')->notice($msg);
      return true;
    }

  }
  
  
  /** getBufferdedText --------------------------------------------------------------------------
   *
   * Gets the buffered text from the Wieting tempstore and wieting_buffered_text.
   * If there is no text this function returns FALSE.
   *
   * @return bool|string
   */
  public static function getBufferedText( ) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('wieting');
    if ($text = $tempstore->get('wieting_buffered_text')) {
      if (strlen($text) > 0) {
        return $text;
      }
    }
    return FALSE;
  }


  /** setBufferdedText --------------------------------------------------------------------------
   *
   * Sets the buffered text from the Wieting tempstore and wieting_buffered_text.
   *
   * @return bool|string
   */
  public static function setBufferedText($text) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('wieting');
    $tempstore->set(('wieting_buffered_text'), $text);
  }


  /** appendBufferdedText --------------------------------------------------------------------------
   *
   * Appends $text to the buffered text in Wieting tempstore and wieting_buffered_text.
   */
  public static function appendBufferedText($text) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('wieting');
    if ($buffer = Common::getBufferedText()) {
      $buffer .= "\n\n" . $text;
    } else {
      $buffer = $text;
    }
    Common::setBufferedText($buffer);
  }
  
  
  /** getActiveUID --------------------------------------------------------------------------
   *
   * Gets the UID of the ACTIVE user.  If none is set this returns FALSE.  Use advanceActiveUID to
   * advance the ACTIVE user ID list.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|null|static
   */
  public static function getActiveUID( ) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('wieting');
    if ($uid_list = $tempstore->get('wieting_selected_volunteers')) {
      return $uid_list[0];
    }
    return FALSE;
  }
  
  /** advanceActiveUID --------------------------------------------------------------------------
   *
   * Advamces the ACTIVE user ID unless there is only one ACTIVE ID and it is the currentUser.
   *
   */
  public static function advanceActiveUID( ) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('wieting');
    if ($uid_list = $tempstore->get('wieting_selected_volunteers')) {
      if ((count($uid_list) > 1) || !self::activeIsCurrent( )) {
        array_shift($uid_list);
        $tempstore->set('wieting_selected_volunteers', $uid_list);
      }
    }
    return;
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
      $new = FALSE;
      $nid = $values[key($values)];
      $node = \Drupal\node\Entity\Node::load($nid);         // ...performance exists, load it for update.
      // ksm("Common::createOnePerformance existing node...", $node);
    } else {
      $new = TRUE;
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
    $node->set('field_reminders_pending',2);
    if ($new) {
      $node->set('field_volunteer_manager', array('target_id' => Common::HELP_NEEDED));
      $node->set('field_volunteer_monitor', array('target_id' => Common::HELP_NEEDED));
      $node->set('field_volunteer_concessions', array('target_id' => Common::HELP_NEEDED));
      $node->set('field_volunteer_ticket_seller', array('target_id' => Common::HELP_NEEDED));
    }
    $node->save();
  }
  
  /** allowedVolunteerRole --------------------------------------------------------------------------
   *
   * @param $uid
   * @param $role
   * @param $silent
   * @return bool
   */
  public static function allowedVolunteerRole($uid, $role, $silent = FALSE) {
    // Get field data from the user.
    $user = \Drupal\user\Entity\User::load($uid);
    $name = $user->get('name')->value;
    $roles = $user->getRoles( );
    // ksm("allowedVolunteerRole roles...", $roles);
    
    // If the user is blocked...issue a message and return false.
    if (user_is_blocked($name)) {
      if (!$silent) { drupal_set_message("Sorry, the account for '$name' is currently blocked.", 'warning'); }
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
      if (!$silent) { drupal_set_message("Sorry, '$name' is not currently listed as a volunteer.", 'warning'); }
      return false;
    }
  
    // Get this volunteer's roles
    $vRoles = $user->get('field_volunteer_roles')->getValue( );
    $partner = FALSE;
    // ksm("allowedVolunteerRole: vRoles...", $vRoles);
    foreach ($vRoles as $key => $vR) {
      if ($role === 'manager' && $vR['target_id'] === Common::MANAGER) { return TRUE; }
      if ($role === 'monitor' && $vR['target_id'] === Common::MONITOR) { return TRUE; }
      if ($role === 'concessions' && $vR['target_id'] === Common::CONCESSIONS) { return TRUE; }
      if ($role === 'ticket_seller' && $vR['target_id'] === Common::TICKET_SELLER) { return TRUE; }
      if ($vR['target_id'] === Common::PARTNER) { $partner = TRUE; }
    }
    
    // Nothing yet... if this is a concessions or monitor assignment and the ACTIVE volunteer is a partner,
    // make a special assignment in this case.
    if (($role === 'concessions' || $role === 'monitor') && $partner) { return TRUE; }
    
    if (!$silent) { drupal_set_message("Sorry, '$name' is not currently listed for the role of $role.", 'warning'); }
    return false;
  }
  
  
  /** isPartner -------------------------------------------------------------------------------------
   *
   * @param $user - The volunteer's user object/
   * @return bool
   */
  public static function isPartner($user) {
    $vRoles = $user->get('field_volunteer_roles')->getValue( );
    // $roles = $user->getRoles( );
    // $name = $user->get('name')->value;
    foreach ($vRoles as $key => $vR) {
      if ($vR['target_id'] === Common::PARTNER) { return TRUE; }
      // drupal_set_message("$name has the role of $user_role");
    }
    return false;
  }
  
  
  /** specialPartnerAssignment --------------------------------------------------------------------------
   *
   * @param $uid
   * @param $role
   * @param $performance
   * @return bool|string
   *
  public static function specialPartnerAssignment($uid, $role, $performance) {
    if ($role != 'concessions' && $role != 'monitor')  { return FALSE; }

    // Get field data from the user.
    $user = \Drupal\user\Entity\User::load($uid);
    $name = $user->get('name')->value;
    $roles = $user->getRoles( );
    
    // If the user is blocked...issue a message and return false.
    if (user_is_blocked($name)) {
      drupal_set_message("Sorry, the account for '$name' is currently blocked.", 'warning');
      return false;
    }
  
    $partner = FALSE;
  
    // If the user is a manager they can do it all...replace the partner and return a special string.  If they are a
    // volunteer then keep checking.
    $volunteer = false;
    foreach ($roles as $user_role) {
      if ($user_role === 'manager' || $user_role === 'volunteer') {
        $volunteer = true;
        break;
      }
    }
    
    // Not a volunteer...issue a message and return false.
    if (!$volunteer) {
      drupal_set_message("Sorry, '$name' is not currently listed as a volunteer.", 'warning');
      return false;
    }
    
    // Get this volunteer's roles...see if they can be a partner
    $vRoles = $user->get('field_volunteer_roles')->getValue( );
    foreach ($vRoles as $key => $vR) {
      if ($vR['target_id'] === Common::PARTNER || $vR['target_id'] === Common::MANAGER) {
        $partner = TRUE;
      }
    }
    
    if (!$partner) { return FALSE; }
    
    // OK, this is a concessions or monitor assignment and the ACTIVE volunteer can be a partner,
    // make a special assignment in this case.  @TODO
    $special = "Making a special $role assignment.";
    return $special;
    
  }
  
  
  /** isHelpNeeded --------------------------------------------------------------------------
   *
   * @param $role
   * @param $performance
   * @param $strict - Set TRUE if admin's are NOT allowed to override!
   * @return bool
   */
  public static function isHelpNeeded($role, $performance, $strict = FALSE) {
    // Get the current user and check admin permission
    $user = \Drupal::currentUser();
    if ($strict) {
      $admin = FALSE;
    } else {
      $admin = $user->hasPermission('manage performances');
    }
  
    $perf = $performance->getTitle( );
    $assigned = $performance->get('field_volunteer_'.$role)->getValue( );
    $assigned_id = $assigned[0]['target_id'];         // @TODO...looks only at the first assigned
    if ($admin || strlen($assigned_id) < 1) {
      return true;                                    // current user is admin or nobody assigned, fill it
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
    // This function is NOT called during auto-scheduling so I'm commenting out the check and returning
    // true in all cases.  Effectively a volunteer's DOW selections only effect auto-scheduling.
    
    /* If the ACTIVE user IS the currentUser, then allow overriding available days/times!
    $current_uid = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    if ($uid === $current_uid) { return true; } */

    // Check this performance against the ACTIVE user's available days/times.
    $user = \Drupal\user\Entity\User::load($uid);
    $name = $user->get('name')->value;
    $target = NULL;

    if (Common::normalPerformanceDate($user, $performance, $target)) {
      return true;
    } else {
      drupal_set_message("Note that '$name' is not generally available for a $target performance.", 'warning');
      // return FALSE;
    }
  
    return true;
  
  }


  /** normalPerformanceDate --------------------------------------------------------------------------
   *
   * @param $user
   * @param $performance
   * @param $target
   * @return bool
   */
  public static function normalPerformanceDate($user, $performance, &$target) {
    // Check this performance against the user's available days/times.
    $available = $user->get('field_available_days_times')->getValue( );
    $utc = $performance->get('field_performance_times')->getValue( );
    $local = strtotime($utc[0]['value'] . " UTC");
    $performance_DOW = date("l", $local);
    $matinee = ($performance->get('field_performance_matinee')->getValue( ) === "0");
    $target = $performance_DOW . ($matinee ? " Matinee" : " Night");
    foreach ($available as $a) {
      if ($target === $a['value']) { return true; }
    }
    return false;
  }

  
  /** activeIsCurrent
   *
   *  Returns true if the ACTIVE user is also the current user.
   */
  public static function activeIsCurrent( ) {
    $current = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    if ($active = self::getActiveUID( )) {
      if ($active === $current) {
        return TRUE;
      }
    }
    return FALSE;
  }
  
  
  /** setPerformanceRole --------------------------------------------------------------------------
   *
   * @param $uid
   * @param $performance
   * @param $role
   * @return bool
   */
  public static function setPerformanceRole($uid, $performance, $role, $silent = FALSE) {

    // Get field data from the user and the performance.
    $user = \Drupal\user\Entity\User::load($uid);
    $username = $user->getUsername( );
    $pid = $performance->id( );
    $title = $performance->getTitle( );
    
    if (self::activeIsCurrent( )) {
      $msg = "You are ";
    } else {
      $msg = $user->get('name')->value . " is ";
    }
  
    $node = \Drupal\node\Entity\Node::load($pid);       // ...performance exists, load it for update.
  
    // Remove the user ID from the blocked volunteers data if it's already there
    if ($role === 'unblock') {      // Remove the user ID from the performance's list of blocked volunteers
      $blocked_volunteers = $performance->get('field_blocked_volunteers')->getValue();
      // ksm("setPerformanceRole blocked_volunteers... ", $blocked_volunteers);
      foreach ($blocked_volunteers as $key => $blocked) {
        if ($blocked['target_id'] == $uid) {
          unset($blocked_volunteers[$key]);
          if (!$silent) {
            drupal_set_message("$msg" . "no longer blocked on $title.");
          }
          $mail = " -- Removed block for $username on $title.";
        }
      }
      $node->set('field_blocked_volunteers', $blocked_volunteers);
      
    // Add the user ID to the blocked volunteers data if it's not already there
    } else if ($role === 'blocked') {      // Add the user ID to the performance's list of blocked volunteers
      $blocked_volunteers = $performance->get('field_blocked_volunteers')->getValue( );
      // ksm("setPerformanceRole blocked_volunteers... ", $blocked_volunteers);
      foreach ($blocked_volunteers as $blocked) {
        if ($blocked['target_id'] == $uid) {
          if (!$silent) { drupal_set_message("$msg already blocked from volunteering on $title."); }
          return;
        }
      }
      $blocked_volunteers[] = array('target_id' => $uid);
      $mail = " -- Blocked $username from volunteering on $title.";
      if (!$silent) { drupal_set_message("$msg now blocked from volunteering on $title."); }
      $node->set('field_blocked_volunteers', $blocked_volunteers);

    // Substitute the user ID into the performance $role.
    } else {
      $previous = $node->get("field_volunteer_" . $role)->getValue( );
      $pID = (int) $previous[0]['target_id'];
      $account = \Drupal\user\Entity\User::load($pID);
      $prevVol = $account->getUsername( );
      $mail = "\n     -- Changed $role role for $title from $prevVol to $username.";
      Common::appendBufferedText($mail);

      $node->set("field_volunteer_" . $role, array('target_id' => "$uid"));
    }

    /*
    if ($mail) {
      $message = "$current has initiated the following performance changes: \n\n\n" . $mail;
      Common::appendBufferedText($message);
      // self::sendMail('performance_change', $message, $current, 'toledowieting@gmail.com');
    }
    */

    $node->save( );

  }
  
  
  /** updateAvailability(&$entity) ---------------------------------------------------
   *
   * @param $entity - The performance node to be updated.
   */
  public static function updateAvailability(&$entity) {
    $times = $entity->get('field_performance_times')->getValue();
    $utcTarget = strtotime($times[0]['value'] . " UTC");
  
    // Find all performances (published or not) within +/- WINDOW days of this performance
    $performances = $list = \Drupal::entityQuery('node')
      ->condition('type', 'performance')
      ->execute();
    // ksm($performances);
  
    $scores = array();          // empty array of volunteer scores
    $names = array();
    $availability = array();
    $inWindow = array();
  
    // Drop performances that are outside the calculation WINDOW
    foreach ($list as $n => $perf) {
      $node = \Drupal\node\Entity\Node::load($perf);
      $title = $node->getTitle();
      $val = $node->get('field_performance_times')->getValue();
      $perfTime = $val[0]['value'];
      $utc = strtotime($perfTime . " UTC");
      $diff = $utcTarget - $utc;
      // ksm($utcTarget, $utc, $diff, CalcAvailability::WINDOW);
      if ($diff < -CalcAvailability::WINDOW || $diff > CalcAvailability::WINDOW) {
        unset($performances[$n]);
      } else {
        $days = (int) $diff / CalcAvailability::ONE_DAY;
        $inWindow[$days] = $node;
      }
    }    // ksm($performances);
  
    // Find all active volunteers...
    $volunteers = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('roles', 'volunteer')
      ->execute();
    // $volunteers = User::loadMultiple($ids);
    // ksm($volunteers);
  
    // Get the list of volunteers blocked from this performance
    $blocked_volunteers = $entity->get('field_blocked_volunteers')->getValue();
    // ksm($volunteers);
    // ksm($blocked_volunteers);
  
    // Loop on all active volunteers.
    foreach ($volunteers as $v => $vol) {
      // drupal_set_message("v and vol are: $v and $vol", 'info');
    
      // If this volunteer is blocked from the target performance, skip them entirely.
      $matched = FALSE;
      foreach ($blocked_volunteers as $key => $target) {
        $blocked_id = $target['target_id'];
        // drupal_set_message("blocked volunteer key and blocked_id are: $key and $blocked_id.", 'info');
        if ($blocked_id == $vol) {
          $matched = TRUE;
          $b = \Drupal\user\Entity\User::load($blocked_id);
          $name = $b->get('name')->value;
          drupal_set_message("Volunteer $name is BLOCKED from the target performance.", 'info');
          break;
        }
      }
      
      if ($matched) { continue; }   // volunteer is blocked, skip 'em
      
      // Load the $user data and do more checking
      $user = \Drupal\user\Entity\User::load($vol);
      $name = $user->getUsername();
      if ($adjust = $user->get('field_availability_adjustment')->getValue()) {
        $score = (int) $adjust[0]['value'];
      } else {
        $score = 0;    // the volunteer's initial score
      }
    
      // If the user is a partner...add PARTNER_ADJ to their score so they are not likely to be scheduled.
      if (Common::isPartner($user)) {
        $score += CalcAvailability::PARTNER_ADJ;
      }
      
      // If the user does not normally work this DOW...add WRONG_DOW to their score.
      if (!Common::normalPerformanceDate($user, $entity, $target)) {
        $score += CalcAvailability::WRONG_DOW;
      }
    
      // Inner loop through window of performances. If the volunteer worked another performance, calculate the
      // number of days between this performance and that as $days and add WINDOW - $days to their score.
      $team = array();
      foreach ($inWindow as $n => $perf) {
        $team = $perf->get('field_volunteer_manager')->getValue();
        foreach ($team as $t) {
          if ($t['target_id'] === $vol) {
            $score += CalcAvailability::WINDOW_DAYS - abs($n);
          }
        }
        $team = $perf->get('field_volunteer_monitor')->getValue();
        foreach ($team as $t) {
          if ($t['target_id'] === $vol) {
            $score += CalcAvailability::WINDOW_DAYS - abs($n);
          }
        }
        $team = $perf->get('field_volunteer_concessions')->getValue();
        foreach ($team as $t) {
          if ($t['target_id'] === $vol) {
            $score += CalcAvailability::WINDOW_DAYS - abs($n);
          }
        }
        $team = $perf->get('field_volunteer_ticket_seller')->getValue();
        foreach ($team as $t) {
          if ($t['target_id'] === $vol) {
            $score += CalcAvailability::WINDOW_DAYS - abs($n);
          }
        }
      }
    
      // Add this volunteer and their score to the $scores array as $vol => $score.
      // drupal_set_message("Score for $name is $score.");
      $scores[$vol] = $score;
      $names[$vol] = $name;
    }
  
    unset($entity->field_availability_scores);
  
    // Sort $scores by ASCENDING value and save each name|uid|score triplet in field_availability_scores
    if (!asort($scores)) {
      drupal_set_message('$scores could not be sorted!', 'error');
    }
    foreach ($scores as $v => $score) {
      $entity->field_availability_scores->appendItem("$names[$v]|$v|$score");
    }
  
    $entity->save( );
  }
  
  
  /**
   * Checks object access.
   *
   * @param mixed $object
   *   The object to check access for.
   */
  public static function hasAccess($object) {
    $active = \Drupal\wieting\Plugin\Action\Common::getActiveUID( );
    $current = \Drupal::currentUser( )->id( );
    if ($active == $current) {
      return TRUE;
    } else {
      $account = \Drupal\user\Entity\User::load($current);
      return $account->hasPermission('manage performances');
    }
    return FALSE;
  }
  
  
}

?>
