<?php $this->extend('crud/layout') ?>

<?php $this->start('content') ?>
<h1><?= $crud->title ?></h1>

<?= $crud->form->render() ?>
<?php $this->stop() ?>