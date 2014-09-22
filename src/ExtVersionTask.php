<?php
if ( !defined( 'SMR_TEST_RUN' ) ) {
	include_once 'phing/Task.php';
}
require_once 'VersionNumber.php';

/**
 * ExtVersionTask
 * 
 * Gets and optionally increments version number. Can get and increment any part
 * of a four part version number. When incrementing always increments the build
 * number also.
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
 * @package			No package
 * @copyright		Copyright (c) 2014 Saimiri Design (http://www.saimiri.fi/)
 * @author			Juha Auvinen <juha@saimiri.fi>
 * @license			http://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @since				File available since Release 1.0.0
 */
class ExtVersionTask extends Task
{
	/**
	* A string that separates build number from rest of the version string.
	* @var string
	*/
	protected $buildSeparator;

	/**
	* Custom part to inject into the version string.
	* 
	* @var string
	*/
	protected $custom;

	/**
	* Default values for certain properties. Note that these are zero-indexed
	* because that is what VersionNumber expects.
	*
	* @see ExtVersionTask::setDefaults()
	* @var array
	*/
	protected $defaultValues = array(
		'buildSeparator' => '.',
		'first' => 0,
		'last' => 3,
		'releaseType' => 3,
		'readOnly' => false
	);

	/**
	* File containing the version number (to increment).
	* 
	* @var string
	*/
	protected $file;

	/**
	* Rows in $file, in case there are more than one.
	*
	* @see ExtVersionTask::parseFile()
	* 
	* @var array
	*/
	protected $fileRows = array();

	/**
	* First part to get from the version number.
	* 
	* @var integer
	*/
	protected $first;

	/**
	* Custom format to use with version number.
	* 
	* @var string 
	*/
	protected $format;

	/**
	* Last part to get from the version number.
	* 
	* @var integer
	*/
	protected $last;

	/**
	* Pre-release string to use with version number.
	* 
	* @var string 
	*/
	protected $preRelease;

	/**
	* Phing property to put the version number into.
	* 
	* @var string
	*/
	protected $property;

	/**
	* Sets the version number read-only, preventing it from incrementing.
	* 
	* @var boolean
	*/
	protected $readOnly;

	/**
	* Release type of this version. Affects which part gets incremented.
	* 
	* @var integer
	*/
	protected $releaseType;

	/**
	* Map for easier matching of strings and integers representing different
	* release types and parts of the version number. For end-user's conveniece
	* release types start from one, although in VersionNumber they are zero
	* indexed. That's why we need to subtract one from them after they are
	* verified.
	* 
	* @var array
	*/
	protected $releaseTypeMap = array(
		'major'			=> '1',
		'minor'			=> '2',
		'patch'			=> '3',
		'bugfix'		=> '3',
		'revision'	=> '3',
		'build'			=> '4'
	);

	/**
	* Token used to find the correct place to put version string when writing to
	* ExtVersionTask::file.
	* 
	* @var string
	*/
	protected $replaceToken = '%ExtVersionTaskReplaceToken%';

	/**
	* Version string from versionstring property, if no file is given.
	* 
	* @var string
	*/
	protected $versionString;

	/*****************************************************************************
	 * 
	 * --------------------------- PUBLIC METHODS -------------------------------
	 * 
	 ****************************************************************************/

	/**
	* Sets the separator to use between build number and rest of the version
	* string.
	* 
	* @param string $sep
	*/
	public function setBuildseparator( $sep ) {
		$this->buildSeparator = $sep;
	}

	/**
	* Sets the custom part of the version string.
	* 
	* @param string $string
	*/
	public function setCustom( $string ) {
		$this->custom = $string;
	}

	/**
	* Sets the property for file containing the version information
	* 
	* @param string $file
	*/
	public function setFile( $file ) {
		$this->file = $file;
	}

	/**
	* Verifies that the file property contains a valid file location
	* 
	* @throws BuildException
	*/
	public function checkFile() {
		if ( !is_readable( $this->file ) ) {
			throw new BuildException(
				'You must specify a valid file containing the version number',
				$this->location
			);
		}
	}

	/**
	* Sets a custom format for displaying the version.
	* 
	* @param string $format
	*/
	public function setFormat( $format ) {
		$this->format = $format;
	}

