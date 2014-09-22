<?php
/**
 * VersionNumber
 * 
 * Class representing a version number.
 * 
 * Copyright 2014 Saimiri Design.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @package			Phing
 * @copyright		Copyright (c) 2014 Saimiri Design (http://www.saimiri.fi/)
 * @author			Juha Auvinen <juha@saimiri.fi>
 * @license			http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @since				File available since Release 1.0.0
 */
class VersionNumber
{
	protected $buildSeparator;
	protected $custom;
	protected $preRelease;
	protected $versionNumberMap = array(
		0 => 'major',
		1 => 'minor',
		2 => 'patch',
		3 => 'build'
	);
	
	/**
	 * Constructor.
	 * 
	 * @param string $versionString Version string
	 * @param string $preRelease Pre-release string to use, .ie "alpha", "dev" etc.
	 */
	public function __construct( $versionString, $preRelease = null ) {
		$versionParts = explode( '.', $versionString );
		for ( $i = 0; $i < 4; $i++ ) {
			$paramName = $this->versionNumberMap[$i];
			if ( isset( $versionParts[$i] ) ) {
				$this->$paramName = $versionParts[$i];
			} else {
				$this->$paramName = 0;
			}
		}
		$this->preRelease = $preRelease;
		$this->buildSeparator = '.';
	}
	
	/**
	 * Sets the string that separates build number from rest of the version
	 * numbers.
	 * 
	 * @param string $separator Build separator to use
	 */
	public function setBuildSeparator( $separator ) {
		$this->buildSeparator = $separator;
	}
	
	/**
	 * Sets custom part to be used with formatted version string.
	 * 
	 * @see self::getFormattedString()
	 * @param string $custom Custom string. Whatever you want, baby.
	 */
	public function setCustom( $custom ) {
		$this->custom = $custom;
	}

	/**
	 * Returns version string defined by custom format.
	 * 
	 * $format may contain any of the following keywords, that are replaced with
	 * corresponding parts of the version: %major%, %minor%, %build%, %patch%,
	 * %prerelease%, %custom%
	 * 
	 * @param string $format Custom format
	 * @return string Formatted version string
	 */
	public function getFormattedString( $format ) {
		return str_replace(
			array( '%major%', '%minor%', '%patch%', '%bugfix%', '%revision', '%build%', '%prerelease%', '%custom%' ),
			array( $this->major, $this->minor, $this->patch, $this->patch, $this->patch, $this->build, $this->preRelease, $this->custom ),
			$format
			);
	}
	
	/**
	 * Gets parts of the version string specified by $from and $to. Values are:
	 * 0 or "major" = major version
	 * 1 or "minor" = minor version
	 * 2 or "patch" = patch version
	 * 3 or "build" = build number
	 * 
	 * @param integer $from First part to get
	 * @param integer $to Last part to get
	 * @return string Requested parts of the version string
	 */
	public function getPartialString( $from, $to ) {
		$fromKey = $this->seekVersionNumberMap( $from );
		$toKey = $this->seekVersionNumberMap( $to );
		
		return $this->buildString( $fromKey, $toKey, $this->preRelease, $this->buildSeparator );
	}
	
	/**
	 * Increments the specified part of version number. Accepts both string and
	 * integer representations of the version number part.
	 * 
	 * @param integer $part The part of the version number to increment.
	 */
	public function increment( $part = null ) {
		if ( $part !== null ) {
			if ( isset( $this->$part ) ) {
				$this->$part++;
			} else if ( isset( $this->versionNumberMap[$part] ) ) {
				$part = $this->versionNumberMap[$part];
				$this->$part++;
			} else {
				throw new Exception( '"' . $part . '" is not a recognized part of version number.' );
			}
		}
		
		// Always increment build number
		if ( $part != 'build' && $part != 3 ) {
			$this->build++;
		}
	}
	
	/**
	 * Converts VersionNumber to a normalized string in the format of
	 * major.minor.patch.build
	 * 
	 * @return string The version string in default format.
	 */
	public function toNormalizedString() {
		return $this->buildString( 0, 3, '', '.' );
	}
	
	/**
	 * Converts VersionNumber to string in the format of major.minor.patch?build
	 * 
	 * @return string The version string in default format.
	 */
	public function toString() {
		return $this->buildString( 0, 3, '', $this->buildSeparator );
	}

	/*****************************************************************************
	 * 
	 * --------------------------- PRIVATE METHODS -------------------------------
	 * 
	 ****************************************************************************/

	/**
	 * Builds version string.
	 * 
	 * @param integer $from          First part of version to use
	 * @param integer $to            Last part of version to use
	 * @param string $preRelease     Optional pre-release string
	 * @param string $buildSeparator String that separates build numbers
	 * @return string                Version number with specified parts
	 * @throws Exception
	 */
	protected function buildString( $from = 0, $to = 3, $preRelease = '', $buildSeparator = '.' ) {
		//$string = '';
		if ( $to > 3 || $to < 0 ) {
			throw new Exception( "Can't decipher build type $to" );
		}
		$end = $to > 2 ? 2 : $to;
		$parts = array();
		for ( $i = $from; $i <= $end; $i++ ) {
			$prop = $this->versionNumberMap[$i];
			$parts[] = $this->$prop;
		}
		
		$string = implode( '.', $parts );
		
		if ( $preRelease ) {
			$string .= '-' . $this->preRelease;
		}
		
		if ( $to == 3 ) {
			if ( $from < 3 ) {
				$string .= $buildSeparator;
			}
			$string .= $this->build;
		}
		return $string;
	}
	
	/**
	 * Finds a key in self::versionNumberMap that matches given string or integer.
	 * 
	 * @param  string|integer $value  Value to match
	 * @return integer                The key in self::versionNumberMap
	 * @throws Exception
	 */
	protected function seekVersionNumberMap( $value ) {
		if ( isset( $this->versionNumberMap[$value] ) ) {
			return $value;
		} else {
			$key = array_search( strtolower( $value ), $this->versionNumberMap );
			if ( $key !== false ) {
				return $key;
			}
		}
		throw new Exception( sprintf( '%s is not recognised part of version' ), $value );
	}
}