rsync:
	mkdir -p $(pubdir)/$(appname)/rules.d
	rsync -rupE --exclude Makefile . $(pubdir)/$(appname)/rules.d

publish:rsync