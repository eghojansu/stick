<?php $this->extend('theme.php') ?>

<?php $this->block('content') ?>
<table>
  <tbody>
    <?php foreach ($crud['fields'] as $field => $def): ?>
      <tr>
        <td><?= $def['label'] ?></td>
        <td><?= $crud['item'][$field] ?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
<form method="post">
  <button type="submit">Delete</button>
</form>
<?php $this->endBlock() ?>