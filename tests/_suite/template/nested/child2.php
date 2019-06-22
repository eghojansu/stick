<?php $this->extend('nested.child1') ?>

<?php $this->start('subcontent') ?>
  Child 2 sub content
  With parent:
  <div class="subcontent-parent">
    <?= $this->parent() ?>
  </div>
<?php $this->stop() ?>