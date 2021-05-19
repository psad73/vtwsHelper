<?php

require_once 'include/Webservices/AddRelated.php';
require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Delete.php';
require_once 'include/Webservices/DescribeObject.php';
require_once 'include/Webservices/ModuleTypes.php';
require_once 'include/Webservices/Query.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Revise.php';
require_once 'include/Webservices/Update.php';
require_once 'include/Webservices/Utils.php';
require_once 'vtlib/Vtiger/Link.php';

class vtwsHelper
{

    public static function addRelated($sourceItem, $relatedItem)
    {
        vtws_query("update_product_relations");
        try {
            vtws_add_related($sourceItem, $relatedItem, 'Sales Order');
        } catch (WebServiceException $ex) {
            echo $ex->getMessage();
            return false;
        }
        return $sourceItem;
    }

    public static function getEntity($wsId, $id = false)
    {
        $currentUser = self::getCurrentUser();
        if (!preg_match("/([0-9]+)x([0-9]+)/", $wsId, $m)) {
            $wsId = self::getWsId($wsId, $id);
        }
        try {
            $entity = vtws_retrieve($wsId, $currentUser);
        } catch (WebServiceException $ex) {
            echo $ex->getMessage();
            return false;
        }
        return $entity;
    }

    public static function createEntity($module, $data)
    {
        $currentUser = self::getCurrentUser();
        try {
            $entity = vtws_create($module, $data, $currentUser);
        } catch (WebServiceException $ex) {
            echo $ex->getMessage();
        }
        return $entity;
    }

    public static function getCrmId($moduleName, $id)
    {
        $module = self::describeModule($moduleName);
        $crmId = $module['idPrefix'] . 'x' . $id;
        return $crmId;
    }

    public static function describeModule($moduleName)
    {
        $user = self::getCurrentUser();
        $moduleDescription = vtws_describe($moduleName, $user);
        return $moduleDescription;
    }

    public static function listTypes()
    {
        $current_user = self::getCurrentUser();

        try {
            $typeInformation = vtws_listtypes(array(), $current_user);
            foreach ($typeInformation['types'] as $name) {
                echo sprintf("%s\n", $name);
            }
            foreach ($typeInformation['information'] as $name => $information) {
                echo sprintf("Name: %s, Label: %s, SingluarLabel: %s, IsEntity: %s \n",
                    $name, $information['label'], $information['singular'], ($information['isEntity'] ? "yes" : "no"));
            }
        } catch (WebServiceException $ex) {
            echo $ex->getMessage();
        }
    }

    public static function getWsId($moduleName, $id)
    {
        try {
            $wsid = vtws_getWebserviceEntityId($moduleName, $id); // Module_Webservice_ID x CRM_ID
        } catch (WebServiceException $ex) {
            echo $ex->getMessage();
        }
        return $wsid;
    }

    public static function getCurrentUser()
    {
        global $current_user;
        //$currentUser = CRMEntity::getInstance('Users');
        //$currentUser->retrieveCurrentUserInfoFromFile(1);
        $currentUser = $current_user;
        //var_dump($currentUser);
        return $currentUser;
    }

    public static function getEntityCrmId($module, $id)
    {
        $user = self::getCurrentUser();
        $moduleDescription = vtws_describe($module, $user);
        $crmId = $moduleDescription['idPrefix'] . 'x' . (string) $id;
        return $crmId;
    }

    public static function getAllEntities($module)
    {
        $currentUser = self::getCurrentUser();
        try {
            $q = "SELECT * FROM " . $module . "";
            $q = $q . ';'; // NOTE: Make sure to terminate query with ;
            $records = vtws_query($q, $currentUser);
        } catch (WebServiceException $ex) {
            echo $ex->getMessage();
        }
        return $records;
    }

    public static function getTicketsForCompany($accountId)
    {
        $currentUser = self::getCurrentUser();
        $query = sprintf("SELECT * FROM HelpDesk WHERE parent_id = '%s' AND ticketstatus != 'closed';", $accountId);
        $r = vtws_query($query, $currentUser);
        return $r;
    }

