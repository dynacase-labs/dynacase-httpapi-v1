# ============================================
# $Id: Makefile,v 1.1 2002/01/08 12:41:34 eric Exp $
# ============================================

# get the new appname (basename in uppercase)
appname = $(shell basename `pwd` | tr [a-z] [A-Z])

export appname


include $(utildir)/PubRule


DISTFILES = Makefile  $(appname).app $(appname)_init.php.in 

$(pubdir)/api:
	mkdir -p $@

$(pubdir)/api/.htaccess: api.htaccess $(pubdir)/api
	cp $< $@

$(pubdir)/api/apiv1.php: apiRouter.php  $(pubdir)/api
	cd $(pubdir)/api; \
	ln -sf ../$(appname)/$< apiv1.php

publish : $(pubdir)/api/.htaccess $(pubdir)/api/apiv1.php