<?php $this->extend('theme.php') ?>

<?php $this->block('title', 2) ?>
Valid Template Title
<?php $this->endBlock() ?>

<?php $this->block('body') ?>
<?= $this->parent() ?> - Valid Template content - <?= $this->section('title') ?> - <?= $this->parent() ?>
<?php $this->endBlock() ?>