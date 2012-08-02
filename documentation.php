<?php

/**
 * Documentation generator.
 *
 * Copyright (c) 2010, e01 <dimitrov.adrian@gmail.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  - Neither the name of Incutio Ltd. nor the names of its contributors
 *    may be used to endorse or promote products derived from this software
 *    without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

  $functions_file = file( TI_PATH_FRAMEWORK . '/ti-framework.php' );
  $functions = get_defined_functions();
  $functions = $functions['user'];

  $hooks = array();
  foreach ($functions_file as $line) {
    if ( preg_match( '#@fire\ (\w+)#', $line, $hook)) {
      $hooks[] = $hook[1];
    }
  }

  $ti_framework_icon = 'data:image/png;base64,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%3D';

?>
<!doctype html>
<html>
    <head>
        <title> ti-framework v<?php echo TI_FW_VERSION?> </title>
        <style>
            * { margin: 0; padding: 0; -webkit-transition: all 0.3s ease-in-out; transition: all 0.3s ease-in-out; }
            body { background: #eee; color: #111; font: normal 12px sans-serif; }
            a { color: #3b748c; }
            .ti-framework-icon {
              width:55px; height:55px;
              background-image:url(<?php echo $ti_framework_icon?>);
            }
            .navigation { overflow: auto; width: 20%; height: 100%; position: fixed; }
            .menu { padding: 10px; }
            .main { overflow: auto; margin-left: 20%; height: 100%; position: fixed; left: 0; right: 0; }
            .navigation h3 { font-size: 18px; }
            .navigation li { list-style: none; }
            .navigation li a { text-decoration: none; padding: 2px 5px; display: block; }
            .navigation li a:hover, .navigation li a:active { background: #e5e5e5; color: red; }
            .helpblock { margin: 8px; padding: 8px; background: #fff; border: 1px solid #ccc; box-shadow: 0 0 12px #bbb; display: block; }
            .helpblock h3 { font-size: 18px; }
            .v-fire { color: #ce5c00; font-weight: bolder; }
            .v-thanks { color: #810087; font-weight: bolder; }
            .v-access { color: #999966; font-weight: bolder; }
            .v-see { color: #6d914c; font-weight: bolder; }
            .v-param { color: #2397c9; font-weight: bolder; }
            .v-return { color: #f00; font-weight: bolder; }
            .helpblock .code { background: #ddd; display: block; overflow: auto; font-family: monospace; font-size: 11px; }
        </style>
    </head>
    <body>
        <div class="navigation">
              <div class="menu">
                  <div class="ti-framework-icon"></div>
                  <h3>Generic</h3>
                  <ul>
                      <li>
                          <a  href="#base">Base</a>
                      </li>
                  </ul>
                  <h3>Hooks</h3>
                  <ul>
                  <?php foreach ($hooks as $hook):?>

                      <li>
                        <a href="#hook-<?php echo $hook?>">
                          <?php echo $hook?>
                        </a>
                      </li>
                  <?php endforeach?>
                  </ul>
                  <h3>Classes</h3>
                  <ul>
                      <li>
                          <a href="#class-Appliction">Application</a>
                      </li>
                  </ul>
                  <h3>Functions</h3>
                  <ul>
                  <?php foreach ( $functions as $function ):?>
                      <li>
                          <a href="#function-<?php echo $function?>"><?php echo $function?></a>
                      </li>
                  <?php endforeach ?>
                  </ul>
              </div>
        </div>
        <div class="main">

              <div id="base" class="helpblock">
                <h3>Basics</h3>

                <p>&nbsp;</p>
                <h5>.htaccess</h5>
                <code class="code">
<?php $htaccess = <<<EOL

  <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteRule ^index\.php$ - [L]
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule (.*) index.php [L]
  </IfModule>
\n
EOL;
highlight_string($htaccess); unset($htaccess);?>
                </code>

                <p>&nbsp;</p>
                <h5>index.php</h5>
                <code class="code">
<?php $index = <<< EOL
<?php

  // ------------------------------------------------------------- //
  // All these settings are optional, you can set them if you want //
  // ------------------------------------------------------------- //

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
  // define( 'TI_DISABLE_SESSIONS', FALSE );

  // i18n settings.
  //define( 'TI_LOCALE',               'en_US' );
  //define( 'TI_FOLDER_LOCALE',        'locale' );
  //define( 'TI_TIMEZONE',             'GMT' );

  // Set MVC alike folders
  //define( 'TI_FOLDER_INC',           'includes' );
  //define( 'TI_FOLDER_VIEW',          'html' );
  //define( 'TI_FOLDER_CONTROLLER',    'www' );

  // Set MVC alike file extensions
  //define( 'TI_EXT_INC',              '.php' );
  //define( 'TI_EXT_VIEW',             '.html' );
  //define( 'TI_EXT_CONTROLLER',       '.php' );

  // Autorenderer, it call render() method with parameter controller name.
  //define( 'TI_AUTORENDER',           TRUE );

  // Cache controller rules for faster routing.
  define( 'TI_RULES_CACHE',          120 );

  // Default directory where ti-framework stores the cache
  //define( 'TI_FOLDER_CACHE',         'cache' );

  // Setup databases
  define( 'TI_DB',                'mysql:dbname=test;host=localhost;username=user1;password=passWord;prefix=ti_;charset=UTF8');
  //define('TI_DB_ti2',           'mysql://root@localhost/ti2');
  //define('TI_DB_i2',            'interbase://SYS_USER:password@127.0.0.1:c:databases/mydatabase.fbm');

  // ti-framework documentation url (http://example.com/<TI_DOCUMENTATION>)
  define( 'TI_DOCUMENTATION',      'README.html');

  // ------------------------------------------------------------- //
  // This is all required line that you have to have in this file  //
  // Includation instruction to the TI's framework.php             //
  // ------------------------------------------------------------- //
  include 'ti/framework.php';
\n
EOL;
highlight_string($index);unset($index);?>
                </code>
              </div>
              <?php foreach ( $functions as $function ):?>
              <div id="function-<?php echo $function?>" class="helpblock">
              <?php
                    $fc = new ReflectionFunction($function);
                    echo '<h3>' . rtrim(trim($functions_file[$fc->getStartLine()-1]), '{') . '</h3>';
                    $docu = $fc->getDocComment();
                    echo make_clickable(strtr(highlight_string(preg_replace('#\n\s*(/\*\*|\*\/|\*)#', "\n", "\n" . $docu), 1),
                            array(
                                '@thanks' => '<span class="v-thanks">@thanks</span>',
                                '@fire' => '<span class="v-fire">@fire</span>',
                                '@param' => '<span class="v-param">@param</span>',
                                '@access' => '<span class="v-access">@access</span>',
                                '@return' => '<span class="v-return">@return</span>',
                                '@see' => '<span class="v-see">@see</span>',
                            )));
              ?>
            </div>
            <?php endforeach?>
        </div>
    </body>
</html>
