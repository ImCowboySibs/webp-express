<?php

namespace WebPExpress;

use \WebPExpress\Config;
use \WebPExpress\FileHelper;
use \WebPExpress\HTAccessRules;
use \WebPExpress\Paths;
use \WebPExpress\State;

class HTAccess
{
    // (called from this file only. BUT our saveRules methods calls it, and it is called from several classes)
    public static function generateHTAccessRulesFromConfigObj($config, $htaccessDir = 'index')
    {
        return HTAccessRules::generateHTAccessRulesFromConfigObj($config, $htaccessDir);
    }

    /* only called from page-messages.inc, but commented out there... */
    public static function generateHTAccessRulesFromConfigFile($htaccessDir = '') {
        if (Config::isConfigFileThereAndOk()) {
            return self::generateHTAccessRulesFromConfigObj(Config::loadConfig(), $htaccessDir);
        } else {
            return false;
        }
    }

    public static function arePathsUsedInHTAccessOutdated() {
        if (!Config::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return false;
        }

        $pathsGoingToBeUsedInHtaccess = [
            'wod-url-path' => Paths::getWodUrlPath(),
        ];

        $config = Config::loadConfig();
        if ($config === false) {
            // corrupt or not readable
            return true;
        }

        foreach ($config['paths-used-in-htaccess'] as $prop => $value) {
            if ($value != $pathsGoingToBeUsedInHtaccess[$prop]) {
                return true;
            }
        }
    }

    public static function doesRewriteRulesNeedUpdate($newConfig) {
        if (!Config::isConfigFileThere()) {
            // this properly means that rewrite rules have never been generated
            return true;
        }

        $oldConfig = Config::loadConfig();
        if ($oldConfig === false) {
            // corrupt or not readable
            return true;
        }

        $propsToCompare = [
            'forward-query-string' => true,
            'image-types' => 1,
            'redirect-to-existing-in-htaccess' => false,
            'only-redirect-to-converter-on-cache-miss' => false,
            'success-response' => 'converted',
            'cache-control' => 'no-header',
            'cache-control-custom' => 'public, max-age:3600',
            'cache-control-max-age' => 'one-week',
            'cache-control-public' => true,
            'enable-redirection-to-webp-realizer' => false,
            'enable-redirection-to-converter' => true,
            'destination-folder' => 'separate',
            'destination-extension' => 'append',
            'destination-structure' => 'doc-root'
        ];

        /*
        if (isset($newConfig['redirect-to-existing-in-htaccess']) && $newConfig['redirect-to-existing-in-htaccess']) {
            $propsToCompare['destination-folder'] = 'separate';
            $propsToCompare['destination-extension'] = 'append';
            $propsToCompare['destination-structure'] = 'doc-root';
        }*/

        foreach ($propsToCompare as $prop => $behaviourBeforeIntroduced) {
            if (!isset($newConfig[$prop])) {
                continue;
            }
            if (!isset($oldConfig[$prop])) {
                // Do not trigger .htaccess update if the new value results
                // in same old behaviour (before this option was introduced)
                if ($newConfig[$prop] == $behaviourBeforeIntroduced) {
                    continue;
                } else {
                    // Otherwise DO trigger .htaccess update
                    return true;
                }
            }
            if ($newConfig[$prop] != $oldConfig[$prop]) {
                return true;
            }
        }

        if (!isset($oldConfig['paths-used-in-htaccess'])) {
            return true;
        }

        return self::arePathsUsedInHTAccessOutdated();
    }

    /**
     *  Must be parsed ie "wp-content", "index", etc. Not real dirs
     */
    public static function addToActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (!in_array($dirId, $activeHtaccessDirs)) {
            $activeHtaccessDirs[] = $dirId;
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function removeFromActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        if (in_array($dirId, $activeHtaccessDirs)) {
            $activeHtaccessDirs = array_diff($activeHtaccessDirs, [$dirId]);
            State::setState('active-htaccess-dirs', array_values($activeHtaccessDirs));
        }
    }

    public static function isInActiveHTAccessDirsArray($dirId)
    {
        $activeHtaccessDirs = State::getState('active-htaccess-dirs', []);
        return (in_array($dirId, $activeHtaccessDirs));
    }

