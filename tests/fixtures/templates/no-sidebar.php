<?php $_->extend('template', ['title' => 'User Profile']) ?>

<?php $_->start('page') ?>
<h1>Welcome!</h1>
<p>Hello <?= $_->e($name) ?></p>
<?php $_->end() ?>
