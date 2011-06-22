<?php
class IWbookings_Api_User extends Zikula_AbstractApi {
    public function getall($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        $orderby = "space_name";
        $recordset = DBUtil::selectObjectArray('IWbookings_spaces', '', $orderby, '-1', '-1', 'sid');

        // Check for an error with the database code, and if so set an appropriate
        // error message and return
        if ($recordset === false) {
            return LogUtil::registerError($this->__('Error! Could not load items.'));
        }

        // Return the items
        return $recordset;
    }

    public function get($args) {
        if (!SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_READ)) {
            return false;
        }

        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');

        //Comprovem que el par�metre hagi arribat correctament
        if (!isset($sid) || !is_numeric($sid)) {
            SessionUtil::setVar('errormsg', $this->__('Error! Could not do what you wanted. Please check your input.'));
            return false;
        }
        $where = "sid = '" . (int) DataUtil::formatForStore($sid) . "'";
        $registre = DBUtil::selectObject('IWbookings_spaces', $where);


        //Retormem una matriu amb la informaci�
        return $registre;
    }

    public function getall_hores($args) {
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'GET');

        //Comprovaci� de seguretat. Si falla retorna una matriu buida
        $registres = array();
        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $registres;
        }

        $pntable = DBUtil::getTables();
        $column = $pntable['IWtimeframes_column'];
        $where = "$column[mdid] = " . DataUtil::formatForStore($mdid);
        $orderBy = "$column[start]";
        $items = DBUtil::selectObjectArray('IWtimeframes', $where, $orderBy);

        //$items = DBUtil::selectObjectArray('iw_timeframes', 'mdid='.$mdid, $orderby);
        foreach ($items as $item) {
            $registres[] = array('hora' => date('H:i', strtotime($item['start'])) . " - " . date('H:i', strtotime($item['end'])),
                'descriu' => $item['descriu']);
        }

        //Retornem la matriu plena de registres
        return $registres;
    }

    public function getall_hores_MS($args) {
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'GET');

        //Comprovaci� de seguretat. Si falla retorna una matriu buida
        $registres = array();
        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $registres;
        }

        $orderby = "start";
        $items = DBUtil::selectObjectArray('IWtimeframes', 'mdid=' . $mdid, $orderby);

        foreach ($items as $item) {
            $registres[] = array(
                'id' => date('H:i', strtotime($item['start'])) . " - " . date('H:i', strtotime($item['end'])),
                'name' => date('H:i', strtotime($item['start'])) . " - " . date('H:i', strtotime($item['end'])));
        }

        //Retornem la matriu plena de registres
        return $registres;
    }


    public function marcs($args) {
        //Comprovaci� de seguretat. Si falla retorna una matriu buida
        $regs = array();

        // Security check
        if (!SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        $items = DBUtil::selectObjectArray('IWtimeframes_definition', '', 'nom_marc');
        $regs[] = array('id' => '0', 'name' => '');
        foreach ($items as $item) {
            $regs[] = array('id' => $item[mdid], 'name' => $item[nom_marc]);
        }
        return $regs;
    }

    public function fer_reserva($args) {
        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');
        $inici = FormUtil::getPassedValue('inici', isset($args['inici']) ? $args['inici'] : null, 'GET');
        $final = FormUtil::getPassedValue('final', isset($args['final']) ? $args['final'] : null, 'GET');
        $grup = FormUtil::getPassedValue('grup', isset($args['grup']) ? $args['grup'] : null, 'GET');
        $profe = FormUtil::getPassedValue('usr', isset($args['usr']) ? $args['usr'] : null, 'GET');

        //Comprova que la identitat de l'espai de reserva efectivament hagi arribat
        if ((!isset($sid))) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Security check
        if (!SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_ADD)) {
            return LogUtil::registerError($this->__('You are not allowed to administrate the bookings'), 403);
        }
        // Get day of week
        $dow = date("w", DateUtil::makeTimeStamp($inici));
        $item = array('sid' => $sid,
            'user' => $profe,
            'usrgroup' => $grup,
            'start' => $inici,
            'end' => $final,
            'dayofweek' => $dow,
            'temp' => 0);
        if (!DBUtil::insertObject($item, 'IWbookings', 'bid')) {
            LogUtil::registerError($this->__('Error! Creation attempt failed.'));
            return false;
        }

        // Return the id of the newly created item to the calling process
        return $item['bid'];
    }

    /**
     * Calcula la data inicial de la setmana a la qual pertany una determinada data
     * @author	Josep Ferr�ndiz Farr� (jferran6@xtec.cat)
     * @args The date (a MySQL timestamp or a string)
     * @return	the start date of the week
     */
    public function getWeek($args) {
        $TheDate = FormUtil::getPassedValue('date', isset($args['date']) ? $args['date'] : null, 'GET');
        $format = FormUtil::getPassedValue('format', isset($args['format']) ? $args['format'] : null, 'GET');

        $week = array();

        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $week;
        }

        $avui = DateUtil::makeTimestamp($TheDate);
        $dow = date("w", $avui);
        ($dow == 0) ? $dow = 7 : ""; // If sunday
        $fields = explode('-', $TheDate);


        if ($format == 'ymd') {
            $week['start'] = DateUtil::getDatetime_NextDay((1 - $dow), '%Y-%m-%d', $fields[0], $fields[1], $fields[2]);
            $week['end'] = DateUtil::getDatetime_NextDay((7 - $dow), "%Y-%m-%d %H:%M:%S", $fields[0], $fields[1], $fields[2], 23, 59, 59);
        } else {
            $week['start'] = DateUtil::getDatetime_NextDay((1 - $dow), '%d-%m-%y', $fields[0], $fields[1], $fields[2]);
            $week['end'] = DateUtil::getDatetime_NextDay((7 - $dow), "%d-%m-%y", $fields[0], $fields[1], $fields[2]);
        }
        return $week;
    }

    /**
     * Calculate dates for date navigation purposes
     * @author	Josep Ferr�ndiz Farr� (jferran6@xtec.cat)
     * @args The date (a MySQL timestamp or a string)
     * @return	array. Fields: nextweek, preweek, nextmonth, prevmonth
     */
    public function getJumpDates($args) {
        $TheDate = FormUtil::getPassedValue('date', isset($args['date']) ? $args['date'] : null, 'GET');

        $result = array();
        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return false;
        }

        $fields = explode('-', $TheDate);

        $result['nextweek'] = DateUtil::getDatetime_NextWeek(1, '%d-%m-%y', $fields[0], $fields[1], $fields[2]);
        $result['prevweek'] = DateUtil::getDatetime_NextWeek(-1, '%d-%m-%y', $fields[0], $fields[1], $fields[2]);
        $result['nextmonth'] = DateUtil::getDatetime_NextMonth(1, '%d-%m-%y', $fields[0], $fields[1], $fields[2]);
        $result['prevmonth'] = DateUtil::getDatetime_NextMonth(-1, '%d-%m-%y', $fields[0], $fields[1], $fields[2]);

        return $result;
    }

    public function get_bookingInfo($args) {
        $result = array();
        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $result;
        }

        $bid = FormUtil::getPassedValue('bid', isset($args['bid']) ? $args['bid'] : null, 'GET');

        $pntable = DBUtil::getTables();
        $c = $pntable['IWbookings_column'];

        $bInfo = DBUtil::selectObject('IWbookings', "$c[bid]=" . $bid);
        $result = $bInfo;

        $rs = DBUtil::selectObjectArray('IWbookings', "$c[bkey]=" . $bInfo['bkey'], 'bid');
        $result['count'] = count($rs) - 1; // Num grouped bookings

        return $result;
    }

    public function getall_reserves($args) {
        //Comprovaci� de seguretat. Si falla retorna una matriu buida
        $registres = array();

        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $registres;
        }

        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');
        $startp = FormUtil::getPassedValue('from', isset($args['from']) ? $args['from'] : null, 'GET');
        $endp = FormUtil::getPassedValue('to', isset($args['to']) ? $args['to'] : null, 'GET');
        $dow = FormUtil::getPassedValue('dw', isset($args['dw']) ? $args['dw'] : null, 'GET');

        $pntable = DBUtil::getTables();

        $t = $pntable['IWbookings'];
        $t1 = $pntable['IWbookings_spaces'];
        $c = $pntable['IWbookings_column'];
        $c1 = $pntable['IWbookings_spaces_column'];

        ($sid == -1) ? $espai = '' : $espai = "$t.$c[sid]" . "='" . $sid . "' AND ";
        (empty($dow)) ? $dayofweek = '' : $dayofweek = "$c[dayofweek]" . "='" . $dow . "' AND ";
        $sql = "SELECT $c[start],$c[bid],$c[user],$c[end],$c[usrgroup],$c[temp],$t.$c[sid],$c[reason],$c[dayofweek]
			FROM $t,$t1 
			WHERE ($espai $c[start] BETWEEN '" . $startp . "' AND '" . $endp . "' AND $c[cancel]=0 AND $c1[active]=1 AND $t.$c[sid]=$t1.$c1[sid])
			ORDER BY DATE_FORMAT($c[start], '%H:%i:%s')";

        $rs = DBUtil::executeSQL($sql);

        //Recorrem els registres i els posem dins de la matriu
        for (; !$rs->EOF; $rs->MoveNext()) {
            list($start, $bid, $user, $end, $usrgroup, $temp, $sid, $reason, $dayofweek) = $rs->fields;
            //Comprovaci� de seguretat
            if (SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_READ)) {
                $items[] = array('inici' => $start,
                    'final' => $end,
                    'bid' => $bid,
                    'usuari' => $user,
                    'grup' => $usrgroup,
                    'temp' => $temp,
                    'sid' => $sid,
                    'motiu' => $reason,
                    'dayofweek' => $dayofweek);
            }
        }

        //Retornem la matriu plena de registres
        return $items;
    }

    public function reservat($args) {
        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');
        $start = FormUtil::getPassedValue('start', isset($args['start']) ? $args['start'] : null, 'GET');
        $end = FormUtil::getPassedValue('end', isset($args['end']) ? $args['end'] : null, 'GET');

        $startTime = date('H:i:s', strtotime($start));
        $endTime = date('H:i:s', strtotime($end));

        $registres = array();

        //Comprovaci� de seguretat. Si falla retorna una matriu buida
        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $registres;
        }

        // Get day of week
        $dow = date("w", DateUtil::makeTimeStamp($start));

        $pntable = DBUtil::getTables();
        $c = $pntable['IWbookings_column'];

        $where = " WHERE ($c[sid]= '" . DataUtil::formatForStore($sid) . "'
			AND (($c[start] >= '" . DataUtil::formatForStore($start) . "' AND $c[start]<'" . DataUtil::formatForStore($end) . "')
			OR ($c[end] > '" . DataUtil::formatForStore($start) . "' AND $c[end] <='" . DataUtil::formatForStore($end) . "')
			OR ($c[start]<'" . DataUtil::formatForStore($start) . "' AND $c[end]>'" . DataUtil::formatForStore($end) . "')) AND $c[cancel]=0)";

        $items = DBUtil::selectObjectArray('IWbookings', $where);

        return $items;
    }


    public function reserva($args) {
        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');
        $start = FormUtil::getPassedValue('start', isset($args['start']) ? $args['start'] : null, 'GET');
        $end = FormUtil::getPassedValue('end', isset($args['end']) ? $args['end'] : null, 'GET');
        $group = FormUtil::getPassedValue('usrgroup', isset($args['usrgroup']) ? $args['usrgroup'] : null, 'GET');
        $user = FormUtil::getPassedValue('user', isset($args['user']) ? $args['user'] : null, 'GET');
        $reason = FormUtil::getPassedValue('reason', isset($args['reason']) ? $args['reason'] : null, 'GET');
        $nsessions = FormUtil::getPassedValue('nsessions', isset($args['nsessions']) ? $args['nsessions'] : null, 'GET');
        $nbooking = FormUtil::getPassedValue('nbooking', isset($args['nbooking']) ? $args['nbooking'] : null, 'GET');
        $admin = FormUtil::getPassedValue('admin', isset($args['admin']) ? $args['admin'] : null, 'GET');

        //Comprova que la identitat de l'espai de reserva efectivament hagi arribat
        if ((!isset($sid))) {
            return LogUtil::registerError($this->__('Error! Could not do what you wanted. Please check your input.'));
        }
        // Security check
        if (!SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_ADD)) {
            return LogUtil::registerError($this->__('You are not allowed to administrate the bookings'), 403);
        }

        // Identificates grouped bookings: has the same day of week and time during n consecutive weeks
        $key = DBUtil::selectFieldMax('IWbookings', 'bid') + 1;
        if (empty($nbooking))
            $nbooking = $key;

        // Get day of week
        $dow = date("w", DateUtil::makeTimeStamp($start));
        ($dow == 0) ? $dow = 7 : "";
        ($admin) ? $temp = 1 : 0;
        $item = array('sid' => $sid,
            'user' => $user,
            'usrgroup' => $group,
            'start' => $start,
            'end' => $end,
            'reason' => $reason,
            'dayofweek' => $dow,
            'bkey' => $nbooking,
            'temp' => $temp);
        if (!DBUtil::insertObject($item, 'IWbookings', 'bid')) {
            LogUtil::registerError($this->__('Error! Creation attempt failed.'));
            return false;
        }

        // Return the id of the newly created item to the calling process
        return $nbooking;
    }

    /**
     * Esborra una o un conjunt de reserves
     * @author	Josep Ferr�ndiz Farr� (jferran6@xtec.cat)
     * @arg bid booking id
     * @arg eraseAll bool. If true delete all grouped bookings
     * @arg bookingKey. Identifies a grouped booking
     * @return result from the delete operation
     */
    public function anulla($args) {
        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $registres;
        }

        $bid = FormUtil::getPassedValue('bid', isset($args['bid']) ? $args['bid'] : null, 'GET');
        $multiple = FormUtil::getPassedValue('eraseAll', isset($args['eraseAll']) ? $args['eraseAll'] : null, 'GET');
        $bookingKey = FormUtil::getPassedValue('bookingKey', isset($args['bookingKey']) ? $args['bookingKey'] : null, 'GET');

        if ($multiple) {
            $pntable = DBUtil::getTables();
            $c = $pntable['IWbookings_column'];
            $where = "$c[bkey]=" . $bookingKey;
            return DBUtil::deleteWhere('IWbookings', $where);
        } else {
            return DBUtil::deleteObjectByID('IWbookings', $bid, 'bid');
        }
    }

    public function anullades($args) {
        //Comprovaci� de seguretat. Si falla retorna una matriu buida
        $registres = array();

        if (!SecurityUtil::checkPermission('IWbookings::', '::', ACCESS_READ)) {
            return $registres;
        }

        extract($args);

        //Connectem amb la base de dades
        list($dbconn) = DBConnectionStack::getConnection();
        $pntable = & DBUtil::getTables();

        $t = $pntable['IWbookings'];
        $c = $pntable['IWbookings_column'];

        ($sid == -1) ? $espai = '' : $espai = $c[sid] . '=' . $sid . ' AND ';

        $sql = "SELECT $c[bid],$c[user],$c[start],$c[end],$c[usrgroup],$c[temp] FROM $t WHERE $espai $c[anulla]=1 ORDER BY $c[start]";

        $registre = $dbconn->Execute($sql);

        //Comprovem que la consulta hagi estat amb �xit
        if ($dbconn->ErrorNo() != 0) {
            SessionUtil::setVar('errormsg', $this->__('An error has occurred while getting registers from the data base. Please, contact webmaster'));
            return false;
        }

        //Recorrem els registres i els posem dins de la matriu
        for (; !$registre->EOF; $registre->MoveNext()) {
            list($bid, $usuari, $inici, $final, $grup, $temp) = $registre->fields;
            //Comprovaci� de seguretat
            if (SecurityUtil::checkPermission('IWbookings::', "$usuari::$sid", ACCESS_READ)) {
                $registres[] = array('inici' => $inici, 'final' => $final, 'bid' => $bid, 'usuari' => $usuari, 'grup' => $grup, 'temp' => $temp);
            }
        }
        //Tanquem la BD
        $registre->Close();

        //Retornem la matriu plena de registres
        return $registres;
    }

    public function esborra_antigues($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('You are not allowed to administrate the bookings'), 403);
        }

        $sid = FormUtil::getPassedValue('sid', isset($args['sid']) ? $args['sid'] : null, 'GET');

        if (ModUtil::getVar('IWbookings', 'NTPtime')) {
            // NTP time diference correction
            $now = DateUtil::getDatetime(DateUtil::makeTimestamp() + SessionUtil::getVar('timeOffset'));
        } else {
            // The server date
            $now = DateUtil::getDatetime();
        }

        $pntable = DBUtil::getTables();
        $c = $pntable['IWbookings_column'];

        ($sid == -1) ? $espai = '' : $espai = "$c[sid]" . "='" . $sid . "' AND ";

        $where = "$espai $c[end]< '" . $now . "' AND $c[temp] <> 1";

        DBUtil::deleteWhere('IWbookings', $where);

        return true;
    }

    /**
     * Get time from NTP server
     * @author 	Tony Bhimani http://www.xenocafe.com/tutorials/php/ntp_time_synchronization/index.php
     * @adapted	Josep Ferr�ndiz Farr� (jferran6@xtec.cat)
     * @return	the NTP server in timestamp format
     */
    public function getNTPDate() {
        // Security check
        if (!SecurityUtil::checkPermission('IWbookings::', "::", ACCESS_READ)) {
            return LogUtil::registerError($this->__('You are not allowed to administrate the bookings'), 403);
        }

        // ntp time servers to contact
        // we try them one at a time if the previous failed (failover)
        // if all fail then wait till tomorrow
        //$time_servers = array("pool.ntp.org");
        $time_servers = array("nist1.datum.com",
            "time.nist.gov"); //,"time-a.timefreq.bldrdoc.gov","utcnist.colorado.edu");
        // a flag and number of servers
        $valid_response = false;
        $ts_count = sizeof($time_servers);

        // time adjustment
        // you will need to change this value for your region (seconds)
        $time_adjustment = 0;
        for ($i = 0; $i < $ts_count; $i++) {
            $time_server = $time_servers[$i];
            $fs = @fsockopen("www.xtec.cat", 80);
            if ($fs) { // Has Internet connection. If false, avoid response delay
                $fp = fsockopen($time_server, 37, $errno, $errstr, 1); //15 seconds timeout for socket connection
                $data = NULL;
                while (!feof($fp)) {
                    $data .= fgets($fp, 128);
                }
                fclose($fp);
                fclose($fs);
                // we have a response...is it valid? (4 char string -> 32 bits)
                if (strlen($data) != 4) {
                    ;
                } else {
                    $valid_response = true;
                    break;
                }
            }
        }
        $result = 0;
        if ($valid_response) {
            // time server response is a string - convert to numeric
            $NTPtime = ord($data{0}) * pow(256, 3) + ord($data{1}) * pow(256, 2) + ord($data{2}) * 256 + ord($data{3});
            // convert the seconds to the present date & time
            $TimeFrom1990 = $NTPtime - 2840140800;
            $result = $TimeFrom1990 + 631152000;
        } else {
            LogUtil::registererror($this->__('Can\'t obtain NTP servers time'));
        }
        return $result;
    }
}