<?php $this->extend('dashboard') ?>

<?php $this->start('title', true) ?>Profile - <?= $this->parent('title') ?><?php $this->stop() ?>

<?php $this->start('content') ?>
Profile of <?= $this->esc($username) ?>.
  Profile global variable <?= var_export($this->profile, true) ?>.
<?php $this->stop() ?>