    public static function hasRecordOfSavingHTAccessToDir($dir) {
        $dirId = Paths::getAbsDirId($dir);
        if ($dirId !== false) {
            return self::isInActiveHTAccessDirsArray($dirId);
        }
        return false;
    }


    /**
     * @return  string|false  Rules, or false if no rules found or file does not exist.
     */
    public static function extractWebPExpressRulesFromHTAccess($filename) {
        if (FileHelper::fileExists($filename)) {
            $content = FileHelper::loadFile($filename);
            if ($content === false) {
                return false;
            }

            $pos1 = strpos($content, '# BEGIN WebP Express');
            if ($pos1 === false) {
                return false;
            }
            $pos2 = strrpos($content, '# END WebP Express');
            if ($pos2 === false) {
                return false;
            }
            return substr($content, $pos1, $pos2 - $pos1);
        } else {
            // the .htaccess isn't even there. So there are no rules.
            return false;
        }
    }

    /**
     *  Sneak peak into .htaccess to see if we have rules in it
     *  This may not be possible (it requires read permission)
     *  Return true, false, or null if we just can't tell
     */
    public static function haveWeRulesInThisHTAccess($filename) {
        if (FileHelper::fileExists($filename)) {
            $content = FileHelper::loadFile($filename);
            if ($content === false) {
                return null;
            }
            $weRules = (self::extractWebPExpressRulesFromHTAccess($filename));
            if ($weRules === false) {
                return false;
            }
            return (strpos($weRules, '<IfModule mod_rewrite.c>') !== false);
        } else {
            // the .htaccess isn't even there. So there are no rules.
            return false;
        }
    }

    public static function haveWeRulesInThisHTAccessBestGuess($filename)
    {
        // First try to sneak peak. May return null if it cannot be determined.
        $result = self::haveWeRulesInThisHTAccess($filename);
        if ($result === true) {
            return true;
        }
        if ($result === null) {
            // We were not allowed to sneak-peak.
            // Well, good thing that we stored successful .htaccess write locations ;)
            // If we recorded a successful write, then we assume there are still rules there
            // If we did not, we assume there are no rules there
            $dir = FileHelper::dirName($filename);
            return self::hasRecordOfSavingHTAccessToDir($dir);
        }
    }

    public static function getRootsWithWebPExpressRulesIn()
    {
        $allIds = Paths::getImageRootIds();
        $result = [];
        foreach ($allIds as $imageRootId) {
            $filename = Paths::getAbsDirById($imageRootId) . '/.htaccess';
            if (self::haveWeRulesInThisHTAccessBestGuess($filename)) {
                $result[] = $imageRootId;
            }

        }
        return $result;
    }

    public static function saveHTAccessRulesToFile($filename, $rules, $createIfMissing = false) {
        if (!@file_exists($filename)) {
            if (!$createIfMissing) {
                return false;
            }
            // insert_with_markers will create file if it doesn't exist, so we can continue...
        }

        $existingFilePermission = null;
        $existingDirPermission = null;

        // Try to make .htaccess writable if its not
        if (@file_exists($filename)) {
            if (!@is_writable($filename)) {
                $existingFilePermission = FileHelper::filePerm($filename);
                @chmod($filename, 0664);        // chmod may fail, we know...
            }
        } else {
            $dir = FileHelper::dirName($filename);
            if (!@is_writable($dir)) {
                $existingDirPermission = FileHelper::filePerm($dir);
                @chmod($dir, 0775);
            }
        }

        /* Add rules to .htaccess  */
        if (!function_exists('insert_with_markers')) {
            require_once ABSPATH . 'wp-admin/includes/misc.php';
        }

        // Convert to array, because string version has bugs in Wordpress 4.3
        $rules = explode("\n", $rules);
        $success = insert_with_markers($filename, 'WebP Express', $rules);

        // Revert file or dir permissions
        if (!is_null($existingFilePermission)) {
            @chmod($filename, $existingFilePermission);
        }
        if (!is_null($existingDirPermission)) {
            @chmod($dir, $existingDirPermission);
        }

        if ($success) {
            State::setState('htaccess-rules-saved-at-some-point', true);

            //$containsRules = (strpos(implode('',$rules), '# Redirect images to webp-on-demand.php') != false);
            $containsRules = (strpos(implode('',$rules), '<IfModule mod_rewrite.c>') !== false);

            $dir = FileHelper::dirName($filename);
            $dirId = Paths::getAbsDirId($dir);
            if ($dirId !== false) {
                if ($containsRules) {
                    self::addToActiveHTAccessDirsArray($dirId);
                } else {
                    self::removeFromActiveHTAccessDirsArray($dirId);
                }
            }
        }

        return $success;
    }

