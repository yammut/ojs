<?php

/**
 * @file plugins/importexport/native/filter/ArticleNativeXmlFilter.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2000-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ArticleNativeXmlFilter
 * @ingroup plugins_importexport_native
 *
 * @brief Class that converts a Article to a Native XML document.
 */

import('lib.pkp.plugins.importexport.native.filter.SubmissionNativeXmlFilter');

class ArticleNativeXmlFilter extends SubmissionNativeXmlFilter {
	/**
	 * Constructor
	 * @param $filterGroup FilterGroup
	 */
	function __construct($filterGroup) {
		parent::__construct($filterGroup);
	}


	//
	// Implement template methods from PersistableFilter
	//
	/**
	 * @copydoc PersistableFilter::getClassName()
	 */
	function getClassName() {
		return 'plugins.importexport.native.filter.ArticleNativeXmlFilter';
	}


	//
	// Implement abstract methods from SubmissionNativeXmlFilter
	//
	/**
	 * Get the representation export filter group name
	 * @return string
	 */
	function getRepresentationExportFilterGroupName() {
		return 'article-galley=>native-xml';
	}

	//
	// Submission conversion functions
	//
	/**
	 * Create and return a submission node.
	 * @param $doc DOMDocument
	 * @param $submission Submission
	 * @return DOMElement
	 */
	function createSubmissionNode($doc, $submission) {
		$deployment = $this->getDeployment();
		$submissionNode = parent::createSubmissionNode($doc, $submission);

		// Add the series, if one is designated.
		if ($sectionId = $submission->getSectionId()) {
			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$section = $sectionDao->getById($sectionId, $submission->getContextId());
			assert(isset($section));
			$submissionNode->setAttribute('section_ref', $section->getLocalizedAbbrev());
		}

		$publication = $submission->getCurrentPublication();
		$isPublished = $publication->getData('status') === STATUS_PUBLISHED;
		$isPublished ? $submissionNode->setAttribute('seq', (int) $publication->getData('seq')) : $submissionNode->setAttribute('seq', '0');
		$isPublished ? $submissionNode->setAttribute('access_status', $publication->getData('accessStatus')) : $submissionNode->setAttribute('access_status', '0');
		// if this is a published submission and not part/subelement of an issue element
		// add issue identification element
		if ($isPublished && !$deployment->getIssue()) {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issue = $issueDao->getById($publication->getData('issueId'));
			import('plugins.importexport.native.filter.NativeFilterHelper');
			$nativeFilterHelper = new NativeFilterHelper();
			$submissionNode->appendChild($nativeFilterHelper->createIssueIdentificationNode($this, $doc, $issue));
		}
		$pages = $submission->getPages();
		if (!empty($pages)) $submissionNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'pages', htmlspecialchars($pages, ENT_COMPAT, 'UTF-8')));
		// cover images
		import('plugins.importexport.native.filter.NativeFilterHelper');
		$nativeFilterHelper = new NativeFilterHelper();
		$coversNode = $nativeFilterHelper->createCoversNode($this, $doc, $submission);
		if ($coversNode) $submissionNode->appendChild($coversNode);
		return $submissionNode;
	}

}
