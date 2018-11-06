<?php

namespace SectionExtractor {
	class Hooks
	{
		public static function OnParserFirstCallInit( Parser &$parser ) {
			$parser->setFunctionHook( "SectionExtractor", Hooks::GetSectionTitles, Parser::SFH_OBJECT_ARGS );
		}

		public static function GetSectionTitles()
		{
			return [""];
		}
	}

	protected static function perform_while( Parser &$parser, $frame, $args, $dowhile = false ) {
		// #(do)while: | condition | code
		$rawCond = isset( $args[1] ) ? $args[1] : ''; // unexpanded condition
		$rawCode = isset( $args[2] ) ? $args[2] : ''; // unexpanded loop code
		if(
			$dowhile === false
			&& trim( $frame->expand( $rawCond ) ) === ''
		) {
			// while, but condition not fullfilled from the start
			return '';
		}
		$output = '';
		do {
			// limit check:
			if( ! self::incrCounter( $parser ) ) {
				return self::msgLoopsLimit( $output );
			}
			$output .= trim( $frame->expand( $rawCode ) );
		} while( trim( $frame->expand( $rawCond ) ) );
		return $output;
	}
}
