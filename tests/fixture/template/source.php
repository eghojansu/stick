<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Source</title>
</head>
<body>
  <!-- comment -->
  c
  c

  <!-- if -->
  <?php if ($context['a'] > $context['b']): ?>
    a is bigger than b
  <?php elseif ($context['a'] == $context['b']): ?>
    a is equal to b
  <?php else: ?>
    a is smaller than b
  <?php endif ?>

  <?php if (intval($context['foo']) > 0): ?>
    foo more than zero
  <?php endif ?>

  <!-- if with method call -->
  <?php if ($context['date']->format('Y') and is_int(1) and $context['checkvar']): ?>
    Foo is good
  <?php endif ?>

  <!-- for -->
  <?php foreach (range(1,10) as $i): ?>
    <?php echo $context['i'] ?>
  <?php endforeach ?>

  <?php $context['no'] = ['index'=>0,'index0'=>-1,'odd'=>null,'even'=>null]; foreach ($context['names'] ?: [] as $name): $context['no']['index']++;$context['no']['index0']++;$context['no']['odd'] = $context['no']['index'] % 2 === 0;$context['no']['even'] = $context['no']['index'] % 2 !== 0 ?>
    <?php echo $context['no']['index'] . '. ' . $context['name'] ?>
  <?php endforeach ?>

  <?php $context['no'] = ['index'=>0,'index0'=>-1,'odd'=>null,'even'=>null]; foreach ($context['records'] ?: [] as $record): $context['no']['index']++;$context['no']['index0']++;$context['no']['odd'] = $context['no']['index'] % 2 === 0;$context['no']['even'] = $context['no']['index'] % 2 !== 0 ?>
    <?php foreach ($context['record'] ?: [] as $field => $value): ?>
      <?php echo $context['no']['index'] . '. ' . $context['field'] . ' ' . $context['value'] ?>
    <?php endforeach ?>
  <?php endforeach ?>

  <!-- echo -->
  <?php echo 'Value of PASSWORD_BCRYPT constant is ' . PASSWORD_BCRYPT ?>
  <?php echo $context['foo'] ?>
  <?php echo $context['student']['name'] ?>
  <?php echo $context['student']['grades']['first'] ?>
  <?php echo $context['user']->profile->name ?>
  <?php echo $context['user']->profile->getName() ?>
  <?php echo $context['user']->hasRole('foo') ?>
  <?php echo $context['user']->age > 18 ? 'Adult' : 'Teenager' ?>
  <?php echo trim($context['foo']) ?>
  <?php echo 'Bcrypt hash of "password" is ' . password_hash('password', PASSWORD_BCRYPT) ?>
  <?php echo "Bcrypt hash of 'gassword' is " . password_hash("gassword", PASSWORD_BCRYPT) ?>
  <?php echo "Bcrypt hash of 'gass\"word' is " . password_hash("gassword", PASSWORD_BCRYPT) ?>

  <!-- encode -->
  <?php echo htmlspecialchars($context['foo']) ?>
  <?php echo htmlspecialchars(trim($context['foo'])) ?>

  <!-- filter -->
  <?php echo trim($context['foo']) ?>
  <?php echo trim($context['bar']['baz']) ?>
  <?php echo trim($context['baz']['qux']['quux']) ?>
  <?php echo trim($context['user']->age) ?>
  <?php echo trim($context['user']->profile->name) ?>

  <?php echo trim(trim($context['foo'])) ?>
  <?php echo trim(trim(trim($context['foo']))) ?>

  <?php echo trim($context['foo'], '</p>') ?>
  <?php echo trim($context['user']->profile->name, 'o') ?>
  <?php echo trim($context['student']['name'], $context['foo']) ?>

  <!-- set -->
  <?php $context['key'] = 'name' ?>
  <?php echo $context['student'][$context['name']] ?>

  <?php $context['foo'] = 'bar'; $context['bar'] = 'baz' ?>
  <?php $context['qux'] = ['quux',$context['foo'],$context['user']->age,$context['student']['name']] ?>
  <?php $context['quux'] = ['quux',$context['foo'] , $context['user']->age] ?>
  <?php $context['bleh'] = ['one'=> 1, 'two'=> 2,'three'=>$context['foo'],'four'=>$context['user']->age,'five'=>$context['student']['name']] ?>

  <?php echo $this->render('fragment.html', $context, false) ?>
  <?php echo $this->render('fragment.html', ['fragment'=>'fragment var','foo'=>$context['foo']] + $context, false) ?>
  <?php echo $this->render('fragment.html', ['fragment'=>'fragment var','foo'=>$context['foo']], false) ?>

  <?php if (!function_exists('name')):function name($one,$two,$three) {if (!isset($context)):$context = [];endif;$context['one'] = $one;$context['two'] = $two;$context['three'] = $three; ?>
    <?php if ($context['one'] !== $context['two']): ?>
      <?php echo $context['one'] . $context['two'] . $context['three'] ?>
    <?php endif ?>
  <?php } endif ?>

  <?php if (!function_exists('name1')):function name1($one = 1,$two = 'two',$three = '3') {if (!isset($context)):$context = [];endif;$context['one'] = $one;$context['two'] = $two;$context['three'] = $three; ?>
    <?php if ($context['one'] !== $context['two']): ?>
      <?php echo $context['one'] . $context['two'] . $context['three'] ?>
    <?php endif ?>
  <?php } endif ?>

  <?php if (!function_exists('name2')):function name2($one = 1,$context = null) {if (!isset($context)):$context = $GLOBALS['context'];endif;$context['one'] = $one;$context['context'] = $context; ?>
    <?php if ($context['one'] !== $context['two']): ?>
      <?php echo $context['one'] . $context['two'] . $context['three'] ?>
    <?php endif ?>
  <?php } endif ?>

  <?php echo name1('siji') ?>

  <?php $this->render('fragment.html', $context, false) ?>
  <?php echo Fal\Stick\startswith('f', 'foo') ? 'yes' : 'no' ?>
</body>
</html>
