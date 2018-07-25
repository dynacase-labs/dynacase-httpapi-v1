# get the new appname (basename in uppercase)
appname = $(shell basename `pwd` | tr [a-z] [A-Z])
export appname

include $(utildir)/PubRule

DISTFILES = Makefile  $(appname).app $(appname)_init.php.in 

SUBDIR= Layout Images Class rules.d

.PHONY: publish

$(pubdir)/api:
	mkdir -p $@

$(pubdir)/api/.htaccess: api.htaccess $(pubdir)/api
	cp $< $@

$(pubdir)/api/v1.php: v1.php $(pubdir)/api
	cp $< $@

publish : $(pubdir)/api/.htaccess $(pubdir)/api/v1.php