    public static function funnyTicketSorter($a, $b)
    {
        //var_dump($a['id'] < $b['id']);
        if (($a['ticketpriorities'] == 'Miesięczny') && ($b['ticketpriorities'] != 'Miesięczny')) {
            return false;
        }
        if (($b['ticketpriorities'] == 'Miesięczny') && ($a['ticketpriorities'] != 'Miesięczny')) {
            return true;
        }

        if ($a['ticket_no'] < $b['ticket_no']) {
            return true;
        } else {
            return false;
        }
    }

    public static function convRequestDataToArray(&$array)
    {
        $tempArray = $array;
        $array = [];
        foreach ($tempArray as $ar) {
            $array[$ar['name']] = $ar['value'];
        }
        return $array;
    }

    public static function parseCrmId($crmId)
    {
        preg_match("/([0-9]+)x([0-9]+)/", $crmId, $m);
        $module = self::getModuleById($m[1]);

        $parsedId = [
            'crmid' => $m[0],
            'entityid' => $m[2],
            'moduleid' => $m[1],
            'modulename' => $module['name']
        ];

        return $parsedId;
    }

    public static function addEventForTicket($ticketId, $data)
    {
        $user = vtwsHelper::getCurrentUser();
        $assigned_user_id = self::getCrmId('Users', $user->id);
        $eventStatus = self::getEventStatus($data['eventstatus']);
        $activityType = self::getActivityType($data['activitytype']);
        $date_start = date('Y-m-d', strtotime($data['date_start']));
        $due_date = date('Y-m-d', strtotime($data['due_date']));
        $time_start = date('H:i:s', strtotime($data['date_start']));
        $time_end = date('H:i:s', strtotime($data['due_date']));
        $parent_id = self::parseCrmId($data['ticketId']);
        $eventData = [
            'assigned_user_id' => $assigned_user_id,
            'activitytype' => $activityType['activitytype'],
            'date_start' => $date_start,
            'due_date' => $due_date,
            'duration_hours' => $data['duration'],
            'eventstatus' => $eventStatus['eventstatus'],
            'subject' => $data['subject'],
            'time_start' => $time_start,
            'time_end' => $time_end,
            'parent_id' => $parent_id['crmid'],
            'visibility' => 'Public'
        ];
        $event = vtws_create('Events', $eventData, $user);
        return $event;
    }

    public static function getAllRecords($module)
    {
        $db = PearDatabase::getInstance();
    }

