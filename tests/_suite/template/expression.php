<?php $foo='bar';$bar='1';$baz=array('foo' => 'bar') ?>
<?php echo $this->env->render('partial.shtml',[]+$__context) ?>
<?php if (isset($partial)) echo $this->env->render($partial,[]+$__context) ?>

--writing start--
Foo value is: <?= $this->raw($foo) ?>.
--writing end--
--exclude start--

--exclude end--
--ignore start--

  <set foo="1" bar="baz" baz="{{ array('foo' => 'bar') }}" />

  <ignore escape="true">
    Ignore inside ignore? It is ok!
  </ignore>

--ignore end--
--escape ignore start--

  &lt;ignore-me&gt;Yes&lt;/ignore-me&gt;

--escape ignore end--
<?php for ($i = 1;$i < 5;$i++): ?>
  Nth-<?= $i ?>

<?php endfor ?><?php if ($i < 4): ?>
    Not complete?
  <?php endif ?>
<?php foreach (array('foo', 'bar')?:[] as $key=>$value): ?>
  <?= $key ?>-<?= $value ?>

<?php endforeach ?><?php if (!isset($value)): ?>
    Nothing to loop
  <?php endif ?>
<?php if ($bar == 1): ?>
    Bar is one.
  <?php endif ?>
<?php if ($bar == 1): ?>
    Bar is one.
  <?php else: ?>
    Bar is not one.
  <?php endif ?>
<?php if ($bar == 1): ?>
    Bar is one.
  <?php elseif ($bar == 2): ?>
    Bar is two.
  <?php elseif ($bar == 3): ?>
    Bar is three.
  <?php else: ?>
    Bar is unknown.
  <?php endif ?>
<?php switch ($bar): ?><?php case '1': ?>
    Bar is one.
  <?php if (true) break ?><?php case '2': ?>
    Bar is two.
  <?php if (true) break ?><?php default: ?>
    Bar is unknown.
  <?php break ?><?php endswitch ?>