<?php
function smarty_function_IWbookingsadminmenulinks($params, &$smarty)
{
    $func = FormUtil::getPassedValue('func', isset($args['func']) ? $args['func'] : null, 'GET');
    $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');
    
	$dom = ZLanguage::getModuleDomain('IWbookings');	
	// set some defaults
	if (!isset($params['start'])) {
		$params['start'] = '[';
	}
	if (!isset($params['end'])) {
		$params['end'] = ']';
	}
	if (!isset($params['seperator'])) {
		$params['seperator'] = '|';
	}
	if (!isset($params['class'])) {
		$params['class'] = 'pn-menuitem-title';
	}

	$bookingsadminmenulinks = "<span class=\"" . $params['class'] . "\">" . $params['start'] . " ";

	if (SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_ADMIN)) {
		$bookingsadminmenulinks .= "<a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('IWbookings', 'admin', 'newItem', array('m'=>'n'))) . "\">" . __('Add a new booking', $dom) . "</a> " . $params['seperator'];

		$bookingsadminmenulinks .= " <a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('IWbookings', 'admin', 'main')) . "\">" . __('Show rooms and equipment bookings', $dom) . "</a> ";

	    if ($func == 'assigna'){        
		        $bookingsadminmenulinks .= $params['seperator'] . " <a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('IWbookings', 'admin', 'buida', array('sid' => $sid))) . "\">" . __('Empty table', $dom) . "</a> ";
		}
		
		$bookingsadminmenulinks .= $params['seperator'] . " <a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('IWbookings', 'admin', 'conf')) . "\">" . __('Module configuration', $dom) . "</a> ";
	}

	$bookingsadminmenulinks .= $params['end'] . "</span>\n";

	return $bookingsadminmenulinks;
}