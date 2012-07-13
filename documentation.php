<?php

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
    <?php highlight_string('
    <IfModule mod_rewrite.c>
        RewriteEngine On
        SetEnv HTTP_MOD_REWRITE On
        RewriteRule ^index\.php$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule (.*) index.php [L]
    </IfModule>
')?>
                </code>

                <p>&nbsp;</p>
                <h5>index.php</h5>
                <code class="code">
<?php $index = <<< EOL
<?php

// ------------------------------------------------------------- //
// All these settings are optional, you can set them if you want //
// ------------------------------------------------------------- //

define('TI_APP_SECRET',        'mun.ee');

define('TI_DEBUG_MODE',        TRUE);

//define('TI_PATH_WEB',          '/');

//define('TI_LOCALE',            'en_US');

define('TI_HOME',              'index');

//define('TI_TIMEZONE',          'GMT');

define('TI_AUTORENDER',    TRUE);

//define('TI_RULES_CACHE',        0); // FALSE OR Number of seconds for cache

define('TI_DB',                'mysql:dbname=<database>;host=<host>;username=<username>;password=<password>;prefix=<prefix_>;charset=<charset>');
//define('TI_DB_CONN2',          'mysql://root@localhost/ti2');
//define('TI_DB_CONN3',          'interbase://SYS_USER:password@127.0.0.1:c:databases/mydatabase.fbm');

define('TI_DOCUMENTATION',      'README.html'); // call with 'http://example.com/README.html'


// ------------------------------------------------------------- //
// This is all required line that you have to have in this file  //
// Includation instruction to the TI's framework.php             //
// ------------------------------------------------------------- //
include 'ti/framework.php';
EOL;
highlight_string($index)?>
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