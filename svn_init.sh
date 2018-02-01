#!/bin/bash

read -p "WordPress.org password: " password

svn co https://plugins.svn.wordpress.org/wp-quip /tmp/wp-quip-plugin-svn

rsync -Rrd --delete ./assets/ /tmp/wp-quip-plugin-svn/assets/
rsync -Rrd --delete --exclude 'assets' --exclude 'LICENSE' --exclude 'README.md' --exclude 'Makefile' --exclude '*.sh' ./ /tmp/wp-quip-plugin-svn/trunk/

cd /tmp/wp-quip-plugin-svn
svn add trunk/*
svn add assets/*
svn ci -m 'First version of the plugin' --username cloudposse --password "${password}"
svn cp trunk tags/1.0.0
svn ci -m 'Tagging version 1.0.0' --username cloudposse --password "${password}"
