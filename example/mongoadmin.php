<?php

$mongo = new Mongo();

if (isset($_REQUEST['delete_database'])) {
  $mongo
    ->selectDB($_REQUEST['delete_database'])
    ->drop();
  
  header('location: ' . $_SERVER['PHP_SELF']);
  exit;
}

if (isset($_REQUEST['delete_collection'])) {
  $mongo
    ->selectDB($_REQUEST['database'])
    ->selectCollection($_REQUEST['delete_collection'])
    ->drop();
  
  header('location: ' . $_SERVER['PHP_SELF'] . '?database=' . $_REQUEST['database']);
  exit;
}

if (isset($_REQUEST['delete_document'])) {
  $mongo
    ->selectDB($_REQUEST['database'])
    ->selectCollection($_REQUEST['collection'])
    ->remove(array('_id' => new MongoId($_REQUEST['delete_document'])));

  header('location: ' . $_SERVER['PHP_SELF'] . '?database=' . $_REQUEST['database'] . '&collection=' . $_REQUEST['collection']);
  exit;
}


if (isset($_POST['save'])) {
  $collection = $mongo->selectDB($_REQUEST['database'])->selectCollection($_REQUEST['collection']);
  
  function prepareRealValue($value)
  {
    
    $prepared = array();
    foreach ($value as $k => $v) {
      if ($k === '_id') {
        $v = new MongoId($v);
      }
      if ($k === '$id') {
        $v = new MongoId($v);
      }
      if (is_array($v)) {
        $prepared[$k] = prepareRealValue($v);
      } else {
        $prepared[$k] = $v;
      }
    }
    return $prepared;
  }
  eval('$value = ' . $_REQUEST['value'] . ';');
  $value = prepareRealValue($value);

  $collection->save($value);

  $url = $_SERVER['PHP_SELF'] . '?database=' . $_REQUEST['database'] . '&collection=' . $_REQUEST['collection'] . '&id=' . (string) $value['_id'];
  header('location: ' . $url);
  exit;
}
?>
<html>
  <head>
    <title>MongoDB Admin - Powered by Doctrine</title>
    <link rel="stylesheet" type="text/css" media="screen" href="http://www.doctrine-project.org/css/main.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="http://www.doctrine-project.org/css/layout.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="http://www.doctrine-project.org/css/documentation.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="http://www.doctrine-project.org/css/documentation_markup.css" />
  </head>
  
  <body>
  <div id="wrapper">
    <div id="header">
      <h1 id="h1title">Doctrine MongoDB Admin</h1>
      <div id="logo">
        <a href="/">Doctrine - Open Source PHP 5 ORM</a>
      </div>
    </div>    
    <div id="content" class="cls">
        <?php if ( ! isset($_REQUEST['database'])): ?>
          <h2>Databases</h2>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php $dbs = $mongo->listDBs() ?>
              <?php foreach ($dbs['databases'] as $db): ?>
                <tr>
                  <td><a href="<?php echo $_SERVER['PHP_SELF'] . '?database=' . $db['name'] ?>"><?php echo $db['name'] ?></a></td>
                  <td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?delete_database=<?php echo $db['name'] ?>" onClick="return confirm('Are you sure you want to delete this database?');">Delete</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php elseif (isset($_REQUEST['database']) && ! isset($_REQUEST['collection'])): ?>
          <h2>
            <a href="<?php echo $_SERVER['PHP_SELF'] ?>">Databases</a> >>
            <?php echo $_REQUEST['database'] ?>
          </h2>
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php $collections = $mongo->selectDB($_REQUEST['database'])->listCollections() ?>
              <?php foreach ($collections as $collection): ?>
                <tr>
                  <td><a href="<?php echo $_SERVER['PHP_SELF'] . '?database=' . $_REQUEST['database'] . '&collection=' . $collection->getName() ?>"><?php echo $collection->getName() ?></a></td>
                  <td><a href="<?php echo $_SERVER['PHP_SELF'] ?>?database=<?php echo $_REQUEST['database'] ?>&delete_collection=<?php echo $collection->getName() ?>" onClick="return confirm('Are you sure you want to delete this collection?');">Delete</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php elseif ( ! isset($_REQUEST['id'])): ?>
            <h2>
              <a href="<?php echo $_SERVER['PHP_SELF'] ?>">Databases</a> >>
              <a href="<?php echo $_SERVER['PHP_SELF'] ?>?database=<?php echo $_REQUEST['database'] ?>"><?php echo $_REQUEST['database'] ?></a> >> 
              <?php echo $_REQUEST['collection'] ?>
            </h2>
            <table>
              <thead>
                <th>ID</th>
                <th>First Value</th>
                <th></th>
              </thead>
              <tbody>
                <?php foreach ($mongo->selectDB($_REQUEST['database'])->selectCollection($_REQUEST['collection'])->find() as $document): ?>
                  <tr>
                    <td><a href="<?php echo $_SERVER['PHP_SELF'] . '?database=' . $_REQUEST['database'] . '&collection=' . $_REQUEST['collection'] ?>&id=<?php echo (string) $document['_id'] ?>"><?php echo (string) $document['_id'] ?></a></td>
                    <td>
                      <?php $values = array_values($document) ?>
                      <?php echo $values[1]  ?>
                    </td>
                    <td><a href="<?php echo $_SERVER['PHP_SELF'] . '?database=' . $_REQUEST['database'] . '&collection=' . $_REQUEST['collection'] ?>&delete_document=<?php echo (string) $document['_id'] ?>" onClick="return confirm('Are you sure you want to delete this document?');">Delete</a></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
        <?php else: ?>
          <h2>
            <a href="<?php echo $_SERVER['PHP_SELF'] ?>">Databases</a> >>
            <a href="<?php echo $_SERVER['PHP_SELF'] ?>?database=<?php echo $_REQUEST['database'] ?>"><?php echo $_REQUEST['database'] ?></a> >> 
            <a href="<?php echo $_SERVER['PHP_SELF'] . '?database=' . $_REQUEST['database'] . '&collection=' . $_REQUEST['collection'] ?>"><?php echo $_REQUEST['collection'] ?></a> >> 
            <?php echo $_REQUEST['id'] ?>
          </h2>
          <?php $document = $mongo->selectDB($_REQUEST['database'])->selectCollection($_REQUEST['collection'])->findOne(array('_id' => new MongoId($_REQUEST['id']))); ?>
          
          <?php
          function prepareEditValue($value)
          {
            $prepared = array();
            foreach ($value as $key => $value) {
              if ($key === '_id') {
                $value = (string) $value;
              }
              if ($key === '$id') {
                $value = (string) $value;
              }
              if (is_array($value)) {
                $prepared[$key] = prepareEditValue($value);
              } else {
                $prepared[$key] = $value;
              }
            }
            return $prepared;
          }
          $prepared = prepareEditValue($document);
          $value = var_export($prepared, true);
          ?>
          <pre><code><?php print_r($prepared) ?></code></pre>
          <form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
            <?php foreach ($_REQUEST as $k => $v): ?>
              <input type="hidden" name="<?php echo $k ?>" value="<?php echo $v ?>" />
            <?php endforeach; ?>
            <textarea style="margin-left: 30px; margin-right: 30px; margin-bottom: 30px; width: 94%; height: 100%;" name="value"><?php echo $value ?></textarea>
            <input type="submit" name="save" value="Save" />
          </form>
        <?php endif; ?>
      </div>
    </div>
  </body>
</html>