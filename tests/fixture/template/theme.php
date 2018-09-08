<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php $this->block('title') ?>Theme<?php $this->endBlock() ?></title>
</head>
<body>
  <div class="header">
    <h1><?= $this->section('title') ?></h1>
  </div>
  <div class="body">
    <?php $this->block('body') ?>
      Theme Body
    <?php $this->endBlock() ?>
  </div>
  <div class="footer">
    <?php $this->block('footer', 1) ?>
      My Theme
    <?php $this->endBlock() ?>
  </div>
</body>
</html>