<?php require('login.php');

$success = FALSE;
require_once('customFieldsData.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $amount = isset($_POST['amount']) && !empty($_POST['amount']) ? $_POST['amount'] : 1;
  $serialNumber = isset($_POST['serialnumber']) && !empty($_POST['serialnumber']) ? $_POST['serialnumber'] : NULL;
  $comment = isset($_POST['comment']) && !empty($_POST['comment']) ? $_POST['comment'] : NULL;
  $subcategories = isset($_POST['subcategories']) && !empty($_POST['subcategories']) ? explode(',', $_POST['subcategories']) : NULL;

  // Custom fields
  if (isset($_POST['itemUpdateId']) && !empty($_POST['itemUpdateId'])) {
    $existingItem = DB::queryFirstRow('SELECT * FROM items WHERE id=%d', intVal($_POST['itemUpdateId']));

    $category = DB::queryFirstRow('SELECT id,amount FROM headCategories WHERE id=%d', intVal($existingItem['headcategory']));
    DB::update('headCategories', array('amount' => $category['amount'] - intVal($existingItem['amount'])), 'id=%d', $category['id']);

    $exitingSubCategories = explode(',', $existingItem['subcategories']);
    foreach ($exitingSubCategories as $subcategoryId) {
      $subCategory = DB::queryFirstRow('SELECT id, amount FROM subCategories WHERE id=%d', $subcategoryId);
      if ($subCategory !== NULL) {
        DB::update('subCategories', array('amount' => $subCategory['amount'] - intVal($existingItem['amount'])), 'id=%d', $subCategory['id']);
      }
    }

    $storage = DB::queryFirstRow('SELECT id,label,amount FROM storages WHERE id=%d', $existingItem['storageid']);
    if ($storage != NULL) {
      DB::update('storages', array('amount' => $storage['amount'] - $existingItem['amount']), 'id=%d', $storage['id']);
    }
  }

  $subIds = array();
  if ($subcategories !== NULL) {
    foreach ($subcategories as $subcategory) {
      $subCategory = DB::queryFirstRow('SELECT id, amount FROM subCategories WHERE name=%s', $subcategory);
      if ($subCategory !== NULL) {
        $subIds[] = $subCategory['id'];
        DB::update('subCategories', array('amount' => $subCategory['amount'] + $amount), 'id=%d', $subCategory['id']);
      } else {
        DB::insert('subCategories', array('name' => $subcategory, 'amount' => $amount));
        $subIds[] = DB::insertId();
      }
    }
  }

  $storage = DB::queryFirstRow('SELECT id,label,amount FROM storages WHERE label=%s', $_POST['storage']);

  if ($storage == NULL) {
    DB::insert('storages', array('label' => $_POST['storage'], 'amount' => $amount));
    $storage['id'] = DB::insertId();
  } else DB::update('storages', array('amount' => $storage['amount'] + $amount), 'id=%d', $storage['id']);

  $category = DB::queryFirstRow('SELECT id,amount FROM headCategories WHERE name=%s', $_POST['category']);
  if ($category == NULL) {
    DB::insert('headCategories', array('name' => $_POST['category'], 'amount' => $amount));
    $category['id'] = DB::insertId();
  } else DB::update('headCategories', array('amount' => $category['amount'] + $amount), 'id=%d', $category['id']);

  $itemCreationId = NULL;
  if (isset($_POST['itemUpdateId']) && !empty($_POST['itemUpdateId'])) {
    $item = DB::update('items', array('label' => $_POST['label'], 'comment' => $comment, 'serialnumber' => $serialNumber, 'amount' => $amount, 'headcategory' => $category['id'], 'subcategories' => (',' . implode($subIds, ',') . ','), 'storageid' => $storage['id']), 'id=%d', $existingItem['id']);
    $itemCreationId = $existingItem['id'];
  } else {
    $item = DB::insert('items', array('label' => $_POST['label'], 'comment' => $comment, 'serialnumber' => $serialNumber, 'amount' => $amount, 'headcategory' => $category['id'], 'subcategories' => (',' . implode($subIds, ',') . ','), 'storageid' => $storage['id']));
    $itemCreationId = DB::insertId();
  }

  foreach(array_keys($_POST) as $key) {
    if (strncmp($key, 'cfd_', 4) === 0) {
      $fieldKey = intVal(explode('_', $key, 2)[1]);
      $value = $_POST[$key];
      $field = DB::queryFirstRow('SELECT `id`, `dataType`, `fieldValues`, `default` FROM `customFields` WHERE `id`=%d', $fieldKey);
      if ($field !== NULL) {
        $fieldType = NULL;
        foreach ($fieldTypesPos as $key => $index) {
          if ($index === intVal($field['dataType'])) {
            $fieldType = $key;
            break;
          }
        }

        if (empty($value)) {
          $convertedValue = $field['default'];
        } else {
          switch ($field['dataType']) {
            case 0:
            case 1:
            case 2:
              $convertedValue = intval($value);
            break;
            case 3:
            case 4:
              $convertedValue = doubleval($value);
            break;
            default:
              $convertedValue = $value;
            break;
          }
        }


        $existing = DB::queryFirstRow('SELECT `id` FROM `fieldData` WHERE `itemId`=%d AND `fieldId`=%d', intval($itemCreationId), intval($field['id']));
        if ($existing == NULL) DB::insert('fieldData', [$fieldType => $convertedValue, 'itemId' => intval($itemCreationId),'fieldId' => intval($field['id'])]);
        else DB::update('fieldData', [$fieldType => $convertedValue, 'itemId' => intval($itemCreationId),'fieldId' => intval($field['id'])], 'id=%d', $existing['id']);
      }
    }
  }

  $success = TRUE;
}

$isEdit = FALSE;
if (isset($_GET['editItem']) && !empty($_GET['editItem'])) {
  $item = DB::queryFirstRow('SELECT * FROM `items` WHERE `id`=%d', intval($_GET['editItem']));
  $customData = DB::query('SELECT * FROM`fieldData` WHERE `itemId`=%d', intval($item['id']));
  $isEdit = TRUE;
}

if (!isset($item)) $item = array();
$storages = DB::query('SELECT `id`, `label` FROM storages');
$categories = DB::query('SELECT `id`, `name` FROM headCategories');
$subcategories = DB::query('SELECT `id`, `name` FROM subCategories');
$customFields = DB::query('SELECT * FROM customFields');
$smarty->assign('success', $success);
$smarty->assign('isEdit', $isEdit);
if ($isEdit) $smarty->assign('editCategory', $item['headcategory']);
else $smarty->assign('editCategory', -1);
$smarty->assign('item', $item);
$smarty->assign('storages', $storages);
$smarty->assign('categories', $categories);
$smarty->assign('subcategories', $subcategories);

$smarty->assign('customData', $customData);
$smarty->assign('customFields', $customFields);
$smarty->assign('fieldTypesPos', $fieldTypesPos);
$smarty->assign('fieldLimits', $fieldLimits);
$smarty->assign('dataExamples', $dataExamples);

if (isset($_POST)) $smarty->assign('POST', $_POST);
$smarty->assign('SESSION', $_SESSION);


$smarty->display('indexpage.tpl');

exit;
