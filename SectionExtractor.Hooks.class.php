<?php

	class SectionExtractorHooks
	{
		// Borrowed from https://github.com/wikimedia/mediawiki-extensions-ParserFunctions/blob/cf1480cb9629514dd4400b1b83283ae6c83ff163/includes/ExtParserFunctions.php#L314
		public static function pageExists(string $titleText, Title $title)
		{
			global $wgContLang;
			$wgContLang->findVariantLink( $titletext, $title, true );
			if ( $title )
			{
					if ( $title->getNamespace() === NS_SPECIAL )
					{
						return SpecialPageFactory::exists( $title->getDBkey() ) ? true : false;
					}
					elseif ( $title->isExternal() )
					{
						return false;
					}
					else
					{
						$pdbk = $title->getPrefixedDBkey();
						$lc = LinkCache::singleton();
						$id = $lc->getGoodLinkID( $pdbk );
						if ( $id !== 0 )
						{
							return true;
						}
						elseif ( $lc->isBadLink( $pdbk ) )
						{
							return false;
						}
						$id = $title->getArticleID();

						if ( $title->exists() )
						{
							return true;
						}
					}
			}
			return false;
		}
		
		public static function ArgumentsToTitles($arguments)
		{
			$result[] = $arguments[0];
			for($i = 1; $i < count($arguments); $i++)
			{
				$result[] = $arguments[$i]->node->nodeValue;
			}
			return $result;
		}

		public static function FormatTitle($title, $headlines, $withNumber, $pagePrepend, $pageAppend, $titlePrepend, $titleAppend, $headlinePrepend, $headlineAppend)
		{
			global $wgServer, $wgScript;
			$baseUrl = $wgServer . $wgScript . '?title=';

			$output = $titlePrepend . $title . $titleAppend;
			$output .= $pagePrepend;
			foreach ($headlines as $key => $headline)
			{
				if (!is_numeric($key))
				{	
					continue;
				}
				$printedHeadline = $withNumber ? $headline["number"] . " " . $headline["line"] : $headline["line"];

				$output .=	$headlinePrepend .
								"[[" . $headline["fromtitle"] . '#' . $headline["anchor"] . "|". $printedHeadline . "]]" .
							$headlineAppend;
			}
			$output .= $pageAppend;
			return $output;
		}

		public static function GetSectionTitles($input, \PPFrame_DOM $frame, $arguments)
		{
			$requestedTitle		= $arguments[0];
			$withNumber			= $arguments[1] ? $arguments[1]->node->nodeValue==1	: false;
			$pagePrepend		= $arguments[2] ? $arguments[2]->node->nodeValue 	: "<div class='page'>";
			$pageAppend			= $arguments[3] ? $arguments[3]->node->nodeValue 	: "</div>";
			$titlePrepend		= $arguments[4] ? $arguments[4]->node->nodeValue 	: "<div class='title'>";
			$titleAppend		= $arguments[5] ? $arguments[5]->node->nodeValue 	: "</div>";
			$headlinePrepend	= $arguments[6] ? $arguments[6]->node->nodeValue 	: "<div class='headline'>";
			$headlineAppend		= $arguments[7] ? $arguments[7]->node->nodeValue 	: "</div>";

			$title = Title::newFromText( $requestedTitle );

			if (!$title || !SectionExtractorHooks::pageExists($requestedTitle, $title))
			{
				return $requestedTitle . " " . wfMessage("sectionextractor-notfound");
			}

			return static::FormatTitle(
				$title->getText(),
				static::GetSectionsByTitle($requestedTitle),
				$withNumber,
				$pagePrepend,
				$pageAppend,
				$titlePrepend,
				$titleAppend,
				$headlinePrepend,
				$headlineAppend,
				true
			);
		}

		public static function OnParserFirstCallInit( \Parser &$parser )
		{
			$parser->setFunctionHook( "SectionExtractor", "SectionExtractorHooks::GetSectionTitles", \Parser::SFH_OBJECT_ARGS);
		}

		public static function GetSectionsByTitle( string $title )
		{
			global $wgParser;
			$backupParser = $wgParser;
			$wgParser = new Parser();

			$apiRequest = new FauxRequest( array(
				'action' => 'parse',
				'page' => urldecode($title),
				'prop' => 'sections'
			) );
			
			$context = new DerivativeContext( new RequestContext() );
			$context->setRequest( $apiRequest );
			$api = new ApiMain( $context, true );
			$api->execute();
			$result = $api->getResult();

			$wgParser = $backupParser;

			return $result->getResultData()["parse"]["sections"];
		}
	}