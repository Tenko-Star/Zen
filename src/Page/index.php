<?php $p = Zen_Render::bind('index') ?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Zen framework demo</title>
        <meta charset="utf-8">
        <meta name="author" content="tenko_">
        <meta name="keyword" content="Zen ZenFramework Tenko tenko_">
        <style type="text/css">
            .zen-key {
                color: red;
            }
            .zen-value {
                color: blue;
            }
            .zen-colon {
                color: gray;
            }
        </style>
    </head>
    <body>
        <h1><?php $p("{header}") ?></h1>
        <div>
            <p>Server Database Information</p>
            <pre><?php $p("{db_info}") ?></pre>
            <hr>
            <p>Server Widget Information</p>
            <pre><?php $p("{widget_info}") ?></pre>
            <hr>
            <p>Server Plugin Information</p>
            <pre><?php $p("{plugin_info}") ?></pre>
        </div>
        <hr>
        <div>
            <p>Plugin Page</p>
            <p><?php $p("{hello}") ?></p>
        </div>
        <hr>
        <div>
            <p>Database Test</p>
            <p><?php $p("{database}") ?></p>
        </div>
    </body>
</html>
