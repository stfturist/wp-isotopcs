<?php

/**
 * Isotop_Sniffs_ControlStructures_BlankLineAfterEndSniff.
 *
 * Verifies that new lines after end is only two.
 *
 * Credit
 * https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/blob/master/WordPress/Sniffs/WhiteSpace/ControlStructureSpacingSniff.php
 *
 * @author Fredrik Forsmo <fredrik.forsmo@isotop.se>
 */

class Isotop_Sniffs_ControlStructures_BlankLineAfterEndSniff implements PHP_CodeSniffer_Sniff {

	/**
	 * A list of tokenizers this sniff supports.
	 *
	 * @var array
	 */
	public $supportedTokenizers = ['PHP'];

	/**
	 * How many spaces should be between a T_CLOSURE and T_OPEN_PARENTHESIS.
	 *
	 * function[*]() {...}
	 *
	 * @since 0.7.0
	 *
	 * @var int
	 */
	public $spaces_before_closure_open_paren = 0;

	/**
	 * Returns an array of tokens this test wants to listen for.
	 *
	 * @return array
	 */
	public function register() {
		return [
			T_CLASS,
			T_IF,
			T_WHILE,
			T_FOREACH,
			T_FOR,
			T_SWITCH,
			T_DO,
			T_ELSE,
			T_ELSEIF,
			T_FUNCTION,
			T_CLOSURE,
			T_USE,
		];
	}