    /* only called in this file */
    public static function saveHTAccessRulesToFirstWritableHTAccessDir($dirs, $rules)
    {
        foreach ($dirs as $dir) {
            if (self::saveHTAccessRulesToFile($dir . '/.htaccess', $rules, true)) {
                return $dir;
            }
        }
        return false;
    }


    /**
     *  Try to deactivate all .htaccess rules.
     *  If success, we return true.
     *  If we fail, we return an array of filenames that have problems
     */
    public static function deactivateHTAccessRules() {
        //return self::saveHTAccessRules('# Plugin is deactivated');
        $indexDir = Paths::getIndexDirAbs();
        $homeDir = Paths::getHomeDirAbs();
        $wpContentDir = Paths::getContentDirAbs();
        $pluginDir = Paths::getPluginDirAbs();
        $uploadDir = Paths::getUploadDirAbs();
        $themesDir = Paths::getThemesDirAbs();

        $dirsToClean = [$indexDir, $homeDir, $wpContentDir, $pluginDir, $uploadDir, $themesDir];

        $failures = [];

        foreach ($dirsToClean as $dir) {
            $filename = $dir . '/.htaccess';
            if (!FileHelper::fileExists($filename)) {
                continue;
            } else {
                if (self::haveWeRulesInThisHTAccessBestGuess($filename)) {
                    if (!self::saveHTAccessRulesToFile($filename, '# Plugin is deactivated', false)) {
                        $failures[] = $filename;
                    }
                }
            }
        }
        if (count($failures) == 0) {
            return true;
        }
        return $failures;
    }

