
rsync:
	mkdir -p $(pubdir)/$(appname)/Class
	rsync -rupE --exclude Makefile . $(pubdir)/$(appname)/Class

publish:rsync