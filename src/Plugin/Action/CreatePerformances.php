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
    // drupal_set_message('This is \Drupal\wieting\Plugin\Action\CreatePerformances\execute.');
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
  
  
  /**
   * Checks object access.
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $status = \Drupal\wieting\Plugin\Action\Common::hasAccess($object);
    return $status;
  }
  
}

?>
