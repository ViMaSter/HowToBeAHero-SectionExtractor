<?php

	class Hookks
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
		public static function GetSectionTitles($input, \PPFrame_DOM $frame, $arguments)
		{
			$requestedTitles = static::ArgumentsToTitles($arguments);

			$sections = [];
			foreach ($requestedTitles as $title)
			{
				$sections[] = static::GetSectionsByTitle($title);
			}

			return print_r($sections, true);
		}

		public static function OnParserFirstCallInit( \Parser &$parser )
		{
			$parser->setFunctionHook( "SectionExtractor", "Hookks::GetSectionTitles", \Parser::SFH_OBJECT_ARGS);
		}

		public static function GetSectionsByTitle( string $title )
		{
			$titleObj = Title::newFromText($title);
			$wikipage = WikiPage::factory($titleObj);

			if ($wikipage->getContent() == null)
			{
				return [];
			}

			$text = $wikipage->getContent()->getNativeData();

			$newParser = new Parser();
			$parserOutput = $newParser->parse($text,$titleObj,new ParserOptions());

			$output = [];
			foreach ($parserOutput->getSections() as $keySection=>$valueSection)
			{
				$output[] = $valueSection;
			}

			return $output;
		}
	}