<?php $this->extend('theme.php') ?>

<?php $this->block('title', 2) ?>
Valid Template Title
<?php $this->endBlock() ?>

<?php $this->block('body') ?>
Valid Template content - <?= $this->section('title') ?>
<?php $this->endBlock() ?>