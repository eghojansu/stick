<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php $this->block('title') ?>Template Layout<?php $this->endBlock() ?></title>

    <?php $this->block('css') ?>
      <link rel="stylesheet" href="style.css">
    <?php $this->endBlock() ?>
</head>
<body>
  <?php $this->block('body') ?>
    Body
  <?php $this->endBlock() ?>

  Var value is <?=$var?>

  Fragment content loaded from layout:
  <?=$this->include('fragment')?>

  <?php $this->block('js') ?>
    <script src="script.js"></script>
  <?php $this->endBlock() ?>
</body>
</html>
