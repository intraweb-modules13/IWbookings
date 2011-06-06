<?php

class IWbookings_Installer extends Zikula_AbstractInstaller {

    /**
     * Initialise the IWbookings module creating module tables and module vars
     * @author Albert Pérez Monfort (aperezm@xtec.cat)
     * @author Josep Ferràndiz Farré (jferran6@xtec.cat)
     * @return bool true if successful, false otherwise
     */
    public function Install() {
        // Checks if module IWmain is installed. If not returns error
        $modid = ModUtil::getIdFromName('IWmain');
        $modinfo = ModUtil::getInfo($modid);
        if ($modinfo['state'] != 3) {
            return LogUtil::registerError($this->__('Module IWmain is required. You have to install the IWmain module previously to install it.'));
        }

        // Check if the version needed is correct
        $versionNeeded = '3.0.0';
        if (!ModUtil::func('IWmain', 'admin', 'checkVersion', array('version' => $versionNeeded))) {
            return false;
        }

        // Create module tables
        if (!DBUtil::createTable('IWbookings'))
            return false;
        if (!DBUtil::createTable('IWbookings_spaces'))
            return false;

        //Create indexes
        $pntable = DBUtil::getTables();
        $c = $pntable['IWbookings_column'];
        if (!DBUtil::createIndex($c['sid'], 'IWbookings', 'sid'))
            return false;
        if (!DBUtil::createIndex($c['start'], 'IWbookings', 'start'))
            return false;

        //Create module vars
        ModUtil::setVar('IWbookings', 'group', ''); //grup
        ModUtil::setVar('IWbookings', 'weeks', '1'); // setmanes
        ModUtil::setVar('IWbookings', 'month_panel', '0'); // taula_mes
        ModUtil::setVar('IWbookings', 'weekends', '0');  // capssetmana
        ModUtil::setVar('IWbookings', 'eraseold', '1'); // delantigues
        ModUtil::setVar('IWbookings', 'showcolors', '0'); // mostracolors
        ModUtil::setVar('IWbookings', 'NTPtime', '0');
        //Initialation successfull
        return true;
    }

    /**
     * Delete the IWbookings module
     * @author Albert Pérez Monfort (aperezm@xtec.cat)
     * @return bool true if successful, false otherwise
     */
    function Uninstall() {
        // Delete module table
        DBUtil::dropTable('IWbookings');
        DBUtil::dropTable('IWbookings_spaces');

        //Delete module vars
        ModUtil::delVar('IWbookings', 'groups');
        ModUtil::delVar('IWbookings', 'weeks');
        ModUtil::delVar('IWbookings', 'month_panel');
        ModUtil::delVar('IWbookings', 'weekends');
        ModUtil::delVar('IWbookings', 'eraseold');
        ModUtil::delVar('IWbookings', 'showcolors');
        ModUtil::delVar('IWbookings', 'NTPtime');

        //Deletion successfull
        return true;
    }

    /**
     * Update the IWbookings module
     * @author Albert Pérez Monfort (aperezm@xtec.cat)
     * @author Jaume Fernàndez Valiente (jfern343@xtec.cat)
     * @return bool true if successful, false otherwise
     */
    function upgrade($oldversion) {

        $prefix = $GLOBALS['ZConfig']['System']['prefix'];

        //Delete unneeded tables
        DBUtil::dropTable('iw_noteboard_public');

        //Rename tables
        if (!DBUtil::renameTable('iw_bookings', 'IWbookings'))
            return false;
        if (!DBUtil::renameTable('iw_bookings_spaces', 'IWbookings_spaces'))
            return false;


        // Update module_vars table
        // Update the name (keeps old var value)
        $c = "UPDATE {$prefix}_module_vars SET z_modname = 'IWbookings' WHERE z_bkey = 'iw_bookings'";
        if (!DBUtil::executeSQL($c)) {
            return false;
        }

        //Array de noms
        $oldVarsNames = DBUtil::selectFieldArray("module_vars", 'name', "`z_modname` = 'IWforms'", '', false, '');

        $newVarsNames = Array('group', 'weeks', 'month_panel', 'weekends', 'eraseold', 'showcolors', 'NTPtime');

        $newVars = Array('group' => '',
            'weeks' => '1',
            'month_panel' => '0',
            'weekends' => '0',
            'eraseold' => '1',
            'showcolors' => '0',
            'NTPtime' => '0');

        // Delete unneeded vars
        $del = array_diff($oldVarsNames, $newVarsNames);
        foreach ($del as $i) {
            $this->delVar($i);
        }

        // Add new vars
        $add = array_diff($newVarsNames, $oldVarsNames);
        foreach ($add as $i) {
            $this->setVar($i, $newVars[$i]);
        }

        return true;
    }

}