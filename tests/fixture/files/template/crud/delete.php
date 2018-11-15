<?php $this->parent('crud/layout') ?>

<?php $this->start('content') ?>
<h1><?= $crud->title ?></h1>

Delete user: <?= $crud->mapper->username ?>?
<?php $this->stop() ?>