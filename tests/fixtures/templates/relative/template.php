From template.
<?= $_->load('./my_relative') ?>
<?= $_->load('./my_relative2.php') ?>
<?php if (isset($loadUnrelative)): ?><?= $_->load('./unknown_relative') ?><?php endif ?>
