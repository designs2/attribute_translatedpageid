<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedUrl
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Oliver Hoff <oliver@hofff.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @author     Monique Hahnefeld <info@designs2.de>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\TranslatedPageId;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ManipulateWidgetEvent;
use MetaModels\Attribute\TranslatedReference;
use MetaModels\DcGeneral\Events\PageIdWizardHandler;

/**
 * Handle the translated url attribute.
 *
 * @package    MetaModels
 * @subpackage AttributeTranslatedUrl
 */
class TranslatedPageId extends TranslatedReference
{

    /**
     * {@inheritdoc}
     */
    public function getFilterUrlValue($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(parent::getAttributeSettingNames(), array(
            'no_external_link',
            'mandatory'
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function getValueTable()
    {
        return 'tl_metamodel_translatedpageid';
    }

    /**
     * {@inheritdoc}
     */
    public function valueToWidget($varValue)
    {
        return parent::valueToWidget($varValue);
    }

    /**
     * {@inheritdoc}
     */
    public function widgetToValue($varValue, $intId)
    {
        return parent::widgetToValue($varValue, $intId);
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($overrides = array())
    {
        $arrFieldDef = parent::getFieldDefinition($overrides);

        $arrFieldDef['inputType'] = 'text';
        if (!isset($arrFieldDef['eval']['tl_class'])) {
            $arrFieldDef['eval']['tl_class'] = '';
        }
        $arrFieldDef['eval']['tl_class'] .= ' wizard inline';

        if (!$this->get('trim_title')) {
            $arrFieldDef['eval']['size']      = 1;
            $arrFieldDef['eval']['multiple']  = false;
            $arrFieldDef['eval']['tl_class'] .= ' metamodelsattribute_pageid';
        }

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $dispatcher = $this->getMetaModel()->getServiceContainer()->getEventDispatcher();
        $dispatcher->addListener(
            ManipulateWidgetEvent::NAME,
            array(new PageIdWizardHandler($this->getMetaModel(), $this->getColName()), 'getWizard')
        );

        return $arrFieldDef;
    }

    /**
     * {@inheritdoc}
     */

    public function getFilterOptions($ids, $usedOnly, &$count = null)
    {
        // not supported
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function searchForInLanguages($pattern, $languages = array())
    {
        $pattern           = str_replace(array('*', '?'), array('%', '_'), $pattern);
        $languageCondition = '';

        $languages = (array) $languages;
        if ($languages) {
            $languageCondition = 'AND language IN (' . $this->parameterMask($languages) . ')';
        }

        $sql = sprintf(
            'SELECT DISTINCT item_id AS id FROM %1$s WHERE (title LIKE ? OR href LIKE ?) AND att_id = ?%2$s',
            $this->getValueTable(),
            $languageCondition
        );

        $params[] = $pattern;
        $params[] = $pattern;
        $params[] = $this->get('id');
        $params   = array_merge($params, $languages);

        $result = $this->getMetaModel()->getServiceContainer()->getDatabase()->prepare($sql)->execute($params);


        return $result->fetchEach('id');
    }

    
    /**
     * {@inheritdoc}
     */
    public function setTranslatedDataFor($values, $language)
    {
        $values = (array) $values;
        if (!$values) {
            return;
        }

        $this->unsetValueFor(array_keys($values), $language);
        $DB = $this->getMetaModel()->getServiceContainer()->getDatabase();
        $sql = sprintf(
            'INSERT INTO %1$s (att_id, item_id, language, tstamp, value_id) VALUES %2$s',
            $this->getValueTable(),
            rtrim(str_repeat('(?,?,?,?,?),', count($values)), ',')
        );

        $time   = time();
        $params = array();
        foreach ($values as $id => $value) {
            $params[] = $this->get('id');
            $params[] = $id;
            $params[] = $language;
            $params[] = $time;
            $params[] = $value['value'];
        }

        $DB->prepare($sql)->execute($params);

        //set name and alias 
        $AttributeIsTranslatedData = function($attribute){
               return in_array('setTranslatedDataFor',get_class_methods($this->getMetaModel()->getAttribute($attribute)));
        };
        
        if(!$AttributeIsTranslatedData('name') || !$AttributeIsTranslatedData('alias')){
            echo('You have to use translated attributes for name or alias if you use the attribute type of "attribute_translatedpageid"');
            exit;
        }elseif(Null === $this->getMetaModel()->getAttribute('name') || Null === $this->getMetaModel()->getAttribute('alias')){
            echo('You need the translated text attributes "name" and "alias" in your MetaModel:'.$strTable);
            exit;
        }else{
                 
            // Nur type Text für Name und Alias verwenden. Alias funktioniert (noch) nicht.
            $pageModel                      = \PageModel::findById($value['value']);
            $translatedTextValueTable       = 'tl_metamodel_translatedtext';
            $currentId                      = array_keys($values)[0]; //$this->get('id');       
            $nameAttrId                     = $this->getMetaModel()->getAttribute('name')->get('id');
            $aliasAttrId                    = $this->getMetaModel()->getAttribute('alias')->get('id');
            //to unset it find with item_id & attr_id & langcode -> es darf nur eins geben!
            //set new Items
            /*
              `id` int(10) unsigned NOT NULL auto_increment,
              `tstamp` int(10) unsigned NOT NULL default '0',
              `att_id` int(10) unsigned NOT NULL default '0',
              `item_id` int(10) unsigned NOT NULL default '0',
              `langcode` varchar(5) NOT NULL default '',
              `value` varchar(255) NOT NULL default '',
            */
            // Name
            $sqlSETName     = "INSERT INTO ".$translatedTextValueTable." (att_id, item_id, langcode, tstamp, value) VALUES (?,?,?,?,?) ";
            $DB->prepare($sqlSETName)->execute($nameAttrId,$currentId,$language,$time,$pageModel->title);
            // Alias
            $sqlSETAlias    = "INSERT INTO ".$translatedTextValueTable." (att_id, item_id, langcode, tstamp, value) VALUES (?,?,?,?,?) ";
            $DB->prepare($sqlSETAlias)->execute($aliasAttrId,$currentId,$language,$time,$pageModel->alias);

        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTranslatedDataFor($ids, $language)
    {
        $ids = (array) $ids;

        if (!$ids) {
            return array();
        }

        $sql = sprintf(
            'SELECT item_id AS id,value_id
            FROM %1$s
            WHERE att_id=?
            AND language=?
            AND item_id IN (%2$s)',
            $this->getValueTable(),
            $this->parameterMask($ids)
        );

        $params[] = $this->get('id');
        $params[] = $language;
        $params   = array_merge($params, $ids);

        $result = $this->getMetaModel()->getServiceContainer()->getDatabase()->prepare($sql)->execute($params);
        $values = array();
        while ($result->next()) {
            $values[$result->id] = array('value' => $result->value_id);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function unsetValueFor($ids, $language)
    {
        
        $ids = (array) $ids;

        if (!$ids) {
            return;
        }

        $sql = sprintf(
            'DELETE FROM %1$s
            WHERE att_id=?
            AND language=?
            AND item_id IN (%2$s)',
            $this->getValueTable(),
            $this->parameterMask($ids)
        );

        $params[] = $this->get('id');
        $params[] = $language;
        $params   = array_merge($params, $ids);

        $DB = $this->getMetaModel()->getServiceContainer()->getDatabase();
        $DB->prepare($sql)->execute($params);

        $translatedTextValueTable       = 'tl_metamodel_translatedtext';
        $currentId                      = $ids[0];
        $nameAttrId                     = $this->getMetaModel()->getAttribute('name')->get('id');
        $aliasAttrId                    = $this->getMetaModel()->getAttribute('alias')->get('id');
        //remove old value
        $sqlUNSETName   = "DELETE FROM ".$translatedTextValueTable."
        WHERE att_id=?
        AND langcode=?
        AND item_id=?";
        $DB->prepare($sqlUNSETName)->execute($nameAttrId,$language,$currentId);
        $sqlUNSETAlias  = "DELETE FROM ".$translatedTextValueTable."
        WHERE att_id=?
        AND langcode=?
        AND item_id=?";
        $DB->prepare($sqlUNSETAlias)->execute($aliasAttrId,$language,$currentId);
     
    }
}
