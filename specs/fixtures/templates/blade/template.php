<html>
  <head>
    <title>Blade Style</title>
  </head>
  <body>
    <?php $_->start('body') ?>
      Default body content.
    <?php $_->endFlush() ?>
    <?php $_->start('body2') ?>
      Default body content 2.
    <?php $_->end() ?>
  </body>
</html>
