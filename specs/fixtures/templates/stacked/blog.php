<?php $_->extend('stacked.template', ['title' => $title]) ?>

<h1>The Blog</h1>

<section>
  <article>
    <?= $_->section('content') ?>
  </article>
  <aside>
    <?= $_->loadIfExists('stacked.sidebar') ?>
  </aside>
</section>
