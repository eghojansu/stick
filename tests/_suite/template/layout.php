<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php $this->start('title') ?>Layout Title<?php $this->stop() ?></title>
</head>
<body>
  <?php $this->start('override') ?>
    Can be override.
  <?php $this->stop() ?>

  <?php $this->start('keep') ?>
    Layout content.
  <?php $this->stop() ?>
</body>
</html>