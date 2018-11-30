<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php $this->start('title', true) ?>Layout<?php $this->stop() ?></title>
</head>
<body>
  <div class="header">
  <?php $this->start('header') ?>
  Header
  <?php $this->stop() ?>
  </div>
  <div class="content">
  <?php $this->start('content') ?>
  Content
  <?php $this->stop() ?>
  </div>
  <div class="footer">
  <?php $this->start('footer') ?>
  Footer
  <?php $this->stop() ?>
  </div>
</body>
</html>