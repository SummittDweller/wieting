To update the installation profile for this module perform
the following:

  With devel module enabled you can navigate to /devel/php then paste this code and run execute.

  ```
  $config_installer = \Drupal::service('config.installer');
  $config_installer->installDefaultConfig('module', 'wieting');
  ```
  
*Lifted from https://www.drupal.org/node/2330631.*