    public static function testLinks($config) {
        if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false )) {
            if ($config['operation-mode'] != 'no-conversion') {
                if ($config['image-types'] != 0) {
                    $webpExpressRoot = Paths::getPluginUrlPath();
                    $links = '';
                    if ($config['enable-redirection-to-converter']) {
                        $links = '<br>';
                        $links .= '<a href="/' . $webpExpressRoot . '/test/test.jpg?debug&time=' . time() . '" target="_blank">Convert test image (show debug)</a><br>';
                        $links .= '<a href="/' . $webpExpressRoot . '/test/test.jpg?' . time() . '" target="_blank">Convert test image</a><br>';
                    }
                    // TODO: webp-realizer test links (to missing webp)
                    if ($config['enable-redirection-to-webp-realizer']) {
                    }

                    // TODO: test link for testing redirection to existing
                    if ($config['redirect-to-existing-in-htaccess']) {

                    }

                    return $links;
                }
            }
        }
        return '';
    }


    public static function getHTAccessDirRequirements() {
        $minRequired = 'index';
        if (Paths::isWPContentDirMovedOutOfAbsPath()) {
            $minRequired = 'wp-content';
            $pluginToo = Paths::isPluginDirMovedOutOfWpContent() ? 'yes' : 'no';
            $uploadToo = Paths::isUploadDirMovedOutOfWPContentDir() ? 'yes' : 'no';
        } else {
            // plugin requirement depends...
            // - if user grants access to 'index', the requirement is Paths::isPluginDirMovedOutOfAbsPath()
            // - if user grants access to 'wp-content', the requirement is Paths::isPluginDirMovedOutOfWpContent()
            $pluginToo = 'depends';

            // plugin requirement depends...
            // - if user grants access to 'index', we should be fine, as UPLOADS is always in ABSPATH.
            // - if user grants access to 'wp-content', the requirement is Paths::isUploadDirMovedOutOfWPContentDir()
            $uploadToo = 'depends';
        }

        return [
            $minRequired,
            $pluginToo,      // 'yes', 'no' or 'depends'
            $uploadToo
        ];
    }

    /**
     *  Try to save the rules.
     *  Returns many details
     *  (called from migrate1.php, reactivate.php, Config.php and this file)
     */
    public static function saveRules($config) {


        list($minRequired, $pluginToo, $uploadToo) = self::getHTAccessDirRequirements();

        $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'wp-content');
        $wpContentDir = Paths::getContentDirAbs();
        $wpContentFailed = !(HTAccess::saveHTAccessRulesToFile($wpContentDir . '/.htaccess', $rules, true));

        $overidingRulesInWpContentWarning = false;
        if ($wpContentFailed) {
            if ($minRequired == 'index') {
                $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'index');
                $indexFailed = !(HTAccess::saveHTAccessRulesToFile(Paths::getIndexDirAbs() . '/.htaccess', $rules, true));

                if ($indexFailed) {
                    $mainResult = 'failed';
                } else {
                    $mainResult = 'index';
                    $overidingRulesInWpContentWarning = self::haveWeRulesInThisHTAccessBestGuess($wpContentDir . '/.htaccess');
                }
            }
        } else {
            $mainResult = 'wp-content';
            // TODO: Change to something like "The rules are placed in the .htaccess file in your wp-content dir."
            //       BUT! - current text is searched for in page-messages.php
            HTAccess::saveHTAccessRulesToFile(Paths::getIndexDirAbs() . '/.htaccess', '# WebP Express has placed its rules in your wp-content dir. Go there.', false);
        }

        /* plugin */
        if ($pluginToo == 'depends') {
            if ($mainResult == 'wp-content') {
                $pluginToo = (Paths::isPluginDirMovedOutOfWpContent() ? 'yes' : 'no');
            } elseif ($mainResult == 'index') {
                $pluginToo = (Paths::isPluginDirMovedOutOfAbsPath() ? 'yes' : 'no');
            } else {
                // $result must be false. So $pluginToo should still be 'depends'
            }
        }
        $pluginFailed = false;
        $pluginFailedBadly = true;
        if ($pluginToo == 'yes') {
            $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'plugin');
            $pluginDir = Paths::getPluginDirAbs();
            $pluginFailed = !(HTAccess::saveHTAccessRulesToFile($pluginDir . '/.htaccess', $rules, true));
            if ($pluginFailed) {
                $pluginFailedBadly = self::haveWeRulesInThisHTAccessBestGuess($pluginDir . '/.htaccess');
            }
        }

        /* upload */
        if ($uploadToo == 'depends') {
            if ($mainResult == 'wp-content') {
                $uploadToo = (Paths::isUploadDirMovedOutOfWPContentDir() ? 'yes' : 'no');
            } elseif ($mainResult == 'index') {
                $uploadToo = (Paths::isUploadDirMovedOutOfAbsPath() ? 'yes' : 'no');
            } else {
                // $result must be false. So $uploadToo should still be 'depends'
            }
        }
        $uploadFailed = false;
        $uploadFailedBadly = true;
        if ($uploadToo == 'yes') {
            $uploadDir = Paths::getUploadDirAbs();
            $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'uploads');
            $uploadFailed = !(HTAccess::saveHTAccessRulesToFile($uploadDir . '/.htaccess', $rules, true));
            if ($uploadFailed) {
                $uploadFailedBadly = self::haveWeRulesInThisHTAccessBestGuess($uploadDir . '/.htaccess');
            }
        }

        $rules = HTAccess::generateHTAccessRulesFromConfigObj($config, 'themes');
        $themesFailed = !(HTAccess::saveHTAccessRulesToFile(Paths::getThemesDirAbs() . '/.htaccess', $rules, true));

        return [
            'mainResult' => $mainResult,                // 'index', 'wp-content' or 'failed'
            'minRequired' => $minRequired,              // 'index' or 'wp-content'
            'overidingRulesInWpContentWarning' => $overidingRulesInWpContentWarning,  // true if main result is 'index' but we cannot remove those in wp-content
            'rules' => $rules,                          // The rules we generated
            'pluginToo' => $pluginToo,                  // 'yes', 'no' or 'depends'
            'pluginFailed' => $pluginFailed,            // true if failed to write to plugin folder (it only tries that, if pluginToo == 'yes')
            'pluginFailedBadly' => $pluginFailedBadly,  // true if plugin failed AND it seems we have rewrite rules there
            'uploadToo' => $uploadToo,                  // 'yes', 'no' or 'depends'
            'uploadFailed' => $uploadFailed,
            'uploadFailedBadly' => $uploadFailedBadly,
        ];
    }
}
