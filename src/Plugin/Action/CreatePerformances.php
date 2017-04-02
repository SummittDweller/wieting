<?php
/**
 * @file
 * Contains \Drupal\wieting\Plugin\Action\CreatePerformances.
 *
 * Lifted from https://www.drupal.org/node/2330631
 */

namespace Drupal\wieting\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\wieting\Plugin\Action\Common;

/**
 *
 * Action to create Performance(s) corresponding to a selected Show.
 *
 * @Action(
 *   id = "create_show_performances",
 *   label = @Translation("Create a weekend of performances for a specified show."),
 *   type = "node"
 * )
 */
class CreatePerformances extends ActionBase {

  // const HELP_NEEDED = "249";    // uid of HELP NEEDED

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\CreatePerformances\execute.');
    // ksm($entity);

    // Get the show's title, convert the $entity to an array, and find the number of performances for the show
    $showTitle = $entity->get('title')->value;
    $fields = $entity->toArray();
    $numPerformances = count($fields['field_performance_times']);

    // Message the user about the number of performances.  If less than one we are done.
    $msg = "'$showTitle' has $numPerformances performances.";
    drupal_set_message($msg);
    if ($numPerformances < 1) { return; }

    // Loop on the field_performance_times for the show.  Create one performance node for each.
    $counter = 1;
    foreach ($fields['field_performance_times'] as $showTime) {
      // ksm($showTime);
      $date_time = $showTime['value'];               // UTC performance time string
      $local = strtotime($date_time.' UTC');    // returns a "local" timestamp!
      $performanceTitle = date('l, F j, Y - g A', $local);
      $msg = "Generating ONE performance of '$showTitle' for $performanceTitle.";
      drupal_set_message($msg);

      // Count down the number of performances
      $numPerformances--;
      $final = ($numPerformances === 0 ? TRUE : FALSE);

      // For the first performance...set the show's field_opens value.
      if ($counter === 1) {
        $entity->set('field_opens', $showTime['value']);
      }

      // For the final performance...calculate and set the show's field_closes value.
      if ($final) {
        $running = intval($fields['field_running_time']['value']) * 60;
        $close = strtotime($date_time) + $running;
        $closes = date("Y-m-d\TH:i:s", $close);
        $entity->set('field_closes', $closes);
      }

      $counter++;
      Common::createOnePerformance($entity, $performanceTitle, $date_time, $final);
    }

    $entity->save();
  }


  /** createOnePerformance
   *
   * @param $entity
   * @param $title
   * @param string $utc - Performance start time in UTC (GMT time)
   * @param $final
   *
  private function createOnePerformance($entity, $title, $utc, $final) {

    const HELP_NEEDED = '249';

    // Check if the performance already exists, load if not or create if necessary.
    $values = \Drupal::entityQuery('node')
        ->condition('type', 'performance')
        ->condition('title', $title)
        ->execute();
    if ($node_exists = !empty($values)) {
      $nid = $values[key($values)];
      $node = \Drupal\node\Entity\Node::load($nid);         // ...performance exists, load it for update.
      ksm("createPerformance existing node...", $node);
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
    $node->set('field_volunteer_manager', array('target_id' => "249"));
    $node->set('field_volunteer_monitor', array('target_id' => HELP_NEEDED));
    $node->set('field_volunteer_concessions', array('target_id' => HELP_NEEDED));
    $node->set('field_volunteer_ticket_seller', array('target_id' => HELP_NEEDED));
    $node->save();
  }

   */

  /**
   * Checks object access.
   *
   * @param mixed $object
   *   The object to execute the action on.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The user for which to check access, or NULL to check access
   *   for the current user. Defaults to NULL.
   * @param bool $return_as_object
   *   (optional) Defaults to FALSE.
   *
   * @return bool|\Drupal\Core\Access\AccessResultInterface
   *   The access result. Returns a boolean if $return_as_object is FALSE (this
   *   is the default) and otherwise an AccessResultInterface object.
   *   When a boolean is returned, the result of AccessInterface::isAllowed() is
   *   returned, i.e. TRUE means access is explicitly allowed, FALSE means
   *   access is either explicitly forbidden or "no opinion".
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    drupal_set_message('This is \Drupal\wieting\Plugin\Action\CreatePerformances\access.');
    return TRUE;
  }

}

?>
