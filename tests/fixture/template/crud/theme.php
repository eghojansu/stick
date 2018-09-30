<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $crud->subtitle.' - '.$crud->title ?></title>
</head>
<body>
  <h1><?= $crud->title ?></h1>
  <h2><?= $crud->subtitle ?></h2>
  <?php $this->block('content') ?>
  <?php $this->endBlock() ?>
</body>
</html>