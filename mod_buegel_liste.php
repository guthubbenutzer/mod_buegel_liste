<?php
defined('_JEXEC') or die;

require_once dirname(__FILE__) . '/helper.php';

$doc = JFactory::getDocument();
$doc->addStyleSheet(JUri::base() . '/modules/mod_buegel_liste/style.css');

$user = JFactory::getUser();
$userId = $user->id;

$input = JFactory::getApplication()->input;

$filter = $input->getCmd('buegel_filter', '');
$koll   = $input->getCmd('buegel_koll', '');
$search = $input->getString('buegel_search', '');
$sort   = $input->getCmd('buegel_sort', 'buegelnummer');
$order  = $input->getCmd('buegel_order', 'ASC');

// Jetzt korrekt, mit allen Parametern:
$items = ModBuegelListeHelper::getItems($search, $sort, $order, $filter, $koll, $userId);


// --- AJAX Aufrufe ---

if ($input->getCmd('task') === 'favorit' && $userId) {
    require_once dirname(__FILE__) . '/helper.php';
    $buegelId = $input->getInt('buegel_id');
    $status = ModBuegelListeHelper::toggleFavorit($userId, $buegelId);
    echo json_encode(['success' => true, 'active' => $status]);
    jexit();
}

if ($input->getCmd('task') === 'druck' && $userId) {
    require_once dirname(__FILE__) . '/helper.php';
    $buegelId = $input->getInt('buegel_id');
    $status = ModBuegelListeHelper::toggleDruck($userId, $buegelId);
    echo json_encode(['success' => true, 'active' => $status]);
    jexit();
}

if ($input->getCmd('task') === 'batchdruck' && $userId) {
    require_once dirname(__FILE__) . '/helper.php';
    $ids = explode(',', $input->getString('buegel_ids', ''));
    $status = $input->getInt('status', 0);
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id < 1) continue;
        if ($status) {
            ModBuegelListeHelper::setDruck($userId, $id);
        } else {
            ModBuegelListeHelper::removeDruck($userId, $id);
        }
    }
    echo json_encode(['success' => true]);
    jexit();
}

if ($input->getCmd('task') === 'batchfavorit' && $userId) {
    require_once dirname(__FILE__) . '/helper.php';
    $ids = explode(',', $input->getString('buegel_ids', ''));
    $status = $input->getInt('status', 0);
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id < 1) continue;
        if ($status) {
            ModBuegelListeHelper::setFavorit($userId, $id, true);
        } else {
            ModBuegelListeHelper::removeFavorit($userId, $id);
        }
    }
    echo json_encode(['success' => true]);
    jexit();
}

$user = JFactory::getUser();
$printBuegelData = ModBuegelListeHelper::getPrintBuegelForUser($user->id);
$jsonPrintData = json_encode($printBuegelData);

// Weiter unten im Template brauchst du $jsonPrintData.


// *** KEIN weiteres getItems unten! ***
// ALLES oben erledigen und dann ans Template übergeben:
require JModuleHelper::getLayoutPath('mod_buegel_liste', $params->get('layout', 'default'));