	/**
	 * Processes this test, when one of its tokens is encountered.
	 *
	 * @param  PHP_CodeSniffer_File $phpcsFile
	 * @param  int                  $stackPtr
	 */
	public function process( PHP_CodeSniffer_File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		if ( $tokens[( $stackPtr + 1 )]['code'] !== T_WHITESPACE
			&& ! ( $tokens[$stackPtr]['code'] === T_ELSE && $tokens[( $stackPtr + 1 )]['code'] === T_COLON )
			&& ! ( T_CLOSURE === $tokens[ $stackPtr ]['code'] && 0 === (int) $this->spaces_before_closure_open_paren )
		) {
			$error = 'Space after opening control structure is required';

			if ( isset( $phpcsFile->fixer ) === true ) {
				$fix = $phpcsFile->addFixableError( $error, $stackPtr, 'NoSpaceAfterStructureOpen' );

				if ( $fix === true ) {
					$phpcsFile->fixer->beginChangeset();
					$phpcsFile->fixer->addContent( $stackPtr, ' ' );
					$phpcsFile->fixer->endChangeset();
				}
			} else {
				$phpcsFile->addError( $error, $stackPtr, 'NoSpaceAfterStructureOpen' );
			}
		}

		if ( isset( $tokens[ $stackPtr ]['scope_closer'] ) === false ) {
			if ( T_USE === $tokens[ $stackPtr ]['code'] && 'closure' === $this->get_use_type( $stackPtr ) ) {
				$scopeOpener = $phpcsFile->findNext( T_OPEN_CURLY_BRACKET, $stackPtr + 1 );
				$scopeCloser = $tokens[ $scopeOpener ]['scope_closer'];
			} else {
				return;
			}
		} else {
			$scopeOpener = $tokens[ $stackPtr ]['scope_opener'];
			$scopeCloser = $tokens[ $stackPtr ]['scope_closer'];
		}

		$firstContent = $phpcsFile->findNext( T_WHITESPACE, ( $scopeOpener + 1 ), null, true );
		$currentToken = $tokens[$scopeOpener];
		$class_line = array_values( array_filter( array_filter( $tokens, function ( $token ) use( $currentToken ) {
			return $token['line'] === $currentToken['line'];
		} ), function ( $token ) {
			return $token['code'] === T_CLASS;
		} ) );

		if ( count( $class_line ) > 0 ) {
			$next_line = array_filter( $tokens, function ( $token ) use( $class_line ) {
				return $token['line'] === $class_line[0]['line'] + 1;
			} );

			if ( count( $next_line ) > 0 ) {
				$whitespace = array_filter( $next_line, function ( $token ) {
					return $token['code'] === T_WHITESPACE;
				} );

				$whitespace = count( $whitespace ) < count( $next_line );

				if ( $whitespace
					&& $tokens[$scopeOpener]['code'] === T_OPEN_CURLY_BRACKET
					&& ! isset( $tokens[$firstContent]['nested_parenthesis'] )
					&& $tokens[$firstContent]['level'] === 1
				) {
					$error = 'No blank line found at start of control structure';

					if ( isset( $phpcsFile->fixer ) === true ) {
						$fix = $phpcsFile->addFixableError( $error, $scopeOpener, 'NoBlankLineAfterStart' );

						if ( $fix === true ) {
							$phpcsFile->fixer->beginChangeset();

							for ( $i = ( $scopeOpener + 2 ); $i < $firstContent; $i++ ) {
								$phpcsFile->fixer->replaceToken( $i, '' );
							}

							$phpcsFile->fixer->addNewline( $scopeOpener );
							$phpcsFile->fixer->endChangeset();
						}
					} else {
						$phpcsFile->addError( $error, $scopeOpener, 'NoBlankLineAfterStart' );
					}
				}

				$next_line = array_values( $next_line );

				$next_next_line = array_filter( $tokens, function ( $token ) use ( $next_line ) {
					return $token['line'] === $next_line[0]['line'] + 1;
				} );

				if ( count( $next_next_line )  > 0 ) {
					$next_whitespace = array_filter( $next_next_line, function ( $token ) {
						return $token['code'] === T_WHITESPACE;
					} );

					$next_whitespace = count( $next_next_line ) === count( $next_whitespace );

					if ( $next_whitespace
						&& $tokens[$scopeOpener]['code'] === T_OPEN_CURLY_BRACKET
						&& ! isset( $tokens[$firstContent]['nested_parenthesis'] )
						&& $tokens[$firstContent]['level'] === 1
					) {
						$error = 'More then one blank line found at start of control structure';

						if ( isset( $phpcsFile->fixer ) === true ) {
							$fix = $phpcsFile->addFixableError( $error, $scopeOpener, 'MoreThenOneBlankLineAfterStart' );

							if ( $fix === true ) {
								$phpcsFile->fixer->beginChangeset();

								for ( $i = ( $scopeOpener + 2 ); $i < $firstContent; $i++ ) {
									$phpcsFile->fixer->replaceToken( $i, '' );
								}

								$phpcsFile->fixer->addNewline( $scopeOpener );
								$phpcsFile->fixer->endChangeset();
							}
						} else {
							$phpcsFile->addError( $error, $scopeOpener, 'MoreThenOneBlankLineAfterStart' );
						}
					}
				}
			}
		}

		$trailingContent = $phpcsFile->findNext( T_WHITESPACE, ( $scopeCloser + 1 ), null, true );

		if ( $tokens[$trailingContent]['code'] === T_ELSE ) {
			if ( $tokens[$stackPtr]['code'] === T_IF ) {
				// IF with ELSE.
				return;
			}
		}

		if ( $tokens[$trailingContent]['code'] === T_COMMENT ) {
			if ( $tokens[$trailingContent]['line'] === $tokens[$scopeCloser]['line'] ) {
				if ( substr( $tokens[$trailingContent]['content'], 0, 5 ) === '//end' ) {
					// There is an end comment, so we have to get the next piece
					// of content.
					$trailingContent = $phpcsFile->findNext( T_WHITESPACE, ( $trailingContent + 1 ), null, true );
				}
			}
		}

		if ( $tokens[$trailingContent]['code'] === T_BREAK ) {
			// If this BREAK is closing a CASE, we don't need the
			// blank line after this control structure.
			if ( isset( $tokens[$trailingContent]['scope_condition'] ) === true ) {
				$condition = $tokens[$trailingContent]['scope_condition'];

				if ( $tokens[$condition]['code'] === T_CASE || $tokens[$condition]['code'] === T_DEFAULT ) {
					return;
				}
			}
		}

		if ( $tokens[$trailingContent]['code'] === T_CLOSE_TAG ) {
			// At the end of the script or embedded code.
			return;
		}

		if ( $tokens[$trailingContent]['code'] === T_CLOSE_CURLY_BRACKET ) {
			// Another control structure's closing brace.

			if ( isset( $tokens[$trailingContent]['scope_condition'] ) === true ) {
				$owner = $tokens[$trailingContent]['scope_condition'];

				if ( $tokens[$owner]['code'] === T_FUNCTION ) {
					// The next content is the closing brace of a function
					// so normal function rules apply and we can ignore it.
					return;
				}
			}

			if ( $tokens[$scopeCloser]['code'] === $tokens[$scopeCloser + 2]['code'] ) {
				// TODO: Won't cover following case: "} echo 'OK';".
				$error = 'No blank line found after control structure. One blank line should exist.';

				if ( isset( $phpcsFile->fixer ) === true ) {
					$fix = $phpcsFile->addFixableError( $error, $scopeCloser, 'NoBlankLineAfterEnd' );

					if ( $fix === true ) {
						$phpcsFile->fixer->beginChangeset();

						for ( $i = ( $scopeCloser + 2 ); $i < $trailingContent; $i++ ) {
							$phpcsFile->fixer->replaceToken( $i, '' );
						}

						// TODO: Instead a separate error should be triggered when content comes right after closing brace.
						$phpcsFile->fixer->addNewlineBefore( $trailingContent );
						$phpcsFile->fixer->endChangeset();
					}
				} else {
					$phpcsFile->addError( $error, $scopeCloser, 'BlankLineAfterEnd' );
				}
			}

			if ( $tokens[$trailingContent]['line'] != ( $tokens[$scopeCloser]['line'] + 1 )
				&& $tokens[$trailingContent]['line'] != ( $tokens[$scopeCloser]['line'] + 2 )
			) {
				// TODO: Won't cover following case: "} echo 'OK';".
				$error = 'Two blank line found after control structure';

				if ( isset( $phpcsFile->fixer ) === true ) {
					$fix = $phpcsFile->addFixableError( $error, $scopeCloser, 'MoreThenOneBlankLineAfterEnd' );

					if ( $fix === true ) {
						$phpcsFile->fixer->beginChangeset();

						for ( $i = ( $scopeCloser + 2 ); $i < $trailingContent; $i++ ) {
							$phpcsFile->fixer->replaceToken( $i, '' );
						}

						// TODO: Instead a separate error should be triggered when content comes right after closing brace.
						$phpcsFile->fixer->addNewlineBefore( $trailingContent );
						$phpcsFile->fixer->endChangeset();
					}
				} else {
					$phpcsFile->addError( $error, $scopeCloser, 'BlankLineAfterEnd' );
				}
			}
		}
	}

}
