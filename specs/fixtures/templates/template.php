<html>
<head>
  <title><?= $_->e($title) ?></title>
</head>
<body>
  <img src="logo.png">

  <div id="page">
    <?= $_->section('page') ?>
  </div>

  <div id="sidebar">
    <?php if ($_->exists('sidebar')) : ?>
      <?= $_->section('sidebar') ?>
    <?php else : ?>
      <?= $_->load('sidebar') ?>
    <?php endif ?>
  </div>
</body>
</html>
