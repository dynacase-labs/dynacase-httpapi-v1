appname = $(shell basename `pwd` | tr [a-z] [A-Z])

rsync:
	mkdir -p $(pubdir)/$(appname)
	rsync -rupE --exclude Makefile . $(pubdir)/$(appname)

publish:rsync