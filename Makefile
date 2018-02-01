SHELL = /bin/bash

.PHONY : init_svn
init_svn:
	./init_svn.sh

.PHONY : release_svn
release_svn:
	./release_svn.sh
