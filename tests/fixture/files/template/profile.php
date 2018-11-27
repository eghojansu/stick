<?php $this->extend('dashboard') ?>

<?php $this->start('title', true) ?>Profile - <?= $this->parent('title') ?><?php $this->stop() ?>

<?php $this->start('content') ?>
Profile of <?= $this->esc($username) ?>.
<?php $this->stop() ?>