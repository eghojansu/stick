<?php $this->parent('dashboard') ?>

<?php $this->start('title', true) ?>Profile - <?= $this->parent->section('title') ?><?php $this->stop() ?>

<?php $this->start('content') ?>
Profile of <?= $this->esc($username) ?>.
<?php $this->stop() ?>