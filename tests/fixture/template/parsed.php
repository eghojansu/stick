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
  <?php if ($a > $b): ?>
    a is bigger than b
  <?php elseif ($a == $b): ?>
    a is equal to b
  <?php else: ?>
    a is smaller than b
  <?php endif ?>

  <?php if (intval($foo) > 0): ?>
    foo more than zero
  <?php endif ?>

  <!-- if with method call -->
  <?php if ($date->format('Y') and is_int(1) and $checkvar): ?>
    Foo is good
  <?php endif ?>

  <!-- for -->
  <?php foreach (range(1,10) as $i): ?>
    <?php echo $i ?>
  <?php endforeach ?>

  <?php $no = ['index'=>0,'index0'=>-1,'odd'=>null,'even'=>null]; foreach ($names ?: [] as $name): $no['index']++;$no['index0']++;$no['odd'] = $no['index'] % 2 === 0;$no['even'] = $no['index'] % 2 !== 0 ?>
    <?php echo $no['index'] . '. ' . $name ?>
  <?php endforeach ?>

  <?php $no = ['index'=>0,'index0'=>-1,'odd'=>null,'even'=>null]; foreach ($records ?: [] as $record): $no['index']++;$no['index0']++;$no['odd'] = $no['index'] % 2 === 0;$no['even'] = $no['index'] % 2 !== 0 ?>
    <?php foreach ($record ?: [] as $field => $value): ?>
      <?php echo $no['index'] . '. ' . $field . ' ' . $value ?>
    <?php endforeach ?>
  <?php endforeach ?>

  <!-- echo -->
  <?php echo 'Value of PASSWORD_BCRYPT constant is ' . PASSWORD_BCRYPT ?>
  <?php echo $foo ?>
  <?php echo $student['name'] ?>
  <?php echo $student['grades']['first'] ?>
  <?php echo $user->profile->name ?>
  <?php echo $user->profile->getName() ?>
  <?php echo $user->hasRole('foo') ?>
  <?php echo $user->age > 18 ? 'Adult' : 'Teenager' ?>
  <?php echo trim($foo) ?>
  <?php echo 'Bcrypt hash of "password" is ' . password_hash('password', PASSWORD_BCRYPT) ?>
  <?php echo "Bcrypt hash of 'gassword' is " . password_hash("gassword", PASSWORD_BCRYPT) ?>
  <?php echo "Bcrypt hash of 'gass\"word' is " . password_hash("gassword", PASSWORD_BCRYPT) ?>

  <!-- encode -->
  <?php echo htmlspecialchars($foo) ?>
  <?php echo htmlspecialchars(trim($foo)) ?>

  <!-- filter -->
  <?php echo trim($foo) ?>
  <?php echo trim($bar['baz']) ?>
  <?php echo trim($baz['qux']['quux']) ?>
  <?php echo trim($user->age) ?>
  <?php echo trim($user->profile->name) ?>

  <?php echo trim(trim($foo)) ?>
  <?php echo trim(trim(trim($foo))) ?>

  <?php echo trim($foo, '</p>') ?>
  <?php echo trim($user->profile->name, 'o') ?>
  <?php echo trim($student['name'], $foo) ?>

  <!-- set -->
  <?php $foo = 'bar'; $bar = 'baz' ?>
  <?php $qux = ['quux'] ?>
</body>
</html>