	/**
	* Sets the first part of the version string to be returned.
	* 
	* @param type $first
	*/
	public function setFirst( $first ) {
		$this->first = $first;
	}

	/**
	* Checks that the first part is a valid representation of a version part
	* (either integer 1-4 or string "major|minor|patch|build").
	* 
	* @throws BuildException
	*/
	public function checkFirst() {
		$this->first = $this->validateReleaseType( $this->first, 'First' );
	}

	/**
	* Sets the last part of the version string to be returned.
	* 
	* @param string $last
	*/
	public function setLast( $last ) {
		$this->last = $last;
	}

	/**
	* Checks that the last part is a valid representation of a version part
	* (either integer 1-4 or string "major|minor|patch|build").
	* 
	* @throws BuildException
	*/
	public function checkLast() {
		$this->last = $this->validateReleaseType( $this->last, 'Last' );
	}

	/**
	* Sets the pre-release part of the version string.
	* 
	* @param type $preRelease
	*/
	public function setPrerelease( $preRelease ) {
		$this->preRelease = $preRelease;
	}

	/**
	* Sets the part of the version number to inrement.
	* 
	* Can accept either an integer (1-4) or a string (major, minor, patch, build)
	* as a parameter.
	* 
	* @param type $releaseType
	*/
	public function setReleasetype( $releaseType ) {
		$this->releaseType = $releaseType;
	}

	/**
	* Checks that the release type is a valid representation.
	* 
	* @return type
	* @throws BuildException
	*/
	public function checkReleasetype() {
		$this->releaseType = $this->validateReleaseType( $this->releaseType, 'Releasetype' );
	}

	/**
	* Sets the name of the property to be set in the Phing XML file.
	* 
	* @param string $property
	*/
	public function setProperty( $property ) {
		$this->property = $property;
	}

	/**
	* Verifies that the property is set.
	* 
	* @throws BuildException
	*/
	public function checkProperty() {
		if ( $this->isNotSet( $this->property ) ) {
			throw new BuildException(
				'Property for publishing version number is not set',
				$this->location
			);
		}
	}

	/**
	* Sets the read-only property.
	* 
	* If set, version is not incremented when retrieved.
	* 
	* @param string $readOnly
	*/
	public function setReadOnly( $readOnly ) {
		$this->readOnly = $readOnly;
	}

	/**
	* Validates and normalizes the read-only property.
	* 
	* @return void
	*/
	public function checkReadOnly() {
		$s = strtolower( $this->readOnly );
		if ( $s == 'false' || $s == '0' || $s == 'no' || $s == 'njet' || $s == 'nein' ) {
			$this->readOnly = false;
		} else {
			$this->readOnly = true;
		}
	}

	/**
	* Sets the version string to be used if no file location is given.
	* 
	* @param type $string
	*/
	public function setVersionstring( $string ) {
		$this->versionString = $string;
	}

	public function checkVersionString() {
	}

	/**
	* Main method for this task.
	* 
	* @return void
	*/
	public function main() {
		$this->setDefaults();
		
		if ( $this->file ) {
			$this->checkFile();
			$versionString = $this->parseFile( $this->file );
		} else if ( $this->versionString ) {
			$this->checkVersionString();
			$versionString = $this->versionString;
		} else {
			throw new BuildException(
				'You must specify either "file" or "versionstring".',
				$this->location
			);
		}
		
		$version = new VersionNumber( $versionString, $this->preRelease );
		if ( $this->custom ) {
			$version->setCustom( $this->custom );
		}
		if ( $this->buildSeparator ) {
			$version->setBuildSeparator( $this->buildSeparator );
		}
		if ( !$this->readOnly ) {
			$version->increment( $this->releaseType );
			if ( $this->file ) {
				$this->writeFile( $version->toNormalizedString(), $this->file );
			}
		}
		
		$string = '';
		if ( $this->format ) {
			$string = $version->getFormattedString( $this->format );
		} else {
			// If first and last are not set, they default to 0 and 3
			$string = $version->getPartialString( $this->first, $this->last );
		}
		
		$this->project->setProperty(
		 $this->property,
		 $string
		);
	}

