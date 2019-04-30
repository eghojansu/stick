<?php $this->extend('layout.shtml') ?>

<?php $this->start('title') ?>Complete Title<?php $this->stop() ?>

<?php $this->start('override') ?>
  <?= $this->parent() ?>
  <?php $foo='bar';$bar=1 + 1 ?>
  <?php if ('bar' === $foo): ?>
      Foo is bar.

      <?php if (true): ?>
        Always displayed.
      <?php endif ?>
    <?php else: ?>
      Foo is not bar.
    <?php endif ?>
  <?php if ($bar == 1): ?>
      bar is one.
    <?php elseif ($bar == 2): ?>
      bar is two.
    <?php elseif ($bar == 3): ?>
      bar is three.
    <?php else: ?>
      bar is to much.
    <?php endif ?>
  <?php switch ($foo): ?><?php case 'foo': ?>
      fook ok
    <?php break ?><?php case 1+1: ?>
      foo two
    <?php break ?><?php default: ?>
      on default
    <?php break ?><?php endswitch ?>
  <?php for ($i = 0;$i < 10;$i++): ?>
    I am <?= $i ?>/10.
  <?php endfor ?>
  <?php foreach ($items?:[] as $key=>$item): ?>
    <?= $key ?> - <?= $item.'
' ?>
  <?php endforeach ?>

    I am totally ignored from being rendered.
    {{ @you.still.see.me.in.a.dot.style }}

  While this line rendered nicely: <?= $you['see']['me']['as']['array']['access'] ?>.

  This is an filter example: <?= trim(' foo ') ?>.
  This is an filter example (by calling template method): <?= $this->path('foo') ?>.
  <?php if (true) echo $this->env->render('foo.shtml',['pfoo'=>'bar','foo'=>$foo]+$__context) ?>

  <?php echo $this->env->render('foo.shtml',[]+$__context) ?>
<?php $this->stop() ?>