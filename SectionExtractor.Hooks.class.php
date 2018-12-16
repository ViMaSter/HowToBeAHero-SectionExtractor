<?php

	class SectionExtractorHooks
	{
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
			foreach ($headlines as $headline)
			{
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

			$titleObj = Title::newFromText($requestedTitle);
			if (!$titleObj)
			{
				return $requestedTitle . " cannot be found!";
			}

			return static::FormatTitle(
				$titleObj->getText(),
				static::GetSectionsByTitle($titleObj),
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

		public static function GetSectionsByTitle( \Title $titleObj )
		{
			$wikipage = WikiPage::factory($titleObj);

			if ($wikipage->getContent() == null)
			{
				return [];
			}

			$text = $wikipage->getContent()->getNativeData();

			$newParser = new Parser();
			$parserOutput = $newParser->parse($text,$titleObj,new ParserOptions());

			$output = [];
			$sections = $parserOutput->getSections();
			foreach ($sections as $keySection=>$valueSection)
			{
				$output[] = $valueSection;
			}

			return $output;
		}
	}