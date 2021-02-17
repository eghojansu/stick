<?php $_->extend('template', ['title' => 'User Profile']) ?>

<?php $_->start('page') ?>
<h1>Welcome!</h1>
<p>Hello <?= $_->e($name) ?></p>
<?php $_->end() ?>

<?php $_->start('sidebar') ?>
<ul>
  <li><a href="/link">Example Link</a></li>
  <li><a href="/link">Example Link</a></li>
  <li><a href="/link">Example Link</a></li>
  <li><a href="/link">Example Link</a></li>
  <li><a href="/link">Example Link</a></li>
</ul>
<?php $_->end() ?>
