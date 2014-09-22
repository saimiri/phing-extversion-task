# Extended Version Task for Phing

## Parameters

Required parameters are `property` and either `file` or `versionstring`.

| Parameter (default)		| Explanation |
|---------------------|-------------|
| releasetype (build)	| Defines which part of the version number is incremented. Possible values: major, minor, patch|bugfix|revision, build or integers 1-4. |
| file								| File that contains the version number. Version number is always stored as `major.minor.patch.build`. |
| property						| Name of the variable that is used to store the version number. |
| first (major)				| First part of the version number to get. |
| last (build)				| Last part of the version number to get. |
| readonly (false)		| True or false. If true, version number is not incremented. |
| format							| Custom format for version number. Supported tokens are: `%major%`, `%minor%`, `%patch%`, `%build%`, `%prerelease%` and `%custom%`. |
| versionstring				| If you want to use this task just to transform a version number into another format, you can use `versionstring` instead of specifying a file. |
| buildseparator (".")| If you want to use another character besides dot to separate the build number from the rest of the version number, please feel free to do so by using `buildseparator`. |
| prerelease					| A string to use as the pre-release part. For example: "alpha", "dev" etc. |

## Parameter examples

Basic usage. Increment the patch/bugfix version and set it as `version_number`.

```xml
<extversion releasetype="patch" file="./version.number" property="${version_number}" />
```
Same as above but uses integer instead of string.

```xml
<extversion releasetype="3" file="./version.number" property="${version_number}" />
```
Get only the build number. By default the task returns `major.minor.patch.build`.

```xml
<extversion releasetype="build" first="build" last="build" file="./version.number" property="${version_number}" />
```
Get the version number but do not increment it.

```xml
<extversion readonly="true" file="./version.number" property="${version_number}" />
```
Use custom format (`format`) for the version number. Also inject a custom string into it (`custom`).

```xml
<extversion format="%major%.%minor% (%custom%)" custom="Wobbling Walrus" file="./version.number" property="${version_number}" />
```
Use pre-defined string for version number instead of reading it from a file. Note that in this case the version number is not incremented but used as-is.

```xml
<extversion versionstring="1.2.3.700" releasetype="patch" property="${version_number}" />
```
Use non-default character for build separator.

```xml
<extversion buildseparator="+" releasetype="patch" file="./version.number" property="${version_number}" />
```
Set pre-release string. By default it is prefixed with a dash ("-") and put before build number. It may produce weird looking results so use `format` to make it more to your liking.

```xml
<extversion prerelease="dev" releasetype="patch" file="./version.number" property="${version_number}" />
```

## Todo

- Option to use current datetime as build number.
- Unit tests!