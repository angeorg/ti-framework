# About ti-framework #

Non-objective PHP MVC framework


# Usage #
ti-framework needs some config instruction be in your index file


## Example directory structure of app using ti-framework ##

<ul>
  <li>
    /app-root
    <ul>
      <li>
        app
        <ul>
          <li> www </li>
          <li> html </li>
          <li> includes </li>
        </ul>
      </li>
      <li>
        ti
        <ul>
          <li> ti-framework </li>
          <li> ti-framework-documentation.php </li>
          <li> README.md </li>
          <li> .htaccess </li>
        </ul>
      </li>
      <li>
        index.php
      </li>
    </ul>
  </li>
</ul>

## index.php ##
<code>
<?php

// ------------------------------------------------------------- //
// All these settings are optional, you can set them if you want //
// ------------------------------------------------------------- //

// Set appsecret salt.
//define( 'TI_APP_SECRET',           'ti-framework' );

// Path to application directory.
//define( 'TI_PATH_APP',             dirname(__FILE__) . '/application' );

// Set default home url.
//define( 'TI_HOME',                 'index' );

// Enable/Disable debugging, or -1 to enable only logging errors
//define( 'TI_DEBUG_MODE',           FALSE );

// Web url to application (must ending with slash)
// can be domain relative like /my-app/ or full http://example.com/ or just /
//define( 'TI_PATH_WEB',             '/' );

// Disable mod_rewrite support.
//define( 'TI_DISABLE_MOD_REWRITE',  FALSE );

// Disable sessions in the application
//define( 'TI_DISABLE_SESSION',      FALSE );

// i18n settings.
//define( 'TI_LOCALE',               'en_US' );
//define( 'TI_FOLDER_LOCALE',        'locale' );
//define( 'TI_TIMEZONE',             'GMT' );

// Set MVC folders
//define( 'TI_FOLDER_INC',           'includes' );
//define( 'TI_FOLDER_VIEW',          'html' );
//define( 'TI_FOLDER_CONTROLLER',    'www' );

// Set MVC file extensions
//define( 'TI_EXT_INC',              '.php' );
//define( 'TI_EXT_VIEW',             '.html' );
//define( 'TI_EXT_CONTROLLER',       '.php' );

// Autorender, when this option is enabled, then when controller is loaded,
// it will automatic call \$this->render(<controller>)
//define( 'TI_AUTORENDER',           FALSE );

// Autoload this file (<TI_PATH_APP>/TI_AUTOLOAD_FILE) on bootstrap
//define( 'TI_AUTOLOAD_FILE', '__application.php' );

// Default directory where ti-framework stores the cache
//define( 'TI_FOLDER_CACHE',         'cache' );
//define( 'TI_DOCUMENTATION', 'README.html');

// Setup databases
//define( 'TI_DB',               'mysql:dbname=test;host=localhost,username=user1;password=passWord;prefix=ti_;charset=UTF8');
//define( 'TI_DB_priv',          'sqlite:somedbfile.sqlite');

// ti-framework documentation url (http://example.com/<TI_DOCUMENTATION>)
//define( 'TI_DOCUMENTATION',      'README.html');

// ------------------------------------------------------------- //
// This is all required line that you have to have in this file  //
// Includation instruction to the TI's framework.php             //
// ------------------------------------------------------------- //
include 'ti/ti-framework.php';
</code>


# Copyright #
Some functions are copy from WordPress and Drupal,
so all credentials goes to their teams.