<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Main layout</title>
</head>
<body>
    <p>This view include another view named "includeme" with the following content:</p>

    <div id="partialcontent"><?= $this->include('includeme', null, 3); ?></div>

    <p>This is calling to strtoupper function with upper alias: <?= $this->upper('foo'); ?></p>
    <p>This is calling to strtolower function with lower alias: <?= $this->lower('FOO'); ?></p>
    <p>This is calling to lcfirst function: <?= $this->lcfirst('FOO'); ?></p>
</body>
</html>
