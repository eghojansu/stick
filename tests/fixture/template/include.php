<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Main layout</title>
</head>
<body>
    <p>This view include another view named "includeme" with the following content:</p>

    <div id="partialcontent"><?php $this->include('includeme', null, 3) ?></div>

    <p>This is calling to strtoupper function with upper alias: <?= $this->upper('foo') ?></p>
    <p>This is calling to strtolower function with lower alias: <?= $this->lower('FOO') ?></p>
    <p>This is calling to lcfirst function: <?= $this->lcfirst('FOO') ?></p>
    <p>This is calling to trim function with strip nothing: "<?= $this->trim('  Foo  ') ?>"</p>
    <p>This is calling to trim function with trim alias: "<?= $this->trim('  Foo  ', 3) ?>"</p>
    <p>This is calling to rtrim function with trim alias: "<?= $this->trim('  Foo  ', 2) ?>"</p>
    <p>This is calling to ltrim function with trim alias: "<?= $this->trim('  Foo  ', 1) ?>"</p>
</body>
</html>
