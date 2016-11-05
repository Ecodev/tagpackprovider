<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2010 Francois Suter (Cobweb) <typo3@cobweb.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Base dataprovider service. All Data Provider services should inherit from this class
 *
 * @author		Francois Suter (Cobweb) <typo3@cobweb.ch>
 */
class tx_tagpackprovider extends tx_tesseract_providerbase
{
    /**
     * This method returns the type of data structure that the Data Provider can prepare
     *
     * @return	string	type of the provided data structure
     */
    public function getProvidedDataStructure()
    {
        return tx_tesseract::IDLIST_STRUCTURE_TYPE;
    }

    /**
     * This method indicates whether the Data Provider can create the type of data structure requested or not
     *
     * @param	string		$type: type of data structure
     * @return	bool		true if it can handle the requested type, false otherwise
     */
    public function providesDataStructure($type)
    {
        return tx_tesseract::IDLIST_STRUCTURE_TYPE;
    }

    /**
     * This method returns the type of data structure that the Data Provider can receive as input
     *
     * @return	string	type of used data structures
     */
    public function getAcceptedDataStructure()
    {
        return '';
    }

    /**
     * This method indicates whether the Data Provider can use as input the type of data structure requested or not
     *
     * @param	string		$type: type of data structure
     * @return	bool		true if it can use the requested type, false otherwise
     */
    public function acceptsDataStructure($type)
    {
        return false;
    }

    /**
     * Assembles the data structure and returns it
     *
     * @return array Standardized data structure
     */
    public function getDataStructure()
    {
        // Get the list of tables
        // The first one is considered the "main" one
        $tables = t3lib_div::trimExplode(',', $this->providerData['tables']);
        $mainTable = $tables[0];
        // If an empty structure should be returned, initialize one
        if ($this->hasEmptyOutputStructure) {
            $structure = $this->initEmptyDataStructure($mainTable, tx_tesseract::IDLIST_STRUCTURE_TYPE);

            // Otherwise, assemble the structure
        } else {
            $structure = [];
            $uidsPerTable = [];
            $tagsPerItem = [];

            // Assemble list of tags
            $manualTags = [];
            // Get manually selected tags
            if (!empty($this->providerData['tags'])) {
                $manualTags = t3lib_div::trimExplode(',', $this->providerData['tags']);
            }
            // Get tags from expressions
            $expressionTags = [];
            if (!empty($this->providerData['tag_expressions'])) {
                $expressionTags = $this->parseExpressionField($this->providerData['tag_expressions']);
            }
            // Assemble final tags array
            $tags = $manualTags;
            if (count($expressionTags) > 0) {
                // Expression tags override manual tags
                if ($this->providerData['tags_override']) {
                    $tags = $expressionTags;

                    // Expression tags and manual tags are merged
                } else {
                    $tags = array_merge($manualTags, $expressionTags);
                }
            }
            // Make sure each tag appears only once
            $finalTags = array_unique($tags);

            $where = '';
            // Assemble where clause based on selected tags, if any
            if (count($finalTags) > 0) {
                $where = 'uid_local IN (' . implode(',', $finalTags) . ') AND ';
            }

            // Add condition on tables
            if (!empty($this->providerData['tables'])) {
                $condition = '';
                foreach ($tables as $aTable) {
                    if (!empty($condition)) {
                        $condition .= ',';
                    }
                    $condition .= "'" . $aTable . "'";
                }
                $where .= 'tablenames IN (' . $condition . ') AND ';
            }
            // NOTE: not sure how tagpack uses/sets these fields
            // Anyway there's no TCA for table tx_tagpack_tags_relations_mm,
            // so the proper API cannot be used
            $where .= "hidden='0' AND deleted='0'";
            // Query the tags relations table
            // NOTE: results are sorted by table and by sorting
            // This respects the order in which tags were applied. Maybe this doesn't make sense after all. Could be reviewed at a later stage
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_local, uid_foreign, tablenames', 'tx_tagpack_tags_relations_mm', $where, '', 'tablenames ASC, sorting ASC');

            // Loop on the results and sort them by table
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if (!isset($uidsPerTable[$row['tablenames']])) {
                    $uidsPerTable[$row['tablenames']] = [];
                    $tagsPerItem[$row['tablenames']] = [];
                }
                if (!isset($tagsPerItem[$row['tablenames']][$row['uid_foreign']])) {
                    $tagsPerItem[$row['tablenames']][$row['uid_foreign']] = [];
                }
                $uidsPerTable[$row['tablenames']][$row['uid_foreign']] = $row['uid_foreign'];
                $tagsPerItem[$row['tablenames']][$row['uid_foreign']][] = $row['uid_local'];
            }
            // If the items should match all tags ("AND" logical operator chosen)
            // perform some post-process filtering, because such a condition
            // cannot be expressed simply in the SELECT query
            if ($this->providerData['logical_operator'] === 'AND') {
                foreach ($tagsPerItem as $table => $tableRows) {
                    foreach ($tableRows as $uid_foreign => $uid_locals) {
                        // Check if all chosen tags are matched by tags found per item
                        $rest = array_diff($finalTags, $uid_locals);
                        // At least one tag was not matched,
                        // remove item from list
                        if (count($rest) > 0) {
                            unset($uidsPerTable[$table][$uid_foreign]);
                        }
                    }
                }
            }

            // Assemble data structure parts
            $count = 0;
            $uniqueTable = $mainTable;
            $uidList = '';
            $uidListWithTable = '';
            if (count($uidsPerTable) > 0) {
                // Set unique table name, if indeed unique
                if (count($uidsPerTable) === 1) {
                    $uniqueTable = key($uidsPerTable);
                    $Uids = array_unique($uidsPerTable[$uniqueTable]);
                    $uidList = implode(',', $Uids);
                } else {
                    $uniqueTable = '';
                    $uidList = '';
                }
                // Loop on list of uid's per table and assemble lists of id's prepended with table names
                $prependedUids = [];
                $count = 0;
                foreach ($uidsPerTable as $aTable => $list) {
                    $Uids = array_unique($list);
                    foreach ($Uids as $id) {
                        $prependedUids[] = $aTable . '_' . $id;
                        ++$count;
                    }
                }
                $uidListWithTable = implode(',', $prependedUids);
            }

            // Assemble final structure
            $structure['uniqueTable'] = $uniqueTable;
            $structure['uidList'] = $uidList;
            $structure['uidListWithTable'] = $uidListWithTable;
            $structure['count'] = $count;
            $structure['totalCount'] = $count;
        }

