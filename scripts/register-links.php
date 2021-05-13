<?php
require_once('include/events/include.inc');
require_once 'vtlib/Vtiger/Link.php';

Vtiger_Link::deleteLink(56, "HEADERSCRIPT", "QSupportJs", "layouts/v7/modules/QSupport/resources/QSupport.js");
Vtiger_Link::addLink(56, "HEADERSCRIPT", "QSupportJs", "layouts/v7/modules/QSupport/resources/QSupport.js", "", "", "");

Vtiger_Link::deleteLink(56, "DASHBOARDWIDGET", "QSupport", "index.php?module=QSupport&view=ShowWidget&name=Support");
Vtiger_Link::addLink(56, "DASHBOARDWIDGET", "QSupport", "index.php?module=QSupport&view=ShowWidget&name=Support");

Vtiger_Link::deleteLink(56, "HEADERCSS", "QSupportCss", "layouts/v7/modules/QSupport/css/qsupport.css");
Vtiger_Link::addLink(56, "HEADERCSS", "QSupportCss", "layouts/v7/modules/QSupport/css/qsupport.css", "", "", "");

echo 'JS Added Successfully.' ."\n";

