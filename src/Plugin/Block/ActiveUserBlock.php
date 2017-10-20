<?php

/* See https://www.drupal.org/docs/8/creating-custom-modules/create-a-custom-block */

namespace Drupal\wieting\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an 'ACTIVE User' block.
 *
 * @Block(
 *   id = "active_user_block",
 *   admin_label = @Translation("ACTIVE User Block"),
 * )
 */
class ActiveUserBlock extends BlockBase {
  
  /**
   * {@inheritdoc}
   */
  public function build( ) {
    $uid = \Drupal\wieting\Plugin\Action\Common::getActiveUID( );
    $user = \Drupal\user\Entity\User::load($uid);
    $name = $user->getAccountName( );
    $value = $user->get('field_manager_team')->getValue( );
    
    $team = $value[0]['value'];
    $first = $team[0];
    $special = array('!', '*', '~');
    
    if (in_array($first, $special) || ($team === $name)) { $team = NULL; }

    return array(
      'uid' => $uid,
      'name' => $name,
      'team' => $team,
      '#cache' => [ 'contexts' => ['url.path',]],
    );
  }
  
  
}