        return $structure;
    }

    /**
     * This method is used to pass a data structure to the Data Provider
     *
     * @param 	array	$structure: standardised data structure
     */
    public function setDataStructure($structure)
    {
    }

    /**
     * This method is used to pass a Data Filter structure to the Data Provider
     *
     * @param	array	$filter: Data Filter structure
     */
    public function setDataFilter($filter)
    {
        // TODO: improve logging when we finally have a central Tesseract debugging workflow
        if (TYPO3_DLOG) {
            t3lib_div::devLog('Data filters are currently not supported!', 'tagpackprovider', 2);
        }
    }

    /**
     * This method returns a list of tables and fields available in the data structure,
     * complete with localized labels
     *
     * @param	string	$language: 2-letter iso code for language
     * @return	array	list of tables and fields
     */
    public function getTablesAndFields($language = '')
    {
        return [];
    }

    /**
     * This method relies on the expressions parser to get tags from expressions
     *
     * @param	string	$field: field to parse for expressions
     * @return	string	Comma-separated list of tag primary keys
     */
    protected function parseExpressionField($field)
    {
        $tags = [];
        // Parse the field
        $allLines = tx_tesseract_utilities::parseConfigurationField($field);
        // Interpret each line
        foreach ($allLines as $line) {
            try {
                $evaluatedExpression = tx_expressions_parser::evaluateExpression($line);

                // If we specifically asked to get all content, we just skip tags selection
                if ($evaluatedExpression === '\\\\all') {
                    continue;
                }
                // else we try to find tags
                elseif (!empty($evaluatedExpression)) {
                    if (is_array($evaluatedExpression)) {
                        $tagList = $evaluatedExpression;
                    } elseif (strpos($evaluatedExpression, ',') === false) {
                        $tagList = [$evaluatedExpression];
                    } else {
                        $tagList = t3lib_div::trimExplode(',', $evaluatedExpression, 1);
                    }
                    foreach ($tagList as $aTag) {
                        $tags[] = intval($aTag);
                    }
                }
            } catch (Exception $e) {
                // Do nothing if expression parsing failed
            }
        }

        return $tags;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpackprovider/class.tx_tagpackprovider.php']) {
    include_once $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/tagpackprovider/class.tx_tagpackprovider.php'];
}
