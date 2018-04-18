<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
</head>
<body>
  <p>Single page with uppercased <?= $this->upper('title') ?>.</p>
  <div><?= $this->e('<p>This line is safed manually</p>') ?></div>
</body>
</html>
