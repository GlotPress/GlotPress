# release.sh
#
# Takes a tag to release, and syncs it to WordPress.org
#
# Notes:
#		- You must pass in a valid tag to the script as the first parameter
#		- You can pass an SVN user name (case sensitive) as the second parameter of the script if your SVN account is not the same as your current user id.
#		- You may be prompted for your SVN password.
#		- By default the plugin name used for WordPress.org is the directory name, if this is not the case, change the "PLUGIN=" line below.
#		- If the tag already exists in SVN the script will exit.
#		- The script will handle both added and deleted files.

TAG=$1
INBIN=""

# Check to see if we're in the bin directory, if so go up one as the script assumes we're in the root of the git repo.
if [ "${PWD##*/}" == "bin" ]; then
	cd ..
	INBIN="/bin"
fi

PLUGIN="${PWD##*/}"
TMPDIR=`mktemp -d`
TARFILE=`mktemp`
PLUGINDIR="$PWD"
PLUGINSVN="https://plugins.svn.wordpress.org/$PLUGIN"

echo "$TMPDIR"

if [ "$2" !=  "" ]; then
	SVN_OPTIONS=" --username $2"
fi

# Fail on any error
set -e

# Is the tag valid?
if [ -z "$TAG" ] || ! git rev-parse "$TAG" > /dev/null; then
	echo "Invalid tag. Make sure you tag before trying to release."
	cd "$PLUGINDIR$INBIN"
	exit 1
fi

if [[ $VERSION == "v*" ]]; then
	# Starts with an extra "v", strip for the version
	VERSION=${TAG:1}
else
	VERSION="$TAG"
fi


# Disable error trapping and then check if it already exist in SVN.
set +e

svn info "$PLUGINSVN/tags/$VERSION" > /dev/null 2>&1
if (( $? == 0 )); then
	echo "Tag already exists in SVN!"
	cd "$PLUGINDIR$INBIN"
	exit 1
fi

set -e

if [ -d "$TMPDIR" ]; then
	# Wipe it clean
	rm -rf "$TMPDIR"
fi

# Ensure the directory exists first
mkdir "$TMPDIR"

# Grab an unadulterated copy of SVN
svn co "$PLUGINSVN/trunk" "$TMPDIR" > /dev/null

# Extract files from the Git tag to there
git archive --format="tar" "$TAG" > "$TARFILE"
tar -C "$TMPDIR" -xf "$TARFILE"

# Switch to build dir
cd "$TMPDIR"

# Run build tasks
sed -e "s/{{TAG}}/$VERSION/g" < "$PLUGINDIR/bin/readme.template" > readme.temp
sed -e "s/##\(.*\)/=\1 =/g" < "$PLUGINDIR/CHANGELOG.md" > changelog.temp
cat readme.temp changelog.temp > readme.txt
rm readme.temp
rm changelog.temp

# Remove special files
rm "README.md"
rm ".gitattributes"
rm ".gitignore"
rm ".scrutinizer.yml"
rm "phpunit.xml"
rm -r "bin"
rm -r "tests"


# Disable error trapping and then check to see if there are any results, if not, don't run the svn add command as it fails.
set +e
svn status | grep -v "^.[ \t]*\..*" | grep "^?"
if (( $? == 0 )); then
	set -e
	svn status | grep -v "^.[ \t]*\..*" | grep "^?" | awk '{print $2}' | xargs svn add $SVN_OPTIONS
fi

# Find any deleted files and run svn delete.
set +e
tar -df "$TARNAME" 2>&1 | grep "Not found in archive"
if (( $? == 0 )); then
	set -e
	tar -df "$TARNAME" 2>&1 | grep "Not found in archive" | sed -e "s/tar: \(.*\): Warning:.*/\1/g" | xargs svn delete $SVN_OPTIONS
fi

set -e

rm "$TARFILE"

# Pause to allow checking
echo "About to commit $VERSION. Double-check $TMPDIR to make sure everything looks fine."
read -p "Type 'YES' in all capitals and then return to continue."

if [[ "$REPLY" == "YES" ]]; then

	# Commit the changes
	svn commit -m "Updates for $VERSION release." $SVN_OPTIONS

	# tag_ur_it
	svn copy "$PLUGINSVN/trunk" "$PLUGINSVN/tags/$VERSION" -m "Tagged v$VERSION." $SVN_OPTIONS

fi

# Go back to where we started and clean up the temp directory.
cd "$PLUGINDIR$INBIN"
rm -rf "$TEMPDIR"
