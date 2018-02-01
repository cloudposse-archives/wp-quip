#!/bin/bash

# Get the latest tag so we can show it
GIT_LATEST="$(git describe --abbrev=0 --tags)"

# Read the version we are going to release
read -p "Specify a version (ex: 2.0.0). Latest git tag is ${GIT_LATEST}: " version

# Read the WordPress.org password
read -p "WordPress.org password: " password

# Cleanup the old dir if it is there
rm -rf /tmp/wp-quip-plugin-svn

# Checkout the svn repo
svn co http://plugins.svn.wordpress.org/wp-quip /tmp/wp-quip-plugin-svn

echo "Copying files to trunk"
rsync -Rrd --delete --exclude 'assets' --exclude 'LICENSE' --exclude 'README.md' --exclude 'Makefile' --exclude '*.sh' ./ /tmp/wp-quip-plugin-svn/trunk/

cd /tmp/wp-quip-plugin-svn/

svn status | grep '^!' | awk '{print $2}' | xargs svn delete
svn add --force * --auto-props --parents --depth infinity -q

svn status

svn ci -m "Syncing v${version}" --username cloudposse --password "${password}"

echo "Creating release tag"

mkdir /tmp/wp-quip-plugin-svn/tags/${version}
svn add /tmp/wp-quip-plugin-svn/tags/${version}
svn ci -m "Creating tag for v${version}" --username cloudposse --password "${password}"

echo "Copying versioned files to v${version} tag"

svn cp --parents trunk/* tags/${version}

svn ci -m "Tagging v${version}" --username cloudposse --password "${password}"
