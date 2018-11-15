<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Layout 2</title>
</head>
<body>
  <?php $this->start('content') ?>
  Content.

  <?php $this->start('sub') ?>
    Sub.
  <?php $this->stop() ?>
  <?php $this->stop() ?>
</body>
</html>