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


  include 'functions.php';
  $functions_file = file( TI_PATH_FRAMEWORK . '/functions.php');
  $functions = get_defined_functions();
  $functions = $functions['user'];
  sort($functions);



?>
<!doctype html>
<html>
    <head>
        <title> ti-framework v<?php echo TI_FW_VERSION?> </title>
        <style>
            * { margin: 0; padding: 0; }
            body { background: #eee; color: #111; font: normal 12px sans-serif; }
            a { color: #3b748c; }
            .navigation { float: left; width: 200px; margin: 8px; padding: 8px; background: #fff; border: 1px solid #ccc; box-shadow: 0 0 12px #bbb; }
            .main { margin: 8px 8px 8px 240px; }
            .navigation h3 { font-size: 18px; }
            .navigation li { list-style: none; }
            .navigation li a { text-decoration: none; padding: 2px 5px; display: block; }
            .navigation li a:hover, .navigation li a:active { background: #e5e5e5; color: red; }
            .helpblock { margin: 8px; padding: 8px; background: #fff; border: 1px solid #ccc; box-shadow: 0 0 12px #bbb; display: block; }
            .helpblock h3 { font-size: 18px; }
            .v-fire { color: #ce5c00; font-weight: bolder; }
            .v-see { color: #6d914c; font-weight: bolder; }
            .v-param { color: #2397c9; font-weight: bolder; }
            .v-return { color: #333; font-weight: bolder; }
            .helpblock .code { background: #ddd; display: block; overflow: auto; font-family: monospace; font-size: 11px; }
        </style>
        <script src="http://code.jquery.com/jquery-1.7.2.min.js"></script>
        <script>
            $(function() {
                $('.navigation a').click(function() {
                    b = $(this).attr('href');
                    //$('.helpblock').css('display', 'none');
                    //$(b).show();

                })
                //$($('.helpblock')[0]).show();
            })
        </script>
    </head>
    <body>
        <div class="navigation">
            <h3>Generic</h3>
            <ul>
                <li>
                    <a  href="#base">Base</a>
                </li>
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
        <div class="main">

              <div id="base" class="helpblock">
                <h3>Basics</h3>

                <p>&nbsp;</p>
                <h5>.htaccess</h5>
                <code class="code">
<?php $htaccess = <<<EOL
  <IfModule mod_rewrite.c>
      RewriteEngine On
      SetEnv HTTP_MOD_REWRITE On
      RewriteRule ^index\.php$ - [L]
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule (.*) index.php [L]
  </IfModule>
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

  // Set appsecret salt.
  define( 'TI_APP_SECRET',           'ti-framework' );

  // Set default home url.
  //define( 'TI_HOME',                 'index' );

  // Set the debugging mode to false.
  //define( 'TI_DEBUG_MODE',           FALSE );

  // Detect the webpath.
  //define( 'TI_PATH_WEB',             '/' );

  // i18n settings.
  //define( 'TI_LOCALE',               'en_US' );
  //define( 'TI_FOLDER_LOCALE',        'locale' );
  //define( 'TI_TIMEZONE',             'GMT' );

  // Set MVC folders
  //define( 'TI_FOLDER_MODEL',         'class' );
  //define( 'TI_FOLDER_VIEW',          'html' );
  //define( 'TI_FOLDER_CONTROLLER',    'www' );

  // Set MVC file extensions
  //define( 'TI_EXT_MODEL',            '.php' );
  //define( 'TI_EXT_VIEW',             '.html' );
  //define( 'TI_EXT_CONTROLLER',       '.php' );

  // Autorenderer, it call render() method with parameter controller name.
  //define( 'TI_AUTORENDER',           TRUE );

  // Cache controller rules for faster routing.
  //define( 'TI_RULES_CACHE',          FALSE );

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
                    echo strtr(highlight_string(preg_replace('#\n\s*(/\*\*|\*\/|\*)#', "\n", "\n" . $docu), 1),
                            array(
                                '@fire' => '<span class="v-fire">@fire</span>',
                                '@param' => '<span class="v-param">@param</span>',
                                '@return' => '<span class="v-return">@return</span>',
                                '@see' => '<span class="v-see">@see</span>',
                            ));
              ?>
            </div>
            <?php endforeach?>
        </div>
    </body>
</html>
