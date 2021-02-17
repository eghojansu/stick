<?php $_->extend('blade/template') ?>

<?php $_->start('body') ?>
  <?=$_->parent()?>
  This is body from profile.
  <?=$_->insert('body2')?>
<?php $_->end() ?>
