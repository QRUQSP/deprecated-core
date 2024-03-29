<?php
//
// Description
// ===========
// This function will return an array with the list of modules available in the system.
//
// Arguments
// ---------
//
//
function qruqsp_core_getModuleList($q) {

    //
    // This list has to be built from the directory structure
    //
    if( isset($q['config']['qruqsp.core']['packages']) && $q['config']['qruqsp.core']['packages'] != '' ) {
        $packages = preg_split('/,/', $q['config']['qruqsp.core']['packages']);
    } else {
        $packages = array('qruqsp');                // Default to qruqsp
    }

    //
    // Build the list of modules from package directories, unless 
    // otherwise specified in the config
    //
    $rsp = array();
    foreach($packages as $package) {
        $dir = $q['config']['qruqsp.core']['root_dir'] . '/' . $package . '-mods/';
        if( !is_dir($dir) ) {
            continue;
        }

        //
        // Check if there is a list of modules overriding in the config file for this package
        //
        if( isset($q['config']['qruqsp.core'][$package . '.modules']) 
            && $q['config']['qruqsp.core'][$package . '.modules'] != ''
            && $q['config']['qruqsp.core'][$package . '.modules'] != '*' ) {
            $modules = preg_split('/,/', $q['config']['qruqsp.core'][$package . '.modules']);
        } 
    
        //
        // If nothing set in config, build from directory, ignoring core
        //
        else {
            $modules = array();
            $dh = opendir($dir);
            while( false !== ($filename = readdir($dh))) {
                // Skip all files starting with ., and core
                // and other reserved named modules which should be always available
                if( $filename[0] == '.' 
                    ) {
                    continue;
                }
                if( is_dir($dir . $filename) && file_exists($dir . $filename . '/_info.ini')) {
                    array_push($modules, $filename);
                }
            }
            closedir($dh);
        }

        sort($modules);

        foreach($modules as $module) {
            if( file_exists($dir . $module . '/_info.ini') ) {
                $info = parse_ini_file($dir . $module . '/_info.ini');
                if( isset($info['name']) && $info['name'] != '' ) {
                    // Assume active is No, this function just returns what is installed
                    $mod = array('label'=>$info['name'], 'package'=>$package, 'name'=>$module, 'installed'=>'Yes', 'active'=>'No');
                    if( isset($info['optional']) ) {
                        $mod['optional'] = $info['optional'];
                    }
                    array_push($rsp, $mod);
                }
            }
        }
    }


    return array('stat'=>'ok', 'modules'=>$rsp);
}
?>
