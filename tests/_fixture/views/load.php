<?php $this->extend('layout') ?>

<?php $this->start('content') ?>
<?= $this->load('partial', array('context' => 'full')) ?> and parent context: <?= $parent_context ?>.
And also parent block (title): <?= $this->parent('title') ?>.
<?php $this->stop() ?>