    public static function getActivityTypes()
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT * FROM vtiger_activitytype ORDER BY presence DESC";
        $rows = $db->pquery($sql, []);
        $activityTypes = [];
        foreach ($rows as $row) {
            $activityTypes[$row['activitytypeid']] = $row['activitytype'];
        }
        return $activityTypes;
    }

    public static function getEventStatuses()
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT * FROM vtiger_eventstatus ORDER BY presence DESC";
        $rows = $db->pquery($sql, []);
        $eventStatuses = [];
        foreach ($rows as $row) {
            $eventStatuses[$row['eventstatusid']] = $row['eventstatus'];
        }
        return $eventStatuses;
    }

    public static function getActivityType($activityTypeId)
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT * FROM vtiger_activitytype WHERE activitytypeid = ?";
        $result = $db->pquery($sql, [$activityTypeId]);
        if ($result->NumRows() > 0) {
            $activityType = $result->GetRowAssoc();
        } else {
            $activityType = false;
        }
        return $activityType;
    }

    public static function getEventStatus($eventStatusId)
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT * FROM vtiger_eventstatus WHERE eventstatusid = ?";
        $result = $db->pquery($sql, [$eventStatusId]);
        if ($result->NumRows() > 0) {
            $eventStatus = $result->GetRowAssoc();
        } else {
            $eventStatus = false;
        }
        return $eventStatus;
        /**
          var_dump($result->NumRows());
          var_dump($eventStatusId);
          var_dump(get_class_methods($result));
         */
    }

    public static function addSalesOrderToQuote($quoteId, $salesOrderId)
    {
        $currentUser = self::getCurrentUser();
        $query = "UPDATE vtiger_salesorder SET quoteid = 666 WHERE salesorderid = 385";
        $r = vtws_query($query, $currentUser);
        var_dump($r);
        return $r;
    }

    public static function getIdFromCrmId($crmId)
    {
        $idParts = preg_split("/x/", $crmId);
        return $idParts[1];
    }

    /**
     *
     * Not working
     * @param type $item
     * @return type
     */
    public static function updateEntity($item)
    {
        $user = self::getCurrentUser();
        $result = vtws_update($item, $user);
        return $result;
    }

    public static function addRelation($mainCrmId, $relatedCrmId)
    {

    }

    public static function renderTemplate($tplName, $tplVars, $options = [])
    {
        global $root_directory;
        $view = new Vtiger_BasicAjax_View();
        $request = new Vtiger_Request([]);
        $defaultOptions = [
            'tplDir' => "/layouts/v7/modules/",
        ];
        $options = array_merge($defaultOptions, $options);
        $templateDir = '/' . join('/', [
                trim($root_directory, '/'),
                trim($options['tplDir'], '/'),
            ]) . '/';
        $viewer = $view->getViewer($request);
        $viewer->addTemplateDir($templateDir);

        $html = $viewer->fetch($tplName, $tplVars);
        return $html;
    }

    public static function getLayoutDirForModule($modName)
    {
        global $root_directory;
        $dir = '/' . join('/', [
                trim('/layouts/v7/modules/', '/'),
                $modName
            ]) . '/';
        return $dir;
    }

    public static function getModuleById($moduleId)
    {
        $db = PearDatabase::getInstance();
        $sql = "SELECT id, name FROM vtiger_ws_entity WHERE id = ?";
        $result = $db->pquery($sql, [$moduleId]);
        if ($result->NumRows() > 0) {
            $module = $result->GetRowAssoc();
        } else {
            $module = false;
        }
        return $module;
    }

    /**
     *  Installation/uninstall
     *
     */
    public static function getTabId()
    {
        return 51;
    }

    private static function getModuleLinks()
    {
        // id = 0 for SCRIPTS, visible always
        return [
            [
                'id' => 56,
                'type' => 'HEADERSCRIPT',
                'name' => 'QSupportJs',
                'path' => 'layouts/v7/modules/QSupport/resources/QSupport.js',
                'sequence' => 0
            ],
            [
                'id' => 3,
                'type' => 'DASHBOARDWIDGET',
                'name' => 'QSupport',
                'path' => 'index.php?module=QSupport&view=ShowWidget&name=Support',
                'sequence' => 1
            ],
            [
                'id' => 3,
                'type' => 'DASHBOARDWIDGET',
                'name' => 'QSupportQuickEventAdd',
                'path' => 'index.php?module=QSupport&view=ShowWidget&name=QuickEventAdd',
                'sequence' => 1
            ],
            [
                'id' => 51,
                'type' => 'HEADERCSS',
                'name' => 'QSupportCss',
                'path' => 'layouts/v7/modules/QSupport/css/qsupport.css',
                'sequence' => 0
            ],
            [
                'id' => 51,
                'type' => 'HEADERSCRIPT',
                'name' => 'BootstrapDateTimePickerJS',
                'path' => 'layouts/v7/modules/QSupport/resources/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js',
                'sequence' => 0
            ],
            [
                'id' => 51,
                'type' => 'HEADERCSS',
                'name' => 'BootstrapDateTimePickerCss',
                'path' => 'layouts/v7/modules/QSupport/resources/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css',
                'sequence' => 0
            ],
        ];
    }

    public static function vtDashboardWidget()
    {
        $output = "This is a widget!";
        return $output;
    }

    public static function unregisterLinks()
    {
        $links = self::getModuleLinks();
        $tabId = self::getTabId();
        foreach ($links as $link) {
            Vtiger_Link::deleteLink($link['id'], $link['type'], $link['name'], $link['path']);
        }
    }

    public static function registerLinks()
    {
        $links = self::getModuleLinks();
        $tabId = self::getTabId();
        foreach ($links as $link) {
            Vtiger_Link::addLink($link['id'], $link['type'], $link['name'], $link['path'], "", $link['sequence'], "");
        }
    }

    public static function setDebug($debug = true)
    {
        if ($debug) {
            ini_set('error_reporting', E_ALL);
            ini_set('display_errors', true);
        }
    }
}
