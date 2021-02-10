<?php $_->extend('stacked.blog', ['title' => $article['title']]) ?>

<h2><?= $_->e($article['title']) ?></h2>
<article>
  <?= $_->e($article['content']) ?>
</article>
