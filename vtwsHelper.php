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

    public static function describeModule($module)
    {

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
            $q = "SELECT * FROM " . $module . " LIMIT 100";
            $q = $q . ';'; // NOTE: Make sure to terminate query with ;
            $records = vtws_query($q, $currentUser);
            print_r($records);
        } catch (WebServiceException $ex) {
            echo $ex->getMessage();
        }
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

    public static function setDebug()
    {
        ini_set('error_reporting', E_ALL);
        ini_set('display_errors', true);
    }
}
