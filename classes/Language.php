<?php
/**
 * 2007-2016 PrestaShop
 *
 * Thirty Bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 Thirty Bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    Thirty Bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 Thirty Bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * Class LanguageCore
 *
 * @since 1.0.0
 */
class LanguageCore extends ObjectModel
{
    // @codingStandardsIgnoreStart
    /** @var array Languages cache */
    protected static $_checkedLangs;
    protected static $_LANGUAGES;
    protected static $countActiveLanguages = [];
    protected static $_cache_language_installation = null;
    /** @var string Name */
    public $name;
    /** @var string 2-letter iso code */
    public $iso_code;
    /** @var string 5-letter iso code */
    public $language_code;
    /** @var string date format http://http://php.net/manual/en/function.date.php with the date only */
    public $date_format_lite = 'Y-m-d';
    /** @var string date format http://http://php.net/manual/en/function.date.php with hours and minutes */
    public $date_format_full = 'Y-m-d H:i:s';
    /** @var bool true if this language is right to left language */
    public $is_rtl = false;
    /** @var bool Status */
    public $active = true;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'lang',
        'primary' => 'id_lang',
        'fields'  => [
            'name'             => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
            'iso_code'         => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 2],
            'language_code'    => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageCode', 'size' => 5],
            'active'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'is_rtl'           => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_format_lite' => ['type' => self::TYPE_STRING, 'validate' => 'isPhpDateFormat', 'required' => true, 'size' => 32],
            'date_format_full' => ['type' => self::TYPE_STRING, 'validate' => 'isPhpDateFormat', 'required' => true, 'size' => 32],
        ],
    ];
    protected $webserviceParameters = [
        'objectNodeName'  => 'language',
        'objectsNodeName' => 'languages',
    ];
    protected $translationsFilesAndVars = [
        'fields' => '_FIELDS',
        'errors' => '_ERRORS',
        'admin'  => '_LANGADM',
        'pdf'    => '_LANGPDF',
        'tabs'   => 'tabs',
    ];

    /**
     * LanguageCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function __construct($id = null, $idLang = null)
    {
        parent::__construct($id);
    }

    /**
     * Returns an array of language IDs
     *
     * @param bool     $active Select only active languages
     * @param int|bool $idShop Shop ID
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIDs($active = true, $idShop = false)
    {
        return self::getLanguages($active, $idShop, true);
    }

    /**
     * Returns available languages
     *
     * @param bool     $active  Select only active languages
     * @param int|bool $idShop  Shop ID
     * @param bool     $idsOnly If true, returns an array of language IDs
     *
     * @return array Languages
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLanguages($active = true, $idShop = false, $idsOnly = false)
    {
        if (!self::$_LANGUAGES) {
            Language::loadLanguages();
        }

        $languages = [];
        foreach (self::$_LANGUAGES as $language) {
            if ($active && !$language['active'] || ($idShop && !isset($language['shops'][(int) $idShop]))) {
                continue;
            }

            $languages[] = $idsOnly ? $language['id_lang'] : $language;
        }

        return $languages;
    }

    /**
     * Load all languages in memory for caching
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function loadLanguages()
    {
        self::$_LANGUAGES = [];

        $sql = 'SELECT l.*, ls.`id_shop`
				FROM `'._DB_PREFIX_.'lang` l
				LEFT JOIN `'._DB_PREFIX_.'lang_shop` ls ON (l.id_lang = ls.id_lang)';

        $result = Db::getInstance()->executeS($sql);
        foreach ($result as $row) {
            if (!isset(self::$_LANGUAGES[(int) $row['id_lang']])) {
                self::$_LANGUAGES[(int) $row['id_lang']] = $row;
            }
            self::$_LANGUAGES[(int) $row['id_lang']]['shops'][(int) $row['id_shop']] = true;
        }
    }

    /**
     * @param $id_lang
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLanguage($id_lang)
    {
        if (!array_key_exists((int) $id_lang, self::$_LANGUAGES)) {
            return false;
        }

        return self::$_LANGUAGES[(int) ($id_lang)];
    }

    /**
     * @param $isoCode
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLanguageCodeByIso($isoCode)
    {
        if (!Validate::isLanguageIsoCode($isoCode)) {
            die(Tools::displayError('Fatal error: ISO code is not correct').' '.Tools::safeOutput($isoCode));
        }

        return Db::getInstance()->getValue('SELECT `language_code` FROM `'._DB_PREFIX_.'lang` WHERE `iso_code` = \''.pSQL(strtolower($isoCode)).'\'');
    }

    /**
     * @param $code
     *
     * @return bool|Language
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getLanguageByIETFCode($code)
    {
        if (!Validate::isLanguageCode($code)) {
            die(sprintf(Tools::displayError('Fatal error: IETF code %s is not correct'), Tools::safeOutput($code)));
        }

        // $code is in the form of 'xx-YY' where xx is the language code
        // and 'YY' a country code identifying a variant of the language.
        $langCountry = explode('-', $code);
        // Get the language component of the code
        $lang = $langCountry[0];

        // Find the id_lang of the language.
        // We look for anything with the correct language code
        // and sort on equality with the exact IETF code wanted.
        // That way using only one query we get either the exact wanted language
        // or a close match.
        $idLang = Db::getInstance()->getValue(
            'SELECT `id_lang`, IF(language_code = \''.pSQL($code).'\', 0, LENGTH(language_code)) as found
			FROM `'._DB_PREFIX_.'lang`
			WHERE LEFT(`language_code`,2) = \''.pSQL($lang).'\'
			ORDER BY found ASC'
        );

        // Instantiate the Language object if we found it.
        if ($idLang) {
            return new Language($idLang);
        } else {
            return false;
        }
    }

    /**
     * Return array (id_lang, iso_code)
     *
     * @param string $iso_code Iso code
     *
     * @return array  Language (id_lang, iso_code)
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIsoIds($active = true)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT `id_lang`, `iso_code` FROM `'._DB_PREFIX_.'lang` '.($active ? 'WHERE active = 1' : ''));
    }

    /**
     * @param $from
     * @param $to
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function copyLanguageData($from, $to)
    {
        $result = Db::getInstance()->executeS('SHOW TABLES FROM `'._DB_NAME_.'`');
        foreach ($result as $row) {
            if (preg_match('/_lang/', $row['Tables_in_'._DB_NAME_]) && $row['Tables_in_'._DB_NAME_] != _DB_PREFIX_.'lang') {
                $result2 = Db::getInstance()->executeS('SELECT * FROM `'.$row['Tables_in_'._DB_NAME_].'` WHERE `id_lang` = '.(int) $from);
                if (!count($result2)) {
                    continue;
                }
                Db::getInstance()->execute('DELETE FROM `'.$row['Tables_in_'._DB_NAME_].'` WHERE `id_lang` = '.(int) $to);
                $query = 'INSERT INTO `'.$row['Tables_in_'._DB_NAME_].'` VALUES ';
                foreach ($result2 as $row2) {
                    $query .= '(';
                    $row2['id_lang'] = $to;
                    foreach ($row2 as $field) {
                        $query .= (!is_string($field) && $field == null) ? 'NULL,' : '\''.pSQL($field, true).'\',';
                    }
                    $query = rtrim($query, ',').'),';
                }
                $query = rtrim($query, ',');
                Db::getInstance()->execute($query);
            }
        }

        return true;
    }

    /**
     * @param $iso_code
     *
     * @return bool|mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isInstalled($iso_code)
    {
        if (self::$_cache_language_installation === null) {
            self::$_cache_language_installation = [];
            $result = Db::getInstance()->executeS('SELECT `id_lang`, `iso_code` FROM `'._DB_PREFIX_.'lang`');
            foreach ($result as $row) {
                self::$_cache_language_installation[$row['iso_code']] = $row['id_lang'];
            }
        }

        return (isset(self::$_cache_language_installation[$iso_code]) ? self::$_cache_language_installation[$iso_code] : false);
    }

    /**
     * Check if more on than one language is activated
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function isMultiLanguageActivated($idShop = null)
    {
        return (Language::countActiveLanguages($idShop) > 1);
    }

    /**
     * @param null $id_shop
     *
     * @return mixed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function countActiveLanguages($id_shop = null)
    {
        if (isset(Context::getContext()->shop) && is_object(Context::getContext()->shop) && $id_shop === null) {
            $id_shop = (int) Context::getContext()->shop->id;
        }

        if (!isset(self::$countActiveLanguages[$id_shop])) {
            self::$countActiveLanguages[$id_shop] = Db::getInstance()->getValue(
                '
				SELECT COUNT(DISTINCT l.id_lang) FROM `'._DB_PREFIX_.'lang` l
				JOIN '._DB_PREFIX_.'lang_shop lang_shop ON (lang_shop.id_lang = l.id_lang AND lang_shop.id_shop = '.(int) $id_shop.')
				WHERE l.`active` = 1
			'
            );
        }

        return self::$countActiveLanguages[$id_shop];
    }

    /**
     * @param array $modules_list
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function updateModulesTranslations(Array $modules_list)
    {
        require_once(_PS_TOOL_DIR_.'tar/Archive_Tar.php');

        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $gz = false;
            $files_listing = [];
            foreach ($modules_list as $module_name) {
                $filegz = _PS_TRANSLATIONS_DIR_.$lang['iso_code'].'.gzip';

                clearstatcache();
                if (@filemtime($filegz) < (time() - (24 * 3600))) {
                    if (Language::downloadAndInstallLanguagePack($lang['iso_code'], null, null, false) !== true) {
                        break;
                    }
                }

                $gz = new Archive_Tar($filegz, true);
                $files_list = Language::getLanguagePackListContent($lang['iso_code'], $gz);
                foreach ($files_list as $i => $file) {
                    if (strpos($file['filename'], 'modules/'.$module_name.'/') !== 0) {
                        unset($files_list[$i]);
                    }
                }

                foreach ($files_list as $file) {
                    if (isset($file['filename']) && is_string($file['filename'])) {
                        $files_listing[] = $file['filename'];
                    }
                }
            }
            if ($gz) {
                $gz->extractList($files_listing, _PS_TRANSLATIONS_DIR_.'../', '');
            }
        }
    }

    /**
     * @param      $iso
     * @param null $version
     * @param null $params
     * @param bool $install
     *
     * @return array|bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function downloadAndInstallLanguagePack($iso, $version = null, $params = null, $install = true)
    {
        if (!Validate::isLanguageIsoCode((string) $iso)) {
            return false;
        }

        if ($version == null) {
            $version = _PS_VERSION_;
        }

        $langPack = false;
        $errors = [];
        $file = _PS_TRANSLATIONS_DIR_.(string) $iso.'.gzip';

        $guzzle = new GuzzleHttp\Client(['http_errors' => false]);
        try {
            $langPackLink = (string) $guzzle->get('http://www.prestashop.com/download/lang_packs/get_language_pack.php?version='.$version.'&iso_lang='.Tools::strtolower((string) $iso))->getBody();
        } catch (Exception $e) {
            $langPackLink = null;
        }
        if (!$langPackLink) {
            $errors[] = Tools::displayError('Archive cannot be downloaded from prestashop.com.');
        } elseif (!$langPack = json_decode($langPackLink)) {
            $errors[] = Tools::displayError('Error occurred when language was checked according to your Prestashop version.');
        } elseif (empty($langPack->error) && ($content = (string) $guzzle->get('http://translations.prestashop.com/download/lang_packs/gzip/'.$langPack->version.'/'.Tools::strtolower($langPack->iso_code.'.gzip'))->getBody())) {
            if (!@file_put_contents($file, $content)) {
                if (is_writable(dirname($file))) {
                    @unlink($file);
                    @file_put_contents($file, $content);
                } elseif (!is_writable($file)) {
                    $errors[] = Tools::displayError('Server does not have permissions for writing.').' ('.$file.')';
                }
            }
        }

        if (!file_exists($file)) {
            $errors[] = Tools::displayError('No language pack is available for your version.');
        } elseif ($install) {
            require_once(_PS_TOOL_DIR_.'tar/Archive_Tar.php');
            $gz = new Archive_Tar($file, true);
            $filesList = AdminTranslationsController::filterTranslationFiles(Language::getLanguagePackListContent((string) $iso, $gz));
            $filesPaths = AdminTranslationsController::filesListToPaths($filesList);

            $i = 0;
            $tmpArray = [];

            foreach ($filesPaths as $filesPath) {
                $path = dirname($filesPath);
                if (is_dir(_PS_TRANSLATIONS_DIR_.'../'.$path) && !is_writable(_PS_TRANSLATIONS_DIR_.'../'.$path) && !in_array($path, $tmpArray)) {
                    $errors[] = (!$i++ ? Tools::displayError('The archive cannot be extracted.').' ' : '').Tools::displayError('The server does not have permissions for writing.').' '.sprintf(Tools::displayError('Please check rights for %s'), $path);
                    $tmpArray[] = $path;
                }
            }

            if (defined('_PS_HOST_MODE_')) {
                $mailsFiles = [];
                $otherFiles = [];

                foreach ($filesList as $key => $data) {
                    if (substr($data['filename'], 0, 5) == 'mails') {
                        $mailsFiles[] = $data;
                    } else {
                        $otherFiles[] = $data;
                    }
                }

                $filesList = $otherFiles;
            }

            if (!$gz->extractList(AdminTranslationsController::filesListToPaths($filesList), _PS_TRANSLATIONS_DIR_.'../')) {
                $errors[] = sprintf(Tools::displayError('Cannot decompress the translation file for the following language: %s'), (string) $iso);
            }

            // Clear smarty modules cache
            Tools::clearCache();

            if (!Language::checkAndAddLanguage((string) $iso, $langPack, false, $params)) {
                $errors[] = sprintf(Tools::displayError('An error occurred while creating the language: %s'), (string) $iso);
            } else {
                // Reset cache
                Language::loadLanguages();
                AdminTranslationsController::checkAndAddMailsFiles((string) $iso, $filesList);
                AdminTranslationsController::addNewTabs((string) $iso, $filesList);
            }
        }

        return count($errors) ? $errors : true;
    }

    public static function getLanguagePackListContent($iso, $tar)
    {
        $key = 'Language::getLanguagePackListContent_'.$iso;
        if (!Cache::isStored($key)) {
            if (!$tar instanceof Archive_Tar) {
                return false;
            }
            $result = $tar->listContent();
            Cache::store($key, $result);

            return $result;
        }

        return Cache::retrieve($key);
    }

    /**
     * @param      $isoCode
     * @param bool $langPack
     * @param bool $onlyAdd
     * @param null $paramsLang
     *
     * @return bool
     *
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkAndAddLanguage($isoCode, $langPack = false, $onlyAdd = false, $paramsLang = null)
    {
        if (Language::getIdByIso($isoCode)) {
            return true;
        }

        // Initialize the language
        $lang = new Language();
        $lang->iso_code = Tools::strtolower($isoCode);
        $lang->language_code = $isoCode; // Rewritten afterwards if the language code is available
        $lang->active = true;

        // If the language pack has not been provided, retrieve it from prestashop.com
        if (!$langPack) {
            try {
                $guzzle = new \GuzzleHttp\Client(['http_errors' => false]);
                $langPack = json_decode((string) $guzzle->get('http://www.prestashop.com/download/lang_packs/get_language_pack.php?version='._PS_VERSION_.'&iso_lang='.$isoCode)->getBody());
            } catch (Exception $e) {
                $langPack = null;
            }
        }

        // If a language pack has been found or provided, prefill the language object with the value
        if ($langPack) {
            foreach (get_object_vars($langPack) as $key => $value) {
                if ($key != 'iso_code' && isset(Language::$definition['fields'][$key])) {
                    $lang->$key = $value;
                }
            }
        }

        // Use the values given in parameters to override the data retrieved automatically
        if ($paramsLang !== null && is_array($paramsLang)) {
            foreach ($paramsLang as $key => $value) {
                if ($key != 'iso_code' && isset(Language::$definition['fields'][$key])) {
                    $lang->$key = $value;
                }
            }
        }

        if (!$lang->name && $lang->iso_code) {
            $lang->name = $lang->iso_code;
        }

        if (!$lang->validateFields() || !$lang->validateFieldsLang() || !$lang->add(true, false, $onlyAdd)) {
            return false;
        }

        if (isset($paramsLang['allow_accented_chars_url']) && in_array($paramsLang['allow_accented_chars_url'], ['1', 'true'])) {
            Configuration::updateGlobalValue('PS_ALLOW_ACCENTED_CHARS_URL', 1);
        }

        $guzzle = new \GuzzleHttp\Client(['http_errors' => false]);
        try {
            $flag = (string) $guzzle->get('http://www.prestashop.com/download/lang_packs/flags/jpeg/'.$isoCode.'.jpg')->getBody();
        } catch (Exception $e) {
            $flag = null;
        }
        if (is_object($flag)) {
            ddd($flag);
        }
        if ($flag != null && !preg_match('/<body>/', $flag)) {
            $file = fopen(_PS_ROOT_DIR_.'/img/l/'.(int) $lang->id.'.jpg', 'w');
            if ($file) {
                fwrite($file, $flag);
                fclose($file);
            } else {
                Language::_copyNoneFlag((int) $lang->id);
            }
        } else {
            Language::_copyNoneFlag((int) $lang->id);
        }

        $files_copy = [
            '/en.jpg',
            '/en-default-'.ImageType::getFormatedName('thickbox').'.jpg',
            '/en-default-'.ImageType::getFormatedName('home').'.jpg',
            '/en-default-'.ImageType::getFormatedName('large').'.jpg',
            '/en-default-'.ImageType::getFormatedName('medium').'.jpg',
            '/en-default-'.ImageType::getFormatedName('small').'.jpg',
            '/en-default-'.ImageType::getFormatedName('scene').'.jpg',
        ];

        foreach ([_PS_CAT_IMG_DIR_, _PS_MANU_IMG_DIR_, _PS_PROD_IMG_DIR_, _PS_SUPP_IMG_DIR_] as $to) {
            foreach ($files_copy as $file) {
                @copy(_PS_ROOT_DIR_.'/img/l'.$file, $to.str_replace('/en', '/'.$isoCode, $file));
            }
        }

        return true;
    }

    /**
     * Return id from iso code
     *
     * @param string $isoCode Iso code
     * @param bool   $noCache
     *
     * @return false|null|string
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIdByIso($isoCode, $noCache = false)
    {
        if (!Validate::isLanguageIsoCode($isoCode)) {
            die(Tools::displayError('Fatal error: ISO code is not correct').' '.Tools::safeOutput($isoCode));
        }

        $key = 'Language::getIdByIso_'.$isoCode;
        if ($noCache || !Cache::isStored($key)) {
            $id_lang = Db::getInstance()->getValue('SELECT `id_lang` FROM `'._DB_PREFIX_.'lang` WHERE `iso_code` = \''.pSQL(strtolower($isoCode)).'\'');

            Cache::store($key, $id_lang);

            return $id_lang;
        }

        return Cache::retrieve($key);
    }

    /**
     * @param bool $autodate
     * @param bool $nullValues
     * @param bool $onlyAdd
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function add($autodate = true, $nullValues = false, $onlyAdd = false)
    {
        if (!parent::add($autodate, $nullValues)) {
            return false;
        }

        if ($onlyAdd) {
            return true;
        }

        // create empty files if they not exists
        $this->_generateFiles();

        // @todo Since a lot of modules are not in right format with their primary keys name, just get true ...
        $this->loadUpdateSQL();

        return true;
    }

    /**
     * Generate translations files
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     *
     */
    protected function _generateFiles($newIso = null)
    {
        $isoCode = $newIso ? $newIso : $this->iso_code;

        if (!file_exists(_PS_TRANSLATIONS_DIR_.$isoCode)) {
            if (@mkdir(_PS_TRANSLATIONS_DIR_.$isoCode)) {
                @chmod(_PS_TRANSLATIONS_DIR_.$isoCode, 0777);
            }
        }

        foreach ($this->translationsFilesAndVars as $file => $var) {
            $pathFile = _PS_TRANSLATIONS_DIR_.$isoCode.'/'.$file.'.php';
            if (!file_exists($pathFile)) {
                if ($file != 'tabs') {
                    @file_put_contents(
                        $pathFile, '<?php
	global $'.$var.';
	$'.$var.' = array();
?>'
                    );
                } else {
                    @file_put_contents(
                        $pathFile, '<?php
	$'.$var.' = array();
	return $'.$var.';
?>'
                    );
                }
            }

            @chmod($pathFile, 0777);
        }
    }

    /**
     * loadUpdateSQL will create default lang values when you create a new lang, based on default id lang
     *
     * @return bool true if succeed
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function loadUpdateSQL()
    {
        $tables = Db::getInstance()->executeS('SHOW TABLES LIKE \''.str_replace('_', '\\_', _DB_PREFIX_).'%\_lang\' ');
        $langTables = [];

        foreach ($tables as $table) {
            foreach ($table as $t) {
                if ($t != _DB_PREFIX_.'configuration_lang') {
                    $langTables[] = $t;
                }
            }
        }

        $return = true;

        $shops = Shop::getShopsCollection(false);
        foreach ($shops as $shop) {
            /** @var Shop $shop */
            $idLangDefault = Configuration::get('PS_LANG_DEFAULT', null, $shop->id_shop_group, $shop->id);

            foreach ($langTables as $name) {
                preg_match('#^'.preg_quote(_DB_PREFIX_).'(.+)_lang$#i', $name, $m);
                $identifier = 'id_'.$m[1];

                $fields = '';
                // We will check if the table contains a column "id_shop"
                // If yes, we will add "id_shop" as a WHERE condition in queries copying data from default language
                $shopFieldExists = $primaryKeyExists = false;
                $columns = Db::getInstance()->executeS('SHOW COLUMNS FROM `'.$name.'`');
                foreach ($columns as $column) {
                    $fields .= '`'.$column['Field'].'`, ';
                    if ($column['Field'] == 'id_shop') {
                        $shopFieldExists = true;
                    }
                    if ($column['Field'] == $identifier) {
                        $primaryKeyExists = true;
                    }
                }
                $fields = rtrim($fields, ', ');

                if (!$primaryKeyExists) {
                    continue;
                }

                $sql = 'INSERT IGNORE INTO `'.$name.'` ('.$fields.') (SELECT ';

                // For each column, copy data from default language
                reset($columns);
                foreach ($columns as $column) {
                    if ($identifier != $column['Field'] && $column['Field'] != 'id_lang') {
                        $sql .= '(
							SELECT `'.bqSQL($column['Field']).'`
							FROM `'.bqSQL($name).'` tl
							WHERE tl.`id_lang` = '.(int) $idLangDefault.'
							'.($shopFieldExists ? ' AND tl.`id_shop` = '.(int) $shop->id : '').'
							AND tl.`'.bqSQL($identifier).'` = `'.bqSQL(str_replace('_lang', '', $name)).'`.`'.bqSQL($identifier).'`
						),';
                    } else {
                        $sql .= '`'.bqSQL($column['Field']).'`,';
                    }
                }
                $sql = rtrim($sql, ', ');
                $sql .= ' FROM `'._DB_PREFIX_.'lang` CROSS JOIN `'.bqSQL(str_replace('_lang', '', $name)).'`)';
                $return &= Db::getInstance()->execute($sql);
            }
        }

        return $return;
    }

    /**
     * @param $id
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    protected static function _copyNoneFlag($id)
    {
        return copy(_PS_ROOT_DIR_.'/img/l/none.jpg', _PS_ROOT_DIR_.'/img/l/'.$id.'.jpg');
    }

    /**
     * @see     ObjectModel::getFields()
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function getFields()
    {
        $this->iso_code = strtolower($this->iso_code);
        if (empty($this->language_code)) {
            $this->language_code = $this->iso_code;
        }

        return parent::getFields();
    }

    /**
     * Move translations files after editing language iso code
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function moveToIso($newIso)
    {
        if ($newIso == $this->iso_code) {
            return true;
        }

        if (file_exists(_PS_TRANSLATIONS_DIR_.$this->iso_code)) {
            rename(_PS_TRANSLATIONS_DIR_.$this->iso_code, _PS_TRANSLATIONS_DIR_.$newIso);
        }

        if (file_exists(_PS_MAIL_DIR_.$this->iso_code)) {
            rename(_PS_MAIL_DIR_.$this->iso_code, _PS_MAIL_DIR_.$newIso);
        }

        $modulesList = Module::getModulesDirOnDisk();
        foreach ($modulesList as $moduleDir) {
            if (file_exists(_PS_MODULE_DIR_.$moduleDir.'/mails/'.$this->iso_code)) {
                rename(_PS_MODULE_DIR_.$moduleDir.'/mails/'.$this->iso_code, _PS_MODULE_DIR_.$moduleDir.'/mails/'.$newIso);
            }

            if (file_exists(_PS_MODULE_DIR_.$moduleDir.'/'.$this->iso_code.'.php')) {
                rename(_PS_MODULE_DIR_.$moduleDir.'/'.$this->iso_code.'.php', _PS_MODULE_DIR_.$moduleDir.'/'.$newIso.'.php');
            }
        }

        foreach (Theme::getThemes() as $theme) {
            /** @var Theme $theme */
            $themeDir = $theme->directory;
            if (file_exists(_PS_ALL_THEMES_DIR_.$themeDir.'/lang/'.$this->iso_code.'.php')) {
                rename(_PS_ALL_THEMES_DIR_.$themeDir.'/lang/'.$this->iso_code.'.php', _PS_ALL_THEMES_DIR_.$themeDir.'/lang/'.$newIso.'.php');
            }

            if (file_exists(_PS_ALL_THEMES_DIR_.$themeDir.'/mails/'.$this->iso_code)) {
                rename(_PS_ALL_THEMES_DIR_.$themeDir.'/mails/'.$this->iso_code, _PS_ALL_THEMES_DIR_.$themeDir.'/mails/'.$newIso);
            }

            foreach ($modulesList as $module) {
                if (file_exists(_PS_ALL_THEMES_DIR_.$themeDir.'/modules/'.$module.'/'.$this->iso_code.'.php')) {
                    rename(_PS_ALL_THEMES_DIR_.$themeDir.'/modules/'.$module.'/'.$this->iso_code.'.php', _PS_ALL_THEMES_DIR_.$themeDir.'/modules/'.$module.'/'.$newIso.'.php');
                }
            }
        }
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function checkFiles()
    {
        return Language::checkFilesWithIsoCode($this->iso_code);
    }

    /**
     * This functions checks if every files exists for the language $iso_code.
     * Concerned files are those located in translations/$iso_code/
     * and translations/mails/$iso_code .
     *
     * @param mixed $isoCode
     *
     * @return bool true if all files exists
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function checkFilesWithIsoCode($isoCode)
    {
        if (isset(self::$_checkedLangs[$isoCode]) && self::$_checkedLangs[$isoCode]) {
            return true;
        }

        foreach (array_keys(Language::getFilesList($isoCode, _THEME_NAME_, false, false, false, true)) as $key) {
            if (!file_exists($key)) {
                return false;
            }
        }
        self::$_checkedLangs[$isoCode] = true;

        return true;
    }

    /**
     * @param      $isoFrom
     * @param      $themeFrom
     * @param bool $isoTo
     * @param bool $themeTo
     * @param bool $select
     * @param bool $check
     * @param bool $modules
     *
     * @return array
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getFilesList($isoFrom, $themeFrom, $isoTo = false, $themeTo = false, $select = false, $check = false, $modules = false)
    {
        if (empty($isoFrom)) {
            die(Tools::displayError());
        }

        $copy = ($isoTo && $themeTo) ? true : false;

        $lPathFrom = _PS_TRANSLATIONS_DIR_.(string) $isoFrom.'/';
        $tPathFrom = _PS_ROOT_DIR_.'/themes/'.(string) $themeFrom.'/';
        $pPathFrom = _PS_ROOT_DIR_.'/themes/'.(string) $themeFrom.'/pdf/';
        $mPathFrom = _PS_MAIL_DIR_.(string) $isoFrom.'/';

        if ($copy) {
            $lPathTo = _PS_TRANSLATIONS_DIR_.(string) $isoTo.'/';
            $tPathTo = _PS_ROOT_DIR_.'/themes/'.(string) $themeTo.'/';
            $pPathTo = _PS_ROOT_DIR_.'/themes/'.(string) $themeTo.'/pdf/';
            $mPathTo = _PS_MAIL_DIR_.(string) $isoTo.'/';
        }

        $lFiles = ['admin.php', 'errors.php', 'fields.php', 'pdf.php', 'tabs.php'];

        // Added natives mails files
        $mFiles = [
            'account.html', 'account.txt',
            'backoffice_order.html', 'backoffice_order.txt',
            'bankwire.html', 'bankwire.txt',
            'cheque.html', 'cheque.txt',
            'contact.html', 'contact.txt',
            'contact_form.html', 'contact_form.txt',
            'credit_slip.html', 'credit_slip.txt',
            'download_product.html', 'download_product.txt',
            'employee_password.html', 'employee_password.txt',
            'forward_msg.html', 'forward_msg.txt',
            'guest_to_customer.html', 'guest_to_customer.txt',
            'in_transit.html', 'in_transit.txt',
            'log_alert.html', 'log_alert.txt',
            'newsletter.html', 'newsletter.txt',
            'order_canceled.html', 'order_canceled.txt',
            'order_conf.html', 'order_conf.txt',
            'order_customer_comment.html', 'order_customer_comment.txt',
            'order_merchant_comment.html', 'order_merchant_comment.txt',
            'order_return_state.html', 'order_return_state.txt',
            'outofstock.html', 'outofstock.txt',
            'password.html', 'password.txt',
            'password_query.html', 'password_query.txt',
            'payment.html', 'payment.txt',
            'payment_error.html', 'payment_error.txt',
            'preparation.html', 'preparation.txt',
            'refund.html', 'refund.txt',
            'reply_msg.html', 'reply_msg.txt',
            'shipped.html', 'shipped.txt',
            'test.html', 'test.txt',
            'voucher.html', 'voucher.txt',
            'voucher_new.html', 'voucher_new.txt',
            'order_changed.html', 'order_changed.txt',
        ];

        $number = -1;

        $files = [];
        $filesTr = [];
        $filesTheme = [];
        $filesMail = [];
        $filesModules = [];

        // When a copy is made from a theme in specific language
        // to an other theme for the same language,
        // it's avoid to copy Translations, Mails files
        // and modules files which are not override by theme.
        if (!$copy || $isoFrom != $isoTo) {
            // Translations files
            if (!$check || ($check && (string) $isoFrom != 'en')) {
                foreach ($lFiles as $file) {
                    $filesTr[$lPathFrom.$file] = ($copy ? $lPathTo.$file : ++$number);
                }
            }
            if ($select == 'tr') {
                return $filesTr;
            }
            $files = array_merge($files, $filesTr);

            // Mail files
            if (!$check || ($check && (string) $isoFrom != 'en')) {
                $filesMail[$mPathFrom.'lang.php'] = ($copy ? $mPathTo.'lang.php' : ++$number);
            }
            foreach ($mFiles as $file) {
                $filesMail[$mPathFrom.$file] = ($copy ? $mPathTo.$file : ++$number);
            }
            if ($select == 'mail') {
                return $filesMail;
            }
            $files = array_merge($files, $filesMail);

            // Modules
            if ($modules) {
                $modList = Module::getModulesDirOnDisk();
                foreach ($modList as $mod) {
                    $modDir = _PS_MODULE_DIR_.$mod;
                    // Lang file
                    if (file_exists($modDir.'/translations/'.(string) $isoFrom.'.php')) {
                        $filesModules[$modDir.'/translations/'.(string) $isoFrom.'.php'] = ($copy ? $modDir.'/translations/'.(string) $isoTo.'.php' : ++$number);
                    } elseif (file_exists($modDir.'/'.(string) $isoFrom.'.php')) {
                        $filesModules[$modDir.'/'.(string) $isoFrom.'.php'] = ($copy ? $modDir.'/'.(string) $isoTo.'.php' : ++$number);
                    }
                    // Mails files
                    $modMailDirFrom = $modDir.'/mails/'.(string) $isoFrom;
                    $modMailDirTo = $modDir.'/mails/'.(string) $isoTo;
                    if (file_exists($modMailDirFrom)) {
                        $dirFiles = scandir($modMailDirFrom);
                        foreach ($dirFiles as $file) {
                            if (file_exists($modMailDirFrom.'/'.$file) && $file != '.' && $file != '..' && $file != '.svn') {
                                $filesModules[$modMailDirFrom.'/'.$file] = ($copy ? $modMailDirTo.'/'.$file : ++$number);
                            }
                        }
                    }
                }
                if ($select == 'modules') {
                    return $filesModules;
                }
                $files = array_merge($files, $filesModules);
            }
        } elseif ($select == 'mail' || $select == 'tr') {
            return $files;
        }

        // Theme files
        if (!$check || ($check && (string) $isoFrom != 'en')) {
            $filesTheme[$tPathFrom.'lang/'.(string) $isoFrom.'.php'] = ($copy ? $tPathTo.'lang/'.(string) $isoTo.'.php' : ++$number);

            // Override for pdf files in the theme
            if (file_exists($pPathFrom.'lang/'.(string) $isoFrom.'.php')) {
                $filesTheme[$pPathFrom.'lang/'.(string) $isoFrom.'.php'] = ($copy ? $pPathTo.'lang/'.(string) $isoTo.'.php' : ++$number);
            }

            $moduleThemeFiles = (file_exists($tPathFrom.'modules/') ? scandir($tPathFrom.'modules/') : []);
            foreach ($moduleThemeFiles as $module) {
                if ($module !== '.' && $module != '..' && $module !== '.svn' && file_exists($tPathFrom.'modules/'.$module.'/translations/'.(string) $isoFrom.'.php')) {
                    $filesTheme[$tPathFrom.'modules/'.$module.'/translations/'.(string) $isoFrom.'.php'] = ($copy ? $tPathTo.'modules/'.$module.'/translations/'.(string) $isoTo.'.php' : ++$number);
                }
            }
        }
        if ($select == 'theme') {
            return $filesTheme;
        }
        $files = array_merge($files, $filesTheme);

        // Return
        return $files;
    }

    /**
     * @param array $selection
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function deleteSelection($selection)
    {
        if (!is_array($selection)) {
            die(Tools::displayError());
        }

        $result = true;
        foreach ($selection as $id) {
            $language = new Language($id);
            $result = $result && $language->delete();
        }

        return $result;
    }

    /**
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public function delete()
    {
        if (!$this->hasMultishopEntries() || Shop::getContext() == Shop::CONTEXT_ALL) {
            if (empty($this->iso_code)) {
                $this->iso_code = Language::getIsoById($this->id);
            }

            // Database translations deletion
            $result = Db::getInstance()->executeS('SHOW TABLES FROM `'._DB_NAME_.'`');
            foreach ($result as $row) {
                if (isset($row['Tables_in_'._DB_NAME_]) && !empty($row['Tables_in_'._DB_NAME_]) && preg_match('/'.preg_quote(_DB_PREFIX_).'_lang/', $row['Tables_in_'._DB_NAME_])) {
                    if (!Db::getInstance()->execute('DELETE FROM `'.$row['Tables_in_'._DB_NAME_].'` WHERE `id_lang` = '.(int) $this->id)) {
                        return false;
                    }
                }
            }

            // Delete tags
            Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'tag WHERE id_lang = '.(int) $this->id);

            // Delete search words
            Db::getInstance()->execute('DELETE FROM '._DB_PREFIX_.'search_word WHERE id_lang = '.(int) $this->id);

            // Files deletion
            foreach (Language::getFilesList($this->iso_code, _THEME_NAME_, false, false, false, true, true) as $key => $file) {
                if (file_exists($key)) {
                    unlink($key);
                }
            }

            $modList = scandir(_PS_MODULE_DIR_);
            foreach ($modList as $mod) {
                Language::recurseDeleteDir(_PS_MODULE_DIR_.$mod.'/mails/'.$this->iso_code);
                $files = @scandir(_PS_MODULE_DIR_.$mod.'/mails/');
                if (count($files) <= 2) {
                    Language::recurseDeleteDir(_PS_MODULE_DIR_.$mod.'/mails/');
                }

                if (file_exists(_PS_MODULE_DIR_.$mod.'/'.$this->iso_code.'.php')) {
                    unlink(_PS_MODULE_DIR_.$mod.'/'.$this->iso_code.'.php');
                    $files = @scandir(_PS_MODULE_DIR_.$mod);
                    if (count($files) <= 2) {
                        Language::recurseDeleteDir(_PS_MODULE_DIR_.$mod);
                    }
                }
            }

            if (file_exists(_PS_MAIL_DIR_.$this->iso_code)) {
                Language::recurseDeleteDir(_PS_MAIL_DIR_.$this->iso_code);
            }
            if (file_exists(_PS_TRANSLATIONS_DIR_.$this->iso_code)) {
                Language::recurseDeleteDir(_PS_TRANSLATIONS_DIR_.$this->iso_code);
            }

            $images = [
                '.jpg',
                '-default-'.ImageType::getFormatedName('thickbox').'.jpg',
                '-default-'.ImageType::getFormatedName('home').'.jpg',
                '-default-'.ImageType::getFormatedName('large').'.jpg',
                '-default-'.ImageType::getFormatedName('medium').'.jpg',
                '-default-'.ImageType::getFormatedName('small').'.jpg',
            ];
            $imagesDirectories = [_PS_CAT_IMG_DIR_, _PS_MANU_IMG_DIR_, _PS_PROD_IMG_DIR_, _PS_SUPP_IMG_DIR_];
            foreach ($imagesDirectories as $imageDirectory) {
                foreach ($images as $image) {
                    if (file_exists($imageDirectory.$this->iso_code.$image)) {
                        unlink($imageDirectory.$this->iso_code.$image);
                    }
                    if (file_exists(_PS_ROOT_DIR_.'/img/l/'.$this->id.'.jpg')) {
                        unlink(_PS_ROOT_DIR_.'/img/l/'.$this->id.'.jpg');
                    }
                }
            }
        }

        if (!parent::delete()) {
            return false;
        }

        return true;
    }

    /**
     * Return iso code from id
     *
     * @param int $idLang Language ID
     *
     * @return string Iso code
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function getIsoById($idLang)
    {
        if (isset(self::$_LANGUAGES[(int) $idLang]['iso_code'])) {
            return self::$_LANGUAGES[(int) $idLang]['iso_code'];
        }

        return false;
    }

    /**
     * @param $dir
     *
     * @return bool
     *
     * @since   1.0.0
     * @version 1.0.0 Initial version
     */
    public static function recurseDeleteDir($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        if ($handle = @opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    if (is_dir($dir.'/'.$file)) {
                        Language::recurseDeleteDir($dir.'/'.$file);
                    } elseif (file_exists($dir.'/'.$file)) {
                        @unlink($dir.'/'.$file);
                    }
                }
            }
            closedir($handle);
        }
        if (is_writable($dir)) {
            rmdir($dir);
        }
    }

    /**
     * Return an array of theme
     *
     * @return array([theme dir] => array('name' => [theme name]))
     * @deprecated 1.0.0
     */
    protected function _getThemesList()
    {
        Tools::displayAsDeprecated();

        static $themes = [];

        if (empty($themes)) {
            $installedThemes = Theme::getThemes();
            foreach ($installedThemes as $theme) {
                /** @var Theme $theme */
                $themes[$theme->directory] = ['name' => $theme->name];
            }
        }

        return $themes;
    }
}
