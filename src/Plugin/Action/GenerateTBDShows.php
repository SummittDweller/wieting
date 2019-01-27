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
use \DateTime;

/**
 * Generate 3 months of TBD shows after the selected show.
 *
 * @Action(
 *   id = "generate_tbd_shows",
 *   label = @Translation("Generate 3 months of TBD shows after the specified show."),
 *   type = "node"
 * )
 */
class GenerateTBDShows extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // drupal_set_message('This is \Drupal\wieting\Plugin\Action\GenerateTBDShows\execute.');
    // ksm("entity...", $entity);

    // Check that "TO BE DETERMINED" is the selected show!
    $showTitle = $entity->get('title')->value;
    if ($showTitle != 'TO BE DETERMINED') {
      drupal_set_message("The selected show must be 'TO BE DETERMINED' for this function to work!", "error");
      return;
    }

    // Find the opening date of the selected show.
    $fields = $entity->toArray( );
    $openS = $fields['field_opens'][0]['value'];
    $openDT = Common::convertFieldToDateTime($openS);
    $target = $openDT;

    // Loop forward through 99 possible TBD dates...
    for ($i=0; $i<98; $i++) {

      // ksm("GenerateTBDShows::execute:target", $target);
      $title = $target->format('l, F j, Y - g A');
      $msg = FALSE;

      $thursday = ($target->format('N') == 4);
      $weekend = ($target->format('N') > 4);
      $summer = ((($target->format('n')) > 5 ) && (($target->format('n')) < 9 ));

      if ($thursday && $summer) {
        $msg = "special TNT";
      } else if ($weekend) {
        $msg = "regular weekend";
      }

      if ($msg) {
        drupal_set_message("Generating a $msg TBD performance for $title");
        Common::createOnePerformance($entity, $title, false);
      }

      $target->modify("+1 day");   // next day
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