	/*****************************************************************************
	 * 
	 * --------------------------- PRIVATE METHODS -------------------------------
	 * 
	 ****************************************************************************/

	/**
	* Convenience method for checking if a parameter is set.
	* 
	* @param  string $value 
	* @return boolean True if set, false if not.
	*/
	protected function isNotSet( $value ) {
		return ( $value === null || strlen( $value ) == 0 );
	}

	/**
	* Parses given file for version string and retrieves it.
	* 
	* Can handle comment lines but assumes everything else must be the version
	* string. Version string must be the only thing on that line, this method
	* doesn't currently check if there is something else, like comments.
	* 
	* Does not validate the format of version string.
	* 
	* @param  string $file File to be checked. Must be valid file.
	* @return string $versionString The version string.
	* @throws BuildException
	*/
	protected function parseFile( $file ) {
		$content = file_get_contents( $file );
		$versionString = '';
		if ( strpos( $content, "\n" ) !== false ) {
			$fileRows = explode( "\n", $content );
			foreach ( $fileRows as $row => $text ) {
				if ( trim($text) !== "" && strpos( $text, '#' ) === false ) {
					$versionString = trim( $text );
					$fileRows[$row] = $this->replaceToken;
				}
			}
			$this->fileRows = $fileRows;
		} else {
			$versionString = trim( $content );
		}
		if ( $versionString === "" ) {
			throw new BuildException(
				sprintf( 'No version string found in %s.', $file),
				$this->location
			);
		}
			return $versionString;
	}

	/**
	* Sets default values as defined by self::defaultValues. If a check method
	* exists for parameter, runs that too if parameter is not empty.
	* 
	* @return void
	*/
	protected function setDefaults() {
		foreach ( $this->defaultValues as $param => $value ) {
			if ( $this->$param === null || strlen( $this->$param ) == 0 ) {
				$this->$param = $value;
			} else {
				$checkName = 'check' . ucfirst( $param );
				if ( method_exists( $this, $checkName ) ) {
					$this->$checkName();
				}
			}
		}
	}

	/**
	* Validates given string or integer 
	* 
	* @param  type $release
	* @return integer Integer representation of the release type
	* @throws BuildException
	*/
	protected function validateReleaseType( $release, $property ) {
		$release = strtolower( $release );
		if ( isset( $this->releaseTypeMap[$release] ) ) {
			return (int)$this->releaseTypeMap[$release] - 1;
		} else {
			$int = array_search( $release, $this->releaseTypeMap );
			if ( $int !== false ) {
				return (int)$release - 1;
			}
		}
		throw new BuildException(
			sprintf( 'Unknown build type "%s" for "%s"', $release, $property ),
			$this->location
		);
	}

	/**
	* Writes version string to file.
	* 
	* @param string $versionString Version string
	* @param string $file          File to use
	*/
	protected function writeFile( $versionString, $file ) {
		if ( !empty( $this->fileRows ) ) {
			$temp = implode( "\n", $this->fileRows );
			$versionString = str_replace( $this->replaceToken, $versionString, $temp );
		}
		file_put_contents( $file, $versionString );
	}

	/**
	 * Set a mock project object for testing purposes.
	 * 
	 * @param object $project The mock project object
	 */
	public function setMockProject( $project ) {
		$this->project = $project;
	}
	
	/**
	 * 
	 * @param type $full
	 */
	public function reset( $full = false ) {
		$this->buildSeparator = null;
		$this->custom = null;
		$this->fileRows = null;
		$this->first = null;
		$this->format = null;
		$this->last = null;
		$this->preRelease = null;
		$this->readOnly = null;
		$this->releaseType = null;
		
		if ( $full ) {
			$this->file = null;
			$this->property = null;
			$this->versionString = null;
		}
	}
	
	/**
	 * An accessor method for testing purposes.
	 * 
	 * @param type $prop  Property to get
	 * @return mixed      The value of property if it exists
	 * @throws Exception
	 */
	public function testProp( $prop ) {
		if ( isset( $this->$prop ) ) {
			return $this->$prop;
		} else {
			throw new Exception( '"' . $prop . '" not found in ExtVersionTask' );
		}
	}
}
