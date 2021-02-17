Foo is uppercased: <?=$_->escape($foo, 'strtolower|upper')?>.
Bar is displayed as is "<?=$bar?>".
HTML escaped: "<?=$_->esc($html)?>".
<?php if (isset($call)): ?><?= $_->$call(...($callArguments ?? array())) ?><?php endif ?>
<?php if (isset($selfRendering)): ?><?=$_->load('consumer')?><?php endif ?>
<?php if (isset($loadUnknown)): ?><?=$_->loadIfExists('unknown', null, 'Loading unknown template fallback.')?><?php endif ?>
<?php if (isset($callParent)): ?><?=$_->parent()?><?php endif ?>
<?php if (isset($startContent)): ?><?php $_->start('content')?><?php endif ?>
<?php if (isset($endContent)): ?><?php $_->end()?><?php endif ?>
<?php if (isset($divisionByZero)): ?><?=1/0?><?php endif ?>
<?php if (isset($doubleStart)): ?>
<?php $_->start('first') ?>
<?php $_->start('second') ?>
<?php $_->end() ?>
<?php endif ?>
