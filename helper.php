<?php
defined('_JEXEC') or die;

class ModBuegelListeHelper
{
    public static function getItems($search = '', $sort = 'buegelnummer', $order = 'ASC', $filter = '', $koll = '', $userId = null)
    {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
    
        $query->select($db->qn(array('t.id', 't.kollektionsnummer', 't.buegelnummer', 't.buegelname')))
              ->from($db->qn('#__pg45_buegel_model_aktuell') . ' AS t');
    
        // Favoriten-Filter
        if ($filter === 'favoriten' && $userId) {
            $query->join('INNER', '#__buegel_favoriten AS f ON f.buegel_id = t.id AND f.user_id = ' . (int)$userId);
        }
        // Print-Filter
        if ($filter === 'print' && $userId) {
            $query->join('INNER', '#__buegel_druck AS d ON d.buegel_id = t.id AND d.user_id = ' . (int)$userId);
        }
        // Kollektion (immer anwendbar, auch zusätzlich zu Favorit/Print)
        if (!empty($koll)) {
            $query->where($db->qn('t.kollektionsnummer') . ' = ' . $db->quote($koll));
        }
        // Suche
        if (!empty($search)) {
            $search = $db->quote('%' . $db->escape($search, true) . '%');
            $query->where(
                $db->qn('t.buegelname') . " LIKE $search" .
                " OR " . $db->qn('t.kollektionsnummer') . " LIKE $search" .
                " OR " . $db->qn('t.buegelnummer') . " LIKE $search"
            );
        }
    
        // Sortierung
        $allowedSort = array('kollektionsnummer', 'buegelnummer', 'buegelname');
        if (!in_array($sort, $allowedSort)) $sort = 'buegelnummer';
        $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
        $query->order($db->qn('t.' . $sort) . ' ' . $order);
    
        $db->setQuery($query);
        return $db->loadObjectList();
    }
    
    // Prüfen ob Favorit
    public static function isFavorit($userId, $buegelId) {
        $db = JFactory::getDbo();
        $db->setQuery("SELECT id FROM #__buegel_favoriten WHERE user_id=".(int)$userId." AND buegel_id=".(int)$buegelId);
        return (bool) $db->loadResult();
    }
    

// Favorit setzen/löschen (umschalten)
public static function toggleFavorit($userId, $buegelId) {
    $db = JFactory::getDbo();
    if (self::isFavorit($userId, $buegelId)) {
        $db->setQuery("DELETE FROM #__buegel_favoriten WHERE user_id=".(int)$userId." AND buegel_id=".(int)$buegelId);
        $db->execute();
        return false;
    } else {
        $db->setQuery("INSERT INTO #__buegel_favoriten (user_id, buegel_id) VALUES (".(int)$userId.", ".(int)$buegelId.")");
        $db->execute();
        return true;
    }
}

// Analog für Drucker:
public static function isToPrint($userId, $buegelId) {
    $db = JFactory::getDbo();
    $db->setQuery("SELECT id FROM #__buegel_druck WHERE user_id=".(int)$userId." AND buegel_id=".(int)$buegelId);
    return (bool) $db->loadResult();
}

public static function toggleDruck($userId, $buegelId) {
    $db = JFactory::getDbo();
    if (self::isToPrint($userId, $buegelId)) {
        $db->setQuery("DELETE FROM #__buegel_druck WHERE user_id=".(int)$userId." AND buegel_id=".(int)$buegelId);
        $db->execute();
        return false;
    } else {
        $db->setQuery("INSERT INTO #__buegel_druck (user_id, buegel_id) VALUES (".(int)$userId.", ".(int)$buegelId.")");
        $db->execute();
        return true;
    }
}

public static function setDruck($userId, $buegelId) {
    $db = JFactory::getDbo();
    // Doppelte vermeiden
    $db->setQuery("SELECT id FROM #__buegel_druck WHERE user_id=".(int)$userId." AND buegel_id=".(int)$buegelId);
    if (!$db->loadResult()) {
        $db->setQuery("INSERT INTO #__buegel_druck (user_id, buegel_id) VALUES (".(int)$userId.", ".(int)$buegelId.")");
        $db->execute();
    }
}
public static function removeDruck($userId, $buegelId) {
    $db = JFactory::getDbo();
    $db->setQuery("DELETE FROM #__buegel_druck WHERE user_id=".(int)$userId." AND buegel_id=".(int)$buegelId);
    $db->execute();
}
public static function setFavorit($userId, $buegelId, $isFavorit) {
    $db = JFactory::getDbo();
    if ($isFavorit) {
        // Insert IGNORE
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__buegel_favoriten'))
            ->columns([$db->quoteName('user_id'), $db->quoteName('buegel_id')])
            ->values((int)$userId . ',' . (int)$buegelId);
        try { $db->setQuery($query)->execute(); } catch (\Exception $e) {}
    } else {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__buegel_favoriten'))
            ->where($db->quoteName('user_id') . '=' . (int)$userId)
            ->where($db->quoteName('buegel_id') . '=' . (int)$buegelId);
        $db->setQuery($query)->execute();
    }
}
public static function setToPrint($userId, $buegelId, $toPrint) {
    $db = JFactory::getDbo();
    if ($toPrint) {
        $query = $db->getQuery(true)
            ->insert($db->quoteName('#__buegel_druck'))
            ->columns([$db->quoteName('user_id'), $db->quoteName('buegel_id')])
            ->values((int)$userId . ',' . (int)$buegelId);
        try { $db->setQuery($query)->execute(); } catch (\Exception $e) {}
    } else {
        $query = $db->getQuery(true)
            ->delete($db->quoteName('#__buegel_druck'))
            ->where($db->quoteName('user_id') . '=' . (int)$userId)
            ->where($db->quoteName('buegel_id') . '=' . (int)$buegelId);
        $db->setQuery($query)->execute();
    }
}


public static function removeFavorit($userId, $buegelId) {
    $db = JFactory::getDbo();
    $db->setQuery("DELETE FROM #__buegel_favoriten WHERE user_id=".(int)$userId." AND buegel_id=".(int)$buegelId);
    $db->execute();
}


public static function countFavoriten($userId) {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__buegel_favoriten'))
        ->where($db->quoteName('user_id') . ' = ' . (int)$userId);
    $db->setQuery($query);
    return (int)$db->loadResult();
}

public static function countToPrint($userId) {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__buegel_druck'))
        ->where($db->quoteName('user_id') . ' = ' . (int)$userId);
    $db->setQuery($query);
    return (int)$db->loadResult();
}

public static function getTotalCount() {
    $db = JFactory::getDbo();
    $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__pg45_buegel_model_aktuell')); // Tabelle anpassen, falls nötig!
    $db->setQuery($query);
    return (int)$db->loadResult();
}

public static function getPrintBuegelForUser($userId) {
    $db = JFactory::getDbo();
    // IDs aller Print-Bügel
    $query = $db->getQuery(true)
        ->select('buegel_id')
        ->from('#__buegel_druck')
        ->where('user_id = ' . (int)$userId);
    $db->setQuery($query);
    $druckIds = $db->loadColumn();

    if (!$druckIds) return [];

    // Details laden
    $query = $db->getQuery(true)
        ->select('*')
        ->from('#__pg45_buegel_model_aktuell')
        ->where('id IN (' . implode(',', array_map('intval', $druckIds)) . ')');
    $db->setQuery($query);
    return $db->loadAssocList();
}


}
