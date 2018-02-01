SHELL = /bin/bash

.PHONY : svn_init
svn_init:
	./svn_init.sh

.PHONY : svn_release
svn_release:
	./svn_release.sh
