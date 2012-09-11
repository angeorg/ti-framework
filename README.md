<h2>About ti-framework</h2>
Configuration-less, one-file lightweight PHP MVC framework.


<h2>Usage</h2>
ti-framework needs some config instruction be in your index file, and .htaccess file in case
you need from MOD_REWRITE.
The example here demonstrate how to build a simple Hello World app with one controller, which response on app address `/helloworld/say/DoeJohn`


<h3>Example directory structure</h3>
<ul>
  <li>
    ti-example-app
    <ul>
      <li>
        app
        <ul>
          <li>
            www
            <ul>
              <li> index.php </li>
              <li> <a href="#appwwwhelloworldphp">helloworld.php</a> </li>
            </ul>
          </li>
          <li>
            html
            <ul>
              <li> index.html </li>
              <li> <a href="#apphtmlhelloworldphp">helloworld.html</a> </li>
              <li> header.html </li>
              <li> footer.html </li>
            </ul>
          <li>
            includes
            <ul>
              <li> somehelper.php </li>
              <li> class-someclass.php </li>
            </ul>
          </li>
          <li> <a href="#app__applicationphp">__application.php</a> </li>
        </ul>
      </li>
      <li>
        ti
        <ul>
          <li> <a href="#htaccess">.htaccess</a> </li>
          <li> README.md </li>
          <li> ti-framework.php </li>
          <li> ti-framework-documentation.php </li>
        </ul>
      </li>
      <li>
        <a href="#indexphp">index.php</a>
      </li>
    </ul>
  </li>
</ul>


<h3>.htaccess</h3>
```
<IfModule mod_rewrite.c>
  RewriteEngine On
  SetEnv HTTP_MOD_REWRITE On
  RewriteRule ^index\.php$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . index.php [L]
</IfModule>
```


<h3>index.php</h3>
```php
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

// Enable/Disable debugging, boolean or PHP error specific type ( E_ALL, E_NOTICE, ... )
//define( 'TI_DEBUG_MODE',           FALSE );

// Disable mod_rewrite support.
//define( 'TI_DISABLE_MOD_REWRITE',  FALSE );

// Disable sessions in the application
//define( 'TI_DISABLE_SESSION',      FALSE );

// i18n settings.
//define( 'TI_LOCALE',               'en_US' );
//define( 'TI_FOLDER_LOCALE',        'locale' );
//define( 'TI_TIMEZONE',             'GMT' );

// Set MVC folders
//define( 'TI_FOLDER_INCLUDES',      'includes' );
//define( 'TI_FOLDER_VIEW',          'html' );
//define( 'TI_FOLDER_CONTROLLER',    'www' );

// Set MVC file extensions
//define( 'TI_EXT_INCLUDES',         '.php' );
//define( 'TI_EXT_VIEW',             '.html' );
//define( 'TI_EXT_CONTROLLER',       '.php' );

// Autoload this file (<TI_PATH_APP>/TI_AUTOLOAD_FILE) on bootstrap
//define( 'TI_AUTOLOAD_FILE',         '__application.php' );

// Default directory where ti-framework stores the cache
//define( 'TI_FOLDER_CACHE',         'cache' );

// Setup databases

// accessed via db()->...
//define( 'TI_DB',                    'mysql:dbname=test;host=localhost,username=user1;password=passWord;prefix=ti_;charset=UTF8');

// accessed via db('priv')->...
//define( 'TI_DB_priv',               'sqlite:somedbfile.sqlite');

// ti-framework documentation url (http://example.com/<TI_DOCUMENTATION>)
//define( 'TI_DOCUMENTATION',         'README.html');

// ------------------------------------------------------------- //
// This is all required line that you have to have in this file  //
// Includation instruction to the TI's framework.php             //
// ------------------------------------------------------------- //
include '<PATH_TO_FRAMEWORK_DIR>/ti-framework.php';

```

<h3>app/www/helloworld.php</h3>
```php
<?php

class HelloWorld extends TI_Controller {

  function Index() {
    $this->render( 'index' );
  }
  
  function Say($name = 'John Doe') {
    $this->name = $name;
    $this->SomeClass = new SomeClass;
    $this->render( 'helloworld' );
  }

}

```

<h3>app/html/helloworld.php</h3>
```php
<?php $this->render('header')?>

<p> Hello, my name is <?php echo $this->name?> </p>

<?php $this->render('footer')?>
```

<h3>app/__application.php</h3>
```php
<?php

load_include('somehelper');

add_hook( 'url_rewrite', function($rules) {
  $rules['say-(.+)'] = 'helloworld/say/$1';
  return $rules;
});

```

<h2>Requirements</h2>
<ul>
  <li>PHP >= 5.2.0</li>
  <li>PDO (in case of using databases)</li>
</ul>


<h2>Copyright</h2>
Some functions are copy from WordPress and Drupal,
so all credentials goes to their teams.


<h2>Reporting Issues</h2>
I would love to hear your feedback. Report issues using the [Github
Issue Tracker](https://github.com/dimitrov-adrian/ti-framework/issues) or email dimitrov.adrian[at]gmail.